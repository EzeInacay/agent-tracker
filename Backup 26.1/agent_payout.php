<?php
session_start();

// ✅ Check if agent is logged in
if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentId   = $_SESSION['agent_id'];
$agentName = $_SESSION['agent_name'];

require_once "db_connect.php";

/* ================== COMMISSION RATE ================== */
// 📌 Get this agent's commission rate (default 60% if none set)
$sql = "SELECT commission_rate FROM users WHERE agent_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $agentId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$commissionRate    = $res['commission_rate'] ?? 60; // percent value
$commissionDecimal = $commissionRate / 100;         // convert to decimal (e.g., 0.6)

$stmt->close();


/* ================== BOOKINGS ================== */

// 📌 Total bookings of this agent
$sql = "SELECT COUNT(*) as total FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 📌 Confirmed bookings
$sql = "SELECT COUNT(*) as confirmed FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id = ? AND bs.booking_status='Confirmed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$confirmedBookings = $stmt->get_result()->fetch_assoc()['confirmed'] ?? 0;

// 📌 Pending bookings
$sql = "SELECT COUNT(*) as pending FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id = ? AND bs.booking_status='Pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$pendingBookings = $stmt->get_result()->fetch_assoc()['pending'] ?? 0;

// 📌 Completed bookings
$sql = "SELECT COUNT(*) as completed FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id = ? AND bs.booking_status='Completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$completedBookings = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;

// 📌 Cancelled bookings
$sql = "SELECT COUNT(*) as cancelled FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id = ? AND bs.booking_status='Cancelled'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$cancelledBookings = $stmt->get_result()->fetch_assoc()['cancelled'] ?? 0;


/* ================== EARNINGS ================== */

