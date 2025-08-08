<?php
session_start();

if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentId = $_SESSION['agent_id'];
$agentName = $_SESSION['agent_name'];

$conn = new mysqli("localhost", "root", "", "katravel_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Automated earnings: final_price - ratehawk_price
$sql = "
    SELECT 
        b.client_name, 
        b.hotel_booked, 
        bs.booking_status, 
        (b.final_price - b.ratehawk_price) AS earnings, 
        bs.payout_date
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    WHERE b.agent_id = ?
    ORDER BY b.booking_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="agent_dashboard.css">
</head>
<body>

<!-- Top Navigation Bar -->
<div class="navbar">
    <div>Agent: <?= htmlspecialchars($agentName) ?></div>
    <div>
        <a href="agent_profile.php">Profile</a>
        <a href="agent_payout.php">Dashboard</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</div>

<!-- Main Content -->
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
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['client_name']) ?></td>
                            <td><?= htmlspecialchars($row['hotel_booked']) ?></td>
                            <td><?= htmlspecialchars($row['booking_status']) ?></td>
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

</div>

<!-- Logout Confirmation Modal -->
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

<!-- Embedded JavaScript -->
<script>
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

<?php
$conn->close();
?>
