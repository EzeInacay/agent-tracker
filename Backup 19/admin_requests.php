<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Database connection
$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch payout requests by status
$sql_pending = "SELECT * FROM payout_requests WHERE status='Pending' ORDER BY request_date DESC";
$sql_processed = "SELECT * FROM payout_requests WHERE status!='Pending' ORDER BY request_date DESC";

$pending = $conn->query($sql_pending);
$processed = $conn->query($sql_processed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Payout Requests</title>
    <link rel="stylesheet" href="admin_requests.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="nav-left">
        <strong>Welcome, <?php echo htmlspecialchars($adminName); ?></strong>
    </div>
    <div class="nav-right">
        <a href="admin_requests.php">Payout Requests</a>
        <a href="admin_bookings.php">Booking History</a>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</nav>

<!-- Pending Payouts -->
<div class="container">
    <h2>Pending Payout Requests</h2>
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Agent ID</th>
                <th>Mode</th>
                <th>Details</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Request Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($pending && $pending->num_rows > 0): ?>
            <?php while($row = $pending->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['request_id'] ?></td>
                    <td><?= $row['agent_id'] ?></td>
                    <td><?= htmlspecialchars($row['mode']) ?></td>
                    <td><?= htmlspecialchars($row['details']) ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td><span class="status pending"><?= $row['status'] ?></span></td>
                    <td><?= $row['request_date'] ?></td>
                    <td>
                        <button class="btn btn-accept" onclick="openActionModal('Approved', <?= $row['request_id'] ?>)">Approve</button>
                        <button class="btn btn-delete" onclick="openActionModal('Declined', <?= $row['request_id'] ?>)">Decline</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No pending payout requests</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Processed Payouts -->
<div class="container">
    <h2>Processed Payout Requests</h2>
    <table>
        <thead>
            <tr>
                <th>Agent ID</th>
                <th>Mode</th>
                <th>Details</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Request Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($processed && $processed->num_rows > 0): ?>
            <?php while($row = $processed->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['agent_id'] ?></td>
                    <td><?= htmlspecialchars($row['mode']) ?></td>
                    <td><?= htmlspecialchars($row['details']) ?></td>
                    <td>₱<?= number_format($row['amount'], 2) ?></td>
                    <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                    <td><?= $row['request_date'] ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No processed payout requests</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Download Excel Button -->
    <div class="download-btn" style="text-align:center; margin-top:15px;">
        <form action="excel_payout.php" method="post">
            <button type="submit" class="btn-success">Download Excel File</button>
        </form>
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

<!-- Approve/Decline Confirmation Modal -->
<div id="actionModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h4 id="actionTitle">Confirm Action</h4>
        <p id="actionMessage"></p>
        <form id="actionForm" method="POST" action="update_payout.php">
            <input type="hidden" name="request_id" id="modalRequestId">
            <input type="hidden" name="action" id="modalAction">
            <div class="modal-buttons">
                <button type="submit" class="btn btn-danger">Yes, Confirm</button>
                <button type="button" onclick="closeActionModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
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

// Approve/Decline Modal
function openActionModal(action, requestId) {
    document.getElementById('actionTitle').innerText = "Confirm " + action;
    document.getElementById('actionMessage').innerText = "Are you sure you want to " + action.toLowerCase() + " this payout request?";
    document.getElementById('modalAction').value = action;
    document.getElementById('modalRequestId').value = requestId;
    document.getElementById('actionModal').style.display = 'flex';
}
function closeActionModal() {
    document.getElementById('actionModal').style.display = 'none';
}
</script>

</body>
</html>
<?php $conn->close(); ?>