// 📌 Weekly earnings
$sql = "SELECT SUM((b.published_rate - b.contracting_rate) * (b.commission_rate / 100)) as weekly
        FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id=? 
        AND bs.booking_status IN ('Confirmed','Completed')
        AND DATE(b.start_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$weeklyEarnings = $stmt->get_result()->fetch_assoc()['weekly'] ?? 0;


// 📌 Gross commissions (all-time)
$sql = "SELECT SUM((b.published_rate - b.contracting_rate) * (b.commission_rate / 100)) as gross
        FROM booking_status bs
        INNER JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.agent_id=? 
        AND bs.booking_status IN ('Confirmed','Completed')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$gross = $stmt->get_result()->fetch_assoc()['gross'] ?? 0;

// 📌 Approved payouts (already paid to agent)
$sql = "SELECT SUM(amount) as paid
        FROM payout_requests 
        WHERE agent_id=? AND status='Approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$paid = $stmt->get_result()->fetch_assoc()['paid'] ?? 0;

// 📌 Net earnings (Gross - Paid)
$totalEarnings = $gross - $paid;

// ✅ Keep raw numeric value
$rawTotalEarnings = max($totalEarnings, 0);

// ✅ Format for display
$weeklyEarnings = number_format(max($weeklyEarnings, 0), 2);
$displayTotalEarnings = number_format($rawTotalEarnings, 2);


/* ================== PAYOUT REQUEST HISTORY ================== */
$sql = "SELECT request_id, request_date, amount, mode, provider, details, remarks, status, approval_date 
        FROM payout_requests WHERE agent_id=? ORDER BY request_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- ✅ critical for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="agent_payout.css?v=4">
</head>
<body>

<!-- NAVIGATION -->
<div class="navbar">
    <div><strong>Welcome, <?= htmlspecialchars($agentName) ?></strong></div>
    <div class="nav-links">
        <a href="agent_analytics.php">Analytics</a>
        <a href="agent_profile.php">Profile</a>
        <a href="agent_dashboard.php">New Booking</a>
        <a href="agent_payout.php" class="active" id="dashboardLink">
         Dashboard
        <span id="notifDot" class="notif-dot" style="display:none;"></span>
        </a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</div>

<!-- DASHBOARD -->
<div class="main-container">
    <div class="card-container">
        <!-- Booking -->
<div class="dashboard-card large">
    <h3>📋 Booking Status</h3>
    <p>Total: <strong><?= $totalBookings ?></strong></p>
    <p>Confirmed: <strong><?= $confirmedBookings ?></strong></p>
    <p>Pending: <strong><?= $pendingBookings ?></strong></p>
    <p>Completed: <strong><?= $completedBookings ?></strong></p>
    <p>Cancelled: <strong><?= $cancelledBookings ?></strong></p>
</div>

<!-- Earnings -->
<div class="dashboard-card large">
    <h3>💰 Earnings</h3>
    <p> Current Commission Rate: <strong><?= $commissionRate ?>%</strong></p>
    <p>This Week: <strong>₱<?= $weeklyEarnings ?></strong></p>
    <p>Available Balance: <strong>₱<?= $displayTotalEarnings ?></strong></p>
</div>


        <!-- Request Payout -->
        <div class="dashboard-card full">
            <h3>🏦 Request Payout</h3>
            <button onclick="openPayoutModal()" class="btn">Request Now</button>
        </div>

        <!-- Payout History -->
        <div class="dashboard-card full">
            <h3>📜 Payout Request History</h3>
            <?php if (count($history) == 0): ?>
                <p>No requests yet.</p>
            <?php else: ?>
                <!-- ✅ scroll container forces horizontal scroll on mobile -->
                <div class="table-container" role="region" aria-label="Payout Request History" tabindex="0">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date Requested</th>
                                <th>Amount (₱)</th>
                                <th>Mode</th>
                                <th>Provider</th>
                                <th>Account No./Email</th>
                                <th>Account Name</th>
                                <th>Status</th>
                                <th>Approval Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($history as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['request_date']) ?></td>
                                <td><?= number_format($req['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($req['mode']) ?></td>
                                <td><?= htmlspecialchars($req['provider']) ?></td>
                                <td><?= htmlspecialchars($req['details']) ?></td>
                                <td><?= htmlspecialchars($req['remarks']) ?></td>
                                <td><span class="status <?= strtolower($req['status']) ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                                <td>
                                    <?php
                                    if (empty($req['approval_date']) || $req['approval_date'] === '0000-00-00 00:00:00') {
                                        echo '—';
                                    } else {
                                        echo date("M d, Y h:i A", strtotime($req['approval_date']));
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payout Modal -->
<div id="payoutModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h4>Request Payout</h4>
        <form action="request_payout.php" method="POST" class="payout-form" onsubmit="return validateAmount()">
            
            <!-- Mode -->
            <div class="form-group">
                <label>Mode:</label>
                <select name="mode" id="modeSelect" required onchange="toggleProviders()">
                    <option value="" disabled selected>Select Mode</option>
                    <option value="Bank">Bank</option>
                    <option value="E-Wallet">E-Wallet</option>
                </select>
            </div>

            <!-- Provider -->
            <div class="form-group" id="providerGroup" style="display:none;">
                <label id="providerLabel">Provider:</label>
                <select name="provider" id="providerSelect" required></select>
            </div>

            <!-- Account Number -->
            <div class="form-group">
                <label>Account Number / Email:</label>
                <input type="text" name="details" required>
            </div>

            <!-- Account Name (Remarks) -->
            <div class="form-group">
                <label>Account Name:</label>
                <input type="text" name="remarks" required>
            </div>

        <!-- Amount -->
<div class="form-group">
    <label>Amount:</label>
    <input type="number" id="amountInput" name="amount" 
           max="<?= $rawTotalEarnings ?>" step="0.01" required>
    <small>Max: ₱<?= $displayTotalEarnings ?></small>
</div>


            <!-- Buttons -->
            <div class="form-buttons">
                <button type="submit" class="btn">Submit Request</button>
                <button type="button" onclick="closePayoutModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal-overlay" style="display:none;">
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
const notifDot = document.getElementById("notifDot");
const dashboardLink = document.getElementById("dashboardLink");

/* --- helpers --- */
async function api(url, opts={}) {
  const res = await fetch(url, {
    method: opts.method || 'GET',
    headers: { 'Cache-Control': 'no-store' },
    cache: 'no-store',
    keepalive: true,
  });
  const txt = await res.text();
  try { return JSON.parse(txt); } catch { return { success:false, raw:txt }; }
}

async function markSeen() {
  const data = await api('mark_notifications.php', { method:'POST' });
  if (data && data.success) {
    notifDot.style.display = 'none';
  }
  return data;
}

async function checkNotifications(initial=false) {
  const data = await api('check_notifications.php');
  if (data && data.success && Number(data.unseen) > 0) {
    notifDot.style.display = 'inline-block';
    if (initial && location.pathname.endsWith('agent_payout.php')) {
      await markSeen();
    }
  } else {
    notifDot.style.display = 'none';
  }
}

checkNotifications(true);
setInterval(checkNotifications, 10000);
document.addEventListener('visibilitychange', () => {
  if (!document.hidden) checkNotifications();
});

dashboardLink.addEventListener('click', async (e) => {
  e.preventDefault();
  await markSeen();
  window.location.href = 'agent_payout.php';
});

/* ✅ Dropdown provider options */
function toggleProviders() {
    const mode = document.getElementById("modeSelect").value;
    const providerGroup = document.getElementById("providerGroup");
    const providerSelect = document.getElementById("providerSelect");

    providerGroup.style.display = "block";
    providerSelect.innerHTML = "";

    if (mode === "Bank") {
        providerSelect.innerHTML = `
            <option value="BDO">BDO</option>
            <option value="BPI">BPI</option>
            <option value="Metrobank">Metrobank</option>
            <option value="Landbank">Landbank</option>
            <option value="UnionBank">UnionBank</option>
        `;
    } else if (mode === "E-Wallet") {
        providerSelect.innerHTML = `
            <option value="GCash">GCash</option>
            <option value="Maya">Maya</option>
            <option value="GrabPay">GrabPay</option>
            <option value="ShopeePay">ShopeePay</option>
        `;
    }
}

function validateAmount() {
    const max = parseFloat("<?= $rawTotalEarnings ?>"); // ✅ raw number
    const input = parseFloat(document.getElementById("amountInput").value);

    if (input > max) {
        alert("❌ You cannot request more than your available earnings (₱" + 
              max.toLocaleString(undefined, {minimumFractionDigits:2}) + ").");
        return false;
    }
    return true;
}


function openLogoutModal(){ document.getElementById('logoutModal').style.display='flex'; }
function closeLogoutModal(){ document.getElementById('logoutModal').style.display='none'; }
function confirmLogout(){ window.location.href='logout.php'; }

function openPayoutModal(){ document.getElementById('payoutModal').style.display='flex'; }
function closePayoutModal(){ document.getElementById('payoutModal').style.display='none'; }
</script>
</body>
</html>