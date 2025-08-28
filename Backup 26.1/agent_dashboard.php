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
if (!empty($_GET['status_filter'])) {
    $where .= " AND bs.booking_status = ?";
    $params[] = $_GET['status_filter'];
    $types   .= "s";
}

$sql = "
    SELECT 
        b.booking_id, 
        b.client_name,
        b.contracting_rate,
        b.published_rate,  
        bs.booking_status, 
        b.commission_rate,
        (b.published_rate - b.contracting_rate) AS earnings
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
        <strong>Welcome, <?= htmlspecialchars($agentName) ?> </strong>
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
            <label>Start Date:<input type="date" name="start_date" required></label>
            <label>End Date:<input type="date" name="end_date" required></label>
            <label>Contracting Rate:<input type="number" name="contracting_rate" step="0.01" required></label>
            <label>Published Rate:<input type="number" name="published_rate" step="0.01" required></label>
            <button type="submit">Submit Booking</button>
        </form>
    </div>

    <!-- Booking History -->
    <div class="history-box">
        <h2>Booking History</h2>
        <form method="GET" action="" class="history-search-form">
            <input type="text" name="search_client" placeholder="Search Client" 
                   value="<?= htmlspecialchars($_GET['search_client'] ?? '') ?>">
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
<div class="table-responsive">
        <table class="booking-table">
<thead>
    <tr>
        <th>Client</th>
        <th>Contracting Rate</th>
        <th>Published Rate</th>
        <th>Status</th>
        <th>Profit</th>
        <th>Rate (%)</th>
        <th>Earning</th>
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
                        $commission = $row['earnings'] * ($row['commission_rate'] / 100);
                    ?>
<tr>
    <td><?= htmlspecialchars($row['client_name']) ?></td>
    <td>₱<?= number_format($row['contracting_rate'], 2) ?></td>
    <td>₱<?= number_format($row['published_rate'], 2) ?></td>
    <td>
        <div class="status-container">
            <?php if (!in_array($status, ['Completed', 'Cancelled'])): ?>
                <form method="POST" action="update_status.php">
                    <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                    <select name="booking_status" 
                            style="background-color: <?= $badgeColor[$status] ?>;"
                            onchange="if (confirmStatusChange(this)) this.form.submit();">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($s === $status) ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
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
    <td><?= number_format($row['commission_rate'], 2) ?>%</td>
    <td>₱<?= number_format($commission, 2) ?></td>
</tr>


                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
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
            window.location.href = "agent_payout.php";
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
