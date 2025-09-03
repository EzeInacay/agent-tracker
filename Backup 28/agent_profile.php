<?php

session_start();
$agent_id = $_SESSION['agent_id'] ?? null;

require_once "db_connect.php";

$agentData = [];
if ($agent_id) {
    $stmt = $conn->prepare("SELECT agent_name, email, contact_number, address, profile_pic FROM users WHERE agent_id = ?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agentData = $result->fetch_assoc();
}

$analytics_sql = "
    SELECT DATE(bs.commission_date) AS day,
           SUM((b.published_rate - b.contracting_rate) * (b.commission_rate / 100)) AS total_income,
           COUNT(b.booking_id) AS total_bookings
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    WHERE b.agent_id = ?
    GROUP BY DATE(bs.commission_date)
    ORDER BY day ASC
";

$stmt = $conn->prepare($analytics_sql);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$analytics_result = $stmt->get_result();

$days = [];
$income_data = [];
$bookings_data = [];
while ($row = $analytics_result->fetch_assoc()) {
    $days[] = $row['day'];
    $income_data[] = $row['total_income'] ?? 0;
    $bookings_data[] = $row['total_bookings'] ?? 0;
}

$conn->close();

// Function to calculate Y-axis max for bookings
function calculateYAxisMax($data) {
    if (empty($data)) {
        return 20; // default if no data
    }
    $max = 20;
    $dataMax = max($data);
    while ($dataMax > $max) {
        $max += 5;
    }
    return $max;
}

$bookingsMax = calculateYAxisMax($bookings_data);
$incomeMax = !empty($income_data) ? max($income_data) : 0;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Agent Profile</title>
    <link rel="stylesheet" href="agent_profile.css">
    
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="left">
        <strong> Welcome, <?= htmlspecialchars($agentData['agent_name'] ?? 'Unknown') ?></strong>
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


<!-- PROFILE CONTAINER -->
<div class="container">
    <h2>My Profile</h2>
    <div class="profile-box">
        <div class="profile-left">
            <div class="profile-pic">
                <?php
                $profilePic = !empty($agentData['profile_pic']) ? htmlspecialchars($agentData['profile_pic']) : 'default-profile.png';
                ?>
                <img src="<?= $profilePic ?>" alt="Profile Picture" style="width:160px; height:160px; border-radius:50%; border:3px solid #007bff; object-fit:cover;">
            </div>
        </div>

        <div class="profile-right">
            <form action="update_profile.php" method="POST">
                <label>Agent ID</label>
                <input type="text" name="agent_id" value="<?= htmlspecialchars($agent_id) ?>" readonly>

                <label>Full Name</label>
                <input type="text" name="agent_name" value="<?= htmlspecialchars($agentData['agent_name'] ?? '') ?>" required>

                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($agentData['email'] ?? '') ?>" required>

                <label>Contact Number</label>
                <input type="text" name="contact_number" value="<?= htmlspecialchars($agentData['contact_number'] ?? '') ?>" required>

                <label>Address</label>
                <textarea name="address" rows="3"><?= htmlspecialchars($agentData['address'] ?? '') ?></textarea>

                <div class="button-group">
                    <!-- Only one "Change Password" button now -->
                    <button type="button" class="btn gray" onclick="openPasswordModal()">Change Password</button>
                </div>
            </form>
        </div>
    </div>
		<!-- ANALYTICS SECTION -->
    <div class="analytics-section" style="margin-top:40px;">
        <h2>My Analytics</h2>
        <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div style="flex:1; min-width:300px;">
                <h3 style="text-align:center;">Total Earnings</h3>
                <canvas id="incomeChart"></canvas>
            </div>
            <div style="flex:1; min-width:300px;">
                <h3 style="text-align:center;">Total Bookings</h3>
                <canvas id="bookingsChart"></canvas>
            </div>
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

<!-- Change Password Modal -->
<div id="passwordModal" class="modal-overlay" style="display: none;">
    <div class="modal-content change-password">
        <h3>Change Password</h3>
        <form action="update_password.php" method="POST">
            <input type="hidden" name="role" value="agent">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($agent_id) ?>">

            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="Enter current password" required>

            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Enter new password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter new password" required>

            <div class="modal-buttons">
                <button type="submit" class="btn btn-danger">Save</button>
                <button type="button" onclick="closePasswordModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal-overlay" style="display: none;">
    <div class="modal-content success">
        <h3>Password Changed</h3>
        <p>Your password has been updated successfully.</p>
        <div class="modal-buttons">
            <button onclick="closeSuccessModal()" class="btn btn-success">OK</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

// Analytics Data
const days = <?= json_encode($days) ?>;
const incomeData = <?= json_encode($income_data) ?>;
const bookingsData = <?= json_encode($bookings_data) ?>;
const incomeMax = <?= $incomeMax ?>;
const bookingsMax = <?= $bookingsMax ?>;
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
        scales: { y: { beginAtZero: true, max: incomeMax, ticks: { callback: value => Math.round(value) } } },
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
        scales: { y: { beginAtZero: true, max: bookingsMax, ticks: { stepSize: 1 } } },
        plugins: { legend: { display: false } }
    }
});

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


function openLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}
function confirmLogout() {
    window.location.href = 'logout.php';
}
function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'flex';
}
function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}
function openSuccessModal() {
    document.getElementById('successModal').style.display = 'flex';
}
function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
}

</script>

<?php if (isset($_GET['password_changed']) && $_GET['password_changed'] == 1): ?>
<script>
    window.onload = function() {
        openSuccessModal();
    };
</script>
<?php endif; ?>

</body>
</html>
