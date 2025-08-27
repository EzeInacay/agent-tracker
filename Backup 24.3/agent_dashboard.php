<?php
session_start();

if (!isset($_SESSION['agent_id'], $_SESSION['agent_name'])) {
    header("Location: login.php");
    exit();
}

$agentId   = $_SESSION['agent_id'];
$agentName = $_SESSION['agent_name'];

require_once "db_connect.php";

$where  = "WHERE b.agent_id = ?";
$params = [$agentId];
$types  = "s";

// Filters
if (!empty($_GET['search_client'])) {
    $where .= " AND b.client_name LIKE ?";
    $params[] = "%" . $_GET['search_client'] . "%";
    $types   .= "s";
}
if (!empty($_GET['search_hotel'])) {
    $where .= " AND b.hotel_booked LIKE ?";
    $params[] = "%" . $_GET['search_hotel'] . "%";
    $types   .= "s";
}
if (!empty($_GET['status_filter'])) {
    $where .= " AND bs.booking_status = ?";
    $params[] = $_GET['status_filter'];
    $types   .= "s";
}

$sql = "
    SELECT 
        b.booking_id, 
        b.client_name, 
        b.hotel_booked, 
        bs.booking_status, 
        (b.final_price - b.ratehawk_price) AS earnings, 
        bs.payout_date
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    $where
    ORDER BY b.booking_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result   = $stmt->get_result();
$statuses = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="agent_dashboard.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

<div class="navbar">
    <div class="left">
        Welcome, <?= htmlspecialchars($agentName) ?>
    </div>
    <div class="right">
        <a href="agent_analytics.php">Analytics</a>
        <a href="agent_profile.php">Profile</a>
        <a href="agent_dashboard.php">New Booking</a>
        <a href="agent_payout.php" id="dashboardLink">
            Dashboard <span id="notifDot" style="display:none;color:red;">●</span>
        </a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</div>



<div class="dashboard-container">

    <!-- Booking Form -->
    <div class="booking-form-box">
        <h2>New Booking</h2>
        <form class="booking-form" method="POST" action="submit_booking.php">
            <label>Client Name:<input type="text" name="client_name" required></label>
            <label>Hotel Booked:<input type="text" name="hotel_booked" required></label>
            <label>Start Date:<input type="date" name="start_date" required></label>
            <label>End Date:<input type="date" name="end_date" required></label>
            <label>Total Price:<input type="number" name="total_price" step="0.01" required></label>
            <label>RateHawk Price:<input type="number" name="ratehawk_price" step="0.01" required></label>
            <label>Final Price:<input type="number" name="final_price" step="0.01" required></label>
            <button type="submit">Submit Booking</button>
        </form>
    </div>

 <!-- Booking History -->
<div class="history-box">
    <h2>Booking History</h2>
<form method="GET" action="" class="history-search-form">
    <input type="text" name="search_client" placeholder="Search Client" 
           value="<?= htmlspecialchars($_GET['search_client'] ?? '') ?>">
    <input type="text" name="search_hotel" placeholder="Search Hotel" 
           value="<?= htmlspecialchars($_GET['search_hotel'] ?? '') ?>">
    <select name="status_filter">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= (($_GET['status_filter'] ?? '') === $s) ? 'selected' : '' ?>>
                <?= $s ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="search-btn">Search</button>
    <a href="agent_dashboard.php" class="search-btn clear-btn">Clear</a>
</form>

    <table class="booking-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Hotel</th>
                <th>Status</th>
                <th>Earnings</th>
                <th>Payout Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $status = $row['booking_status'];
                    $badgeColor = [
                        'Pending'   => 'orange',
                        'Confirmed' => 'green',
                        'Cancelled' => 'red',
                        'Completed' => 'gray'
                    ];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['client_name']) ?></td>
                        <td><?= htmlspecialchars($row['hotel_booked']) ?></td>
                        <td>
                            <div class="status-container">
                                <?php if (!in_array($status, ['Completed', 'Cancelled'])): ?>
                                    <form method="POST" action="update_status.php">
                                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                        <select name="booking_status" 
                                                style="background-color: <?= $badgeColor[$status] ?>;"
                                                onchange="if (confirmStatusChange(this)) this.form.submit();">
                                            <?php foreach ($statuses as $s): ?>
                                                <option value="<?= $s ?>" <?= ($s === $status) ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span class="status-badge" style="background-color: <?= $badgeColor[$status] ?>;">
                                        <?= $status ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>₱<?= number_format($row['earnings'], 2) ?></td>
                        <td><?= $row['payout_date'] ?: 'N/A' ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No bookings found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h4>Confirm Logout</h4>
        <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
            <button onclick="confirmLogout()" class="btn btn-danger">Yes, Logout</button>
            <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<script>
   
// Polling every 10s
function checkNotifications() {
    fetch("check_notifications.php")
        .then(res => res.json())
        .then(data => {
            if (data.success && data.unseen > 0) {
                document.getElementById("notifDot").style.display = "inline";
            } else {
                document.getElementById("notifDot").style.display = "none";
            }
        });
}
setInterval(checkNotifications, 10000);
checkNotifications(); // initial check

// Mark seen when Dashboard clicked
document.getElementById("dashboardLink").addEventListener("click", function(e) {
    e.preventDefault();
    fetch("mark_notifications.php")
        .then(() => {
            document.getElementById("notifDot").style.display = "none";
            window.location.href = "agent_payout.php"; // or admin_dashboard.php
        });
});


function confirmStatusChange(selectElement) {
    return confirm("Are you sure you want to change the status to '" + selectElement.value + "'?");
}
function openLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}
function confirmLogout() {
    window.location.href = 'logout.php';
}
</script>

</body>
</html>
<?php $conn->close(); ?>
