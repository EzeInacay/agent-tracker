<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['admin_name'];

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all agents
$agents_sql = "SELECT agent_id, agent_name FROM users";
$agents_result = $conn->query($agents_sql);

// Prepare arrays for rankings
$rankingData = [];
while($agent = $agents_result->fetch_assoc()) {
    $agent_id = $agent['agent_id'];
    
    // Total bookings & earnings per agent
    $stats_sql = "
        SELECT COUNT(*) AS total_bookings, 
               SUM(final_price - ratehawk_price) AS total_earnings
        FROM bookings 
        WHERE agent_id = '$agent_id'
    ";
    $stats_res = $conn->query($stats_sql)->fetch_assoc();

    $rankingData[] = [
        'agent_id' => $agent_id,
        'agent_name' => $agent['agent_name'],
        'total_bookings' => $stats_res['total_bookings'] ?? 0,
        'total_earnings' => $stats_res['total_earnings'] ?? 0
    ];
}

// ---- ✅ Daily combined data for ALL agents ----
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

$dailyData = [];
while($row = $daily_res->fetch_assoc()){
    $dailyData[] = $row;
}

// Sort rankings by total bookings descending
usort($rankingData, fn($a,$b) => $b['total_bookings'] <=> $a['total_bookings']);

// Sort rankings by total earnings descending
$rankingByEarnings = $rankingData;
usort($rankingByEarnings, fn($a,$b) => $b['total_earnings'] <=> $a['total_earnings']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics</title>
<link rel="stylesheet" href="analytics.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
<nav class="navbar">
    <div class="navbar-left">Welcome, <?php echo htmlspecialchars($adminName); ?></div>
  <div class="navbar-right">
	<a href="analytics.php">Analytics</a>
    <a href="admin_requests.php" id="requestsLink">
    Payout Requests <span id="notifDot" style="display:none;color:red;font-size:18px;">●</span>
</a>
    <a href="admin_bookings.php">Booking History</a>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</nav>

<main class="dashboard-content">

    <div class="analytics-container">

        <!-- Left Panel: Rankings -->
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
                    <tr>
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
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($agent['agent_name']) ?></td>
                        <td><?= number_format($agent['total_earnings'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Right Panel: Charts -->
        <div class="charts-panel">
            <h3>Total Bookings by Day</h3>
            <canvas id="bookingsChart"></canvas>

            <h3>Total Earnings by Day</h3>
            <canvas id="earningsChart"></canvas>
        </div>

    </div>

</main>

<!-- Logout Confirmation Modal -->
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
<script>
// ======================
// 🔔 Polling for new requests
// ======================
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

// ======================
// 🚪 Logout modal
// ======================
function openLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}
function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php';
}

// ======================
// 📊 Charts
// ======================
const dailyData = <?= json_encode($dailyData) ?>;

// Case 1: dailyData is a flat array (combined totals)
if (Array.isArray(dailyData)) {
    const allDays = dailyData.map(d => d.day);
    const combinedBookings = dailyData.map(d => parseInt(d.bookings));
    const combinedEarnings = dailyData.map(d => parseFloat(d.earnings));

    // Bookings Chart
    new Chart(document.getElementById('bookingsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: allDays,
            datasets: [{
                label: 'Total Bookings',
                data: combinedBookings,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: false,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Earnings Chart
    new Chart(document.getElementById('earningsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: allDays,
            datasets: [{
                label: 'Total Earnings (₱)',
                data: combinedEarnings,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: false,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

} else {
    // Case 2: dailyData is grouped by agent (per-agent datasets)
    let allDays = [];
    Object.values(dailyData).forEach(agentDays => {
        agentDays.forEach(d => {
            if(!allDays.includes(d.day)) allDays.push(d.day);
        });
    });
    allDays.sort();

    const bookingsDatasets = [];
    const earningsDatasets = [];
    Object.entries(dailyData).forEach(([agentId, agentDays]) => {
        const bookingValues = allDays.map(day => {
            const match = agentDays.find(d => d.day === day);
            return match ? parseInt(match.bookings) : 0;
        });
        const earningValues = allDays.map(day => {
            const match = agentDays.find(d => d.day === day);
            return match ? parseFloat(match.earnings) : 0;
        });

        bookingsDatasets.push({
            label: 'Agent ' + agentId,
            data: bookingValues,
            borderColor: 'rgb(' + Math.floor(Math.random()*255) + ',' + Math.floor(Math.random()*255) + ',150)',
            fill: false,
            tension: 0.2
        });
        earningsDatasets.push({
            label: 'Agent ' + agentId,
            data: earningValues,
            borderColor: 'rgb(' + Math.floor(Math.random()*255) + ',150,' + Math.floor(Math.random()*255) + ')',
            fill: false,
            tension: 0.2
        });
    });

    // Bookings Chart
    new Chart(document.getElementById('bookingsChart').getContext('2d'), {
        type: 'line',
        data: { labels: allDays, datasets: bookingsDatasets },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    // Earnings Chart
    new Chart(document.getElementById('earningsChart').getContext('2d'), {
        type: 'line',
        data: { labels: allDays, datasets: earningsDatasets },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
}
</script>

</body>
</html>
