<?php
session_start();

if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentId = $_SESSION['agent_id'];
$agentName = $_SESSION['agent_name'];

// Connect to database
$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Total bookings
$sql_total = "
    SELECT COUNT(*) as total
    FROM booking_status bs
    INNER JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.agent_id = ?
";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Confirmed bookings
$sql_confirmed = "
    SELECT COUNT(*) as confirmed
    FROM booking_status bs
    INNER JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.agent_id = ? AND bs.booking_status = 'Confirmed'
";
$stmt = $conn->prepare($sql_confirmed);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$confirmedBookings = $stmt->get_result()->fetch_assoc()['confirmed'] ?? 0;

// Pending bookings
$sql_pending = "
    SELECT COUNT(*) as pending
    FROM booking_status bs
    INNER JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.agent_id = ? AND bs.booking_status = 'Pending'
";
$stmt = $conn->prepare($sql_pending);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$pendingBookings = $stmt->get_result()->fetch_assoc()['pending'] ?? 0;

// Weekly earnings
$sql_weekly = "
    SELECT SUM((b.final_price - b.ratehawk_price) * 0.6) AS weekly
    FROM booking_status bs
    INNER JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.agent_id = ? 
      AND bs.booking_status IN ('Confirmed', 'Completed')
      AND DATE(b.start_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
";
$stmt = $conn->prepare($sql_weekly);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$weeklyEarnings = $stmt->get_result()->fetch_assoc()['weekly'] ?? 0;

// Total earnings
$sql_total = "
    SELECT SUM((b.final_price - b.ratehawk_price) * 0.6) AS total
    FROM booking_status bs
    INNER JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.agent_id = ? 
      AND bs.booking_status IN ('Confirmed', 'Completed')
";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$totalEarnings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Format for display
$weeklyEarnings = number_format($weeklyEarnings, 2);
$totalEarnings = number_format($totalEarnings, 2);



// Last payout
$sql_last_payout = "
    SELECT MAX(bs.payout_date) as last_payout
    FROM booking_status bs
    INNER JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.agent_id = ? AND bs.payout_date IS NOT NULL
";
$stmt = $conn->prepare($sql_last_payout);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$lastPayout = $stmt->get_result()->fetch_assoc()['last_payout'] ?? '--';

// Next payout (every Friday)
$nextPayout = date('Y-m-d', strtotime('next friday'));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="agent_payout.css">
    <link rel="stylesheet" href="responsive.css">

</head>
<body>

<!-- NAVIGATION BAR -->
<div class="navbar">
    <div class="left">Agent: <strong><?= htmlspecialchars($agentName) ?></strong></div>
    <div class="right">
        <a href="agent_profile.php">Profile</a>
        <a href="agent_dashboard.php">New Booking</a>
        <a href="agent_payout.php">Dashboard</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</div>

<!-- DASHBOARD CARDS -->
<div class="main-container">
    <div class="card-container">

        <!-- Booking Status -->
        <div class="dashboard-card">
            <h3>📋 Booking Status</h3>
            <p>Total Bookings: <strong><?= $totalBookings ?></strong></p>
            <p>Confirmed: <strong><?= $confirmedBookings ?></strong></p>
            <p>Pending: <strong><?= $pendingBookings ?></strong></p>
        </div>

        <!-- Earnings -->
        <div class="dashboard-card">
            <h3>💰 Earnings</h3>
            <p>This Week: <strong>₱<?= number_format($weeklyEarnings, 2) ?></strong></p>
            <p>Total: <strong>₱<?= number_format($totalEarnings, 2) ?></strong></p>
        </div>

        <!-- Payout Info -->
        <div class="dashboard-card">
            <h3>📅 Payouts</h3>
            <p>Last: <strong><?= $lastPayout ?></strong></p>
            <p>Next: <strong><?= $nextPayout ?></strong></p>
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
