<?php
session_start();

if (!isset($_SESSION['agent_id'], $_SESSION['agent_name'])) {
    header("Location: login.php");
    exit();
}

$agentName = $_SESSION['agent_name'];
$agentId   = $_SESSION['agent_id'];

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all agents
$agents_result = $conn->query("SELECT agent_id, agent_name FROM users");

// Prepare arrays for rankings & daily data
$rankingData = [];
$dailyDataPerAgent = [];

while($agent = $agents_result->fetch_assoc()) {
    $agent_id_loop = $agent['agent_id'];

    // Total bookings & earnings per agent
    $stats_res = $conn->query("
        SELECT COUNT(*) AS total_bookings, SUM(final_price - ratehawk_price) AS total_earnings
        FROM bookings
        WHERE agent_id = '$agent_id_loop'
    ")->fetch_assoc();

    $rankingData[] = [
        'agent_id' => $agent_id_loop,
        'agent_name' => $agent['agent_name'],
        'total_bookings' => $stats_res['total_bookings'] ?? 0,
        'total_earnings' => $stats_res['total_earnings'] ?? 0
    ];

    // Daily data per agent
    $daily_res = $conn->query("
        SELECT DATE(bs.commission_date) AS day,
               SUM(bs.earnings) AS earnings,
               COUNT(b.booking_id) AS bookings
        FROM bookings b
        JOIN booking_status bs ON b.booking_id = bs.booking_id
        WHERE b.agent_id = '$agent_id_loop'
        GROUP BY DATE(bs.commission_date)
        ORDER BY day ASC
    ");

    $dailyDataPerAgent[$agent_id_loop] = [];
    while($row = $daily_res->fetch_assoc()) {
        $dailyDataPerAgent[$agent_id_loop][] = $row;
    }
}

// Daily combined data (all agents together)
$daily_sql = "
    SELECT DATE(bs.commission_date) AS day,
           SUM(b.final_price - b.ratehawk_price) AS earnings,
           COUNT(b.booking_id) AS bookings
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    GROUP BY DATE(bs.commission_date)
    ORDER BY day ASC
";
$daily_res = $conn->query($daily_sql);
$dailyDataAll = [];
while($row = $daily_res->fetch_assoc()){
    $dailyDataAll[] = $row;
}

// Sort rankings
usort($rankingData, fn($a,$b) => $b['total_bookings'] <=> $a['total_bookings']);
$rankingByEarnings = $rankingData;
usort($rankingByEarnings, fn($a,$b) => $b['total_earnings'] <=> $a['total_earnings']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Agent Analytics</title>
<link rel="stylesheet" href="analytics.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="navbar">
    <div class="left">
        Agent: <strong><?= htmlspecialchars($agentName) ?></strong>
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

<main class="dashboard-content">
    <div class="analytics-container">

        <!-- Rankings -->
        <div class="rankings-panel">
            <h3>Top Agents by Bookings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Agent Name</th>
                        <th>Total Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rankingData as $i => $agent): ?>
                    <tr <?= $agent['agent_id'] == $agentId ? 'style="background:#d4edda;"' : '' ?>>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($agent['agent_name']) ?></td>
                        <td><?= $agent['total_bookings'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Top Agents by Earnings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Agent Name</th>
                        <th>Total Earnings (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rankingByEarnings as $i => $agent): ?>
                    <tr <?= $agent['agent_id'] == $agentId ? 'style="background:#d4edda;"' : '' ?>>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($agent['agent_name']) ?></td>
                        <td><?= number_format($agent['total_earnings'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Combined Charts -->
        <div class="charts-panel">
            <h3>Total Bookings by Day (All Agents)</h3>
            <canvas id="bookingsChart"></canvas>

            <h3>Total Earnings by Day (All Agents)</h3>
            <canvas id="earningsChart"></canvas>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-content">
            <h4>Confirm Logout</h4>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button onclick="confirmLogout()" class="btn btn-danger">Yes, Logout</button>
                <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</main>

<script>

// 🔔 Poll notifications every 10s
function checkNotifications() {
    fetch("check_notifications.php")
        .then(res => res.json())
        .then(data => {
            if (data.success && data.unseen > 0) {
                document.getElementById("notifDot").style.display = "inline";
            } else {
                document.getElementById("notifDot").style.display = "none";
            }
        })
        .catch(() => {
            document.getElementById("notifDot").style.display = "none";
        });
}
setInterval(checkNotifications, 10000);
checkNotifications(); // first check

// ✅ When Dashboard clicked, mark as seen
document.getElementById("dashboardLink").addEventListener("click", function(e) {
    e.preventDefault();
    fetch("mark_notifications.php")
        .then(() => {
            document.getElementById("notifDot").style.display = "none";
            window.location.href = "agent_payout.php";
        })
        .catch(() => {
            window.location.href = "agent_payout.php";
        });
});


// Charts (all agents combined)
const dailyDataAll = <?= json_encode($dailyDataAll) ?>;
const allDays = dailyDataAll.map(d => d.day);
const bookings = dailyDataAll.map(d => parseInt(d.bookings));
const earnings = dailyDataAll.map(d => parseFloat(d.earnings));

new Chart(document.getElementById('bookingsChart').getContext('2d'), {
    type: 'line',
    data: { labels: allDays, datasets: [{ label: 'Total Bookings', data: bookings, borderColor: 'rgb(75,192,192)', fill: false, tension: 0.2 }] },
    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('earningsChart').getContext('2d'), {
    type: 'line',
    data: { labels: allDays, datasets: [{ label: 'Total Earnings (₱)', data: earnings, borderColor: 'rgb(255,99,132)', fill: false, tension: 0.2 }] },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

function openLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}
function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php';
}
</script>
</body>
</html>
