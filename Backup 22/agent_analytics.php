<?php
session_start();

if (!isset($_SESSION['agent_id'], $_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentName = $_SESSION['agent_name'];

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all agents
$agents_result = $conn->query("SELECT agent_id, agent_name FROM users");

// Prepare arrays for rankings & daily data
$rankingData = [];
$dailyData = [];

while($agent = $agents_result->fetch_assoc()) {
    $agent_id = $agent['agent_id'];

    // Total bookings & earnings per agent
    $stats_res = $conn->query("
        SELECT COUNT(*) AS total_bookings, SUM(final_price - ratehawk_price) AS total_earnings
        FROM bookings
        WHERE agent_id = '$agent_id'
    ")->fetch_assoc();

    $rankingData[] = [
        'agent_id' => $agent_id,
        'agent_name' => $agent['agent_name'],
        'total_bookings' => $stats_res['total_bookings'] ?? 0,
        'total_earnings' => $stats_res['total_earnings'] ?? 0
    ];

    // Daily data for combined chart
    $daily_res = $conn->query("
        SELECT DATE(bs.commission_date) AS day,
               SUM(bs.earnings) AS earnings,
               COUNT(b.booking_id) AS bookings
        FROM bookings b
        JOIN booking_status bs ON b.booking_id = bs.booking_id
        WHERE b.agent_id = '$agent_id'
        GROUP BY DATE(bs.commission_date)
        ORDER BY day ASC
    ");

    $dailyData[$agent_id] = [];
    while($row = $daily_res->fetch_assoc()) {
        $dailyData[$agent_id][] = $row;
    }
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
<title>Analytics</title>
<link rel="stylesheet" href="analytics.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="navbar">
    <div class="left">
        Agent: <strong><?= htmlspecialchars($agentData['agent_name'] ?? 'Unknown') ?></strong>
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

        <!-- Combined Charts -->
        <div class="charts-panel">
            <h3>Total Bookings by Day</h3>
            <canvas id="bookingsChart"></canvas>

            <h3>Total Earnings by Day</h3>
            <canvas id="earningsChart"></canvas>
        </div>
    </div>
</main>

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


const dailyData = <?= json_encode($dailyData) ?>;

// Get all unique days
let allDays = [];
Object.values(dailyData).forEach(agentDays => {
    agentDays.forEach(d => {
        if(!allDays.includes(d.day)) allDays.push(d.day);
    });
});
allDays.sort();

// Combine bookings and earnings for all agents
const combinedBookings = allDays.map(day => {
    let total = 0;
    Object.values(dailyData).forEach(agentDays => {
        const match = agentDays.find(d => d.day === day);
        if(match) total += parseInt(match.bookings);
    });
    return total;
});

const combinedEarnings = allDays.map(day => {
    let total = 0;
    Object.values(dailyData).forEach(agentDays => {
        const match = agentDays.find(d => d.day === day);
        if(match) total += parseFloat(match.earnings);
    });
    return total;
});

// Bookings Chart
new Chart(document.getElementById('bookingsChart').getContext('2d'), {
    type: 'line',
    data: { labels: allDays, datasets: [{
        label: 'Total Bookings',
        data: combinedBookings,
        borderColor: 'rgb(75,192,192)',
        fill: false,
        tension: 0.2
    }]},
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Earnings Chart
new Chart(document.getElementById('earningsChart').getContext('2d'), {
    type: 'line',
    data: { labels: allDays, datasets: [{
        label: 'Total Earnings (₱)',
        data: combinedEarnings,
        borderColor: 'rgb(255,99,132)',
        fill: false,
        tension: 0.2
    }]},
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
