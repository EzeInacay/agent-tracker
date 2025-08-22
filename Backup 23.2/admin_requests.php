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

// --- PENDING REQUESTS ---
$sql_pending = "SELECT * FROM payout_requests WHERE status='Pending' ORDER BY request_date DESC";
$pending = $conn->query($sql_pending);

// --- PROCESSED REQUESTS (with search + filter) ---
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$whereClauses = ["status!='Pending'"];

// Search filter
if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $whereClauses[] = "(
        agent_id LIKE '%$searchEscaped%'
        OR mode LIKE '%$searchEscaped%'
        OR details LIKE '%$searchEscaped%'
        OR amount LIKE '%$searchEscaped%'
        OR status LIKE '%$searchEscaped%'
        OR request_date LIKE '%$searchEscaped%'
    )";
}

// Status filter
if (!empty($status_filter)) {
    $statusEscaped = $conn->real_escape_string($status_filter);
    $whereClauses[] = "status = '$statusEscaped'";
}

$searchQuery = "WHERE " . implode(" AND ", $whereClauses);
$sql_processed = "SELECT * FROM payout_requests $searchQuery ORDER BY request_date DESC";
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
        <a href="analytics.php">Analytics</a>
        <a href="admin_requests.php" id="requestsLink">
    Payout Requests <span id="notifDot" style="display:none;color:red;font-size:18px;">●</span>
</a>

        <a href="admin_bookings.php">Booking History</a>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</nav>

<!-- Pending Payouts -->
<div class="container">
    <h2>Pending Payout Requests</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
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
</div>

<!-- Processed Payouts -->
<div class="container">
    <h2>Processed Payout Requests</h2>
    
<!-- Search + Filter + Download (same row) -->
<div class="search-download-bar">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Search payouts..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
            <option value="">All Statuses</option>
            <option value="Approved" <?php if($status_filter == 'Approved') echo 'selected'; ?>>Approved</option>
            <option value="Declined" <?php if($status_filter == 'Declined') echo 'selected'; ?>>Declined</option>
        </select>
        <button type="submit" class="search-btn">Search</button>
        <a href="admin_requests.php" class="clear-btn">Clear</a>
    </form>

        <form action="excel_payout.php" method="post" class="download-form">
        <button type="submit" class="btn-success">Download Excel File</button>
    </form>
</div>

    <div class="table-responsive">
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
                <tr><td colspan="6">No processed payout requests found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
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

// Poll every 10 seconds
function checkNewRequests() {
    fetch("check_new_requests.php")
        .then(res => res.json())
        .then(data => {
            if (data.success && data.unseen > 0) {
                document.getElementById("notifDot").style.display = "inline";
            } else {
                document.getElementById("notifDot").style.display = "none";
            }
        });
}
setInterval(checkNewRequests, 10000);
checkNewRequests(); // initial check

// When admin opens Payout Requests, mark as seen
document.getElementById("requestsLink").addEventListener("click", function(e) {
    e.preventDefault();
    fetch("mark_new_requests.php")
        .then(() => {
            document.getElementById("notifDot").style.display = "none";
            window.location.href = "admin_requests.php";
        });
});


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
