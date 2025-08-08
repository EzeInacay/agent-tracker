<?php
session_start();

if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentName = $_SESSION['agent_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="agent_payout.css">
</head>
<body>

<!-- NAVIGATION BAR -->
<div class="navbar">
    <div class="left">Agent: <strong><?= htmlspecialchars($agentName) ?></strong></div>
    <div class="right">
        <a href="agent_profile.php">Profile</a>
        <a href="agent_dashboard.php">New Booking</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</div>

<!-- DASHBOARD CARDS -->
<div class="main-container">
    <div class="card-container">
        <!-- Booking Status -->
        <div class="dashboard-card">
            <h3>📋 Booking Status</h3>
            <p>Total Bookings: <strong>--</strong></p>
            <p>Confirmed: <strong>--</strong></p>
            <p>Pending: <strong>--</strong></p>
        </div>

        <!-- Earnings -->
        <div class="dashboard-card">
            <h3>💰 Earnings</h3>
            <p>This Week: <strong>₱--</strong></p>
            <p>Total: <strong>₱--</strong></p>
        </div>

        <!-- Payout Info -->
        <div class="dashboard-card">
            <h3>📅 Payouts</h3>
            <p>Last: <strong>--</strong></p>
            <p>Next: <strong>--</strong></p>
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
