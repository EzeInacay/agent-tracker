<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['agent_id'])) {
    die("No agent selected.");
}
$agent_id = $conn->real_escape_string($_GET['agent_id']);

// Fetch agent info
$sql = "SELECT * FROM users WHERE agent_id = '$agent_id'";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    die("Agent not found.");
}
$agent = $res->fetch_assoc();

// Daily analytics (Income + Bookings)
// Fetch daily analytics
$daily_sql = "
    SELECT DATE(bs.commission_date) AS day,
           SUM(b.final_price - b.ratehawk_price) AS total_income,
           COUNT(b.booking_id) AS total_bookings
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    WHERE b.agent_id = '$agent_id'
    GROUP BY DATE(bs.commission_date)
    ORDER BY day ASC
";
$daily_result = $conn->query($daily_sql);

$days = [];
$income_data = [];
$bookings_data = [];

while ($row = $daily_result->fetch_assoc()) {
    $days[] = $row['day'];
    $income_data[] = $row['total_income'] ?? 0;
    $bookings_data[] = $row['total_bookings'] ?? 0;
}

// Function to calculate Y-axis max (start at 20, increase by 5)
function calculateYAxisMax($data) {
    $max = 20;
    $dataMax = max($data);
    while ($dataMax > $max) {
        $max += 5;
    }
    return $max;
}

$incomeMax = max($income_data); // highest total earning
$bookingsMax = calculateYAxisMax($bookings_data);

// Fetch full booking history
$history_sql = "
    SELECT 
        b.booking_id,
        b.client_name,
        b.hotel_booked,
        b.start_date,
        b.end_date,
        bs.booking_status,
        (b.final_price - b.ratehawk_price) AS earnings
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    WHERE b.agent_id = '$agent_id'
    ORDER BY b.booking_id DESC
";
$result = $conn->query($history_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Agent Profile</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="view_agent.css">
<style>
/* Status badge colors */
.status-pending { background-color: orange; color: white; padding: 3px 8px; border-radius: 5px; }
.status-confirmed { background-color: green; color: white; padding: 3px 8px; border-radius: 5px; }
.status-cancelled { background-color: red; color: white; padding: 3px 8px; border-radius: 5px; }
.status-completed { background-color: gray; color: white; padding: 3px 8px; border-radius: 5px; }

/* Chart Canvas */
canvas { width: 100% !important; max-height: 500px; margin-top: 20px; }
</style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-left">Admin Panel</div>
    <div class="navbar-right">
      <a href="admin_dashboard.php">Dashboard</a>
      <a href="admin_bookings.php">Booking History</a>
      <a href="logout.php">Logout</a>
    </div>
</nav>

<main class="dashboard-content">

    <!-- Profile Section -->
    <div class="semi-transparent-box">
      <h2>Agent Profile: <?= htmlspecialchars($agent['agent_name']) ?></h2>
      <div class="profile-box">
        <div class="profile-left">
          <img src="<?= htmlspecialchars($agent['profile_pic'] ?: 'default.png') ?>" alt="Profile Picture">
          <a href="edit_agent.php?agent_id=<?= urlencode($agent['agent_id']) ?>">
            <button class="btn edit">Edit Agent</button>
          </a>
        </div>
        <div class="profile-right">
          <p><strong>ID:</strong> <?= htmlspecialchars($agent['agent_id']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($agent['email']) ?></p>
          <p><strong>Contact:</strong> <?= htmlspecialchars($agent['contact_number']) ?></p>
          <p><strong>Address:</strong> <?= htmlspecialchars($agent['address']) ?></p>
        </div>
      </div>
    </div>

 <!-- Analytics Section -->
<div class="semi-transparent-box" style="display:flex; gap:20px; flex-wrap:wrap;">
    <div style="flex:1; min-width:300px;">
        <h3 style="text-align:center;">Total Earnings</h3>
        <canvas id="incomeChart"></canvas>
    </div>
    <div style="flex:1; min-width:300px;">
        <h3 style="text-align:center;">Total Bookings</h3>
        <canvas id="bookingsChart"></canvas>
    </div>
</div>

    <!-- Booking History -->
    <div class="semi-transparent-box history-box">
      <h2>Booking History</h2>
      <table class="booking-table">
          <thead>
              <tr>
                  <th>Client</th>
                  <th>Hotel</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Booking Status</th>
                  <th>Earnings</th>
              </tr>
          </thead>
          <tbody>
              <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): 
                      $status = $row['booking_status'];
                      $badgeClass = 'status-' . strtolower($status);
                  ?>
                      <tr>
                          <td><?= htmlspecialchars($row['client_name']) ?></td>
                          <td><?= htmlspecialchars($row['hotel_booked']) ?></td>
                          <td><?= htmlspecialchars($row['start_date']) ?></td>
                          <td><?= htmlspecialchars($row['end_date']) ?></td>
                          <td><span class="<?= $badgeClass ?>"><?= $status ?></span></td>
                          <td>₱<?= number_format($row['earnings'],2) ?></td>
                      </tr>
                  <?php endwhile; ?>
              <?php else: ?>
                  <tr><td colspan="6">No bookings found.</td></tr>
              <?php endif; ?>
          </tbody>
      </table>
    </div>

</main>

<script>
const days = <?= json_encode($days) ?>;
const incomeData = <?= json_encode($income_data) ?>;
const bookingsData = <?= json_encode($bookings_data) ?>;
const incomeMax = <?= $incomeMax ?>;
const bookingsMax = <?= $bookingsMax ?>

// Total Earnings Chart
new Chart(document.getElementById('incomeChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: days,
        datasets: [{
            label: 'Earnings (₱)',
            data: incomeData,
            borderColor: 'blue',
            backgroundColor: 'rgba(0,0,255,0.2)',
            fill: true,
            tension: 0.2
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: incomeMax, // now max is the highest value in the data
                ticks: { callback: value => Math.round(value) }
            }
        },
        plugins: { legend: { display: false } }
    }
});

// Total Bookings Chart
new Chart(document.getElementById('bookingsChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: days,
        datasets: [{
            label: 'Bookings',
            data: bookingsData,
            borderColor: 'green',
            backgroundColor: 'rgba(0,255,0,0.2)',
            fill: true,
            tension: 0.2
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: bookingsMax,
                ticks: { stepSize: 1 } // show integer values for bookings
            }
        },
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html>
