<?php

session_start();
$agent_id = $_SESSION['agent_id'] ?? null;

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'katravel_system';
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$agentData = [];
if ($agent_id) {
    $stmt = $conn->prepare("SELECT agent_name, email, contact_number, address, profile_pic FROM users WHERE agent_id = ?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agentData = $result->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agent Profile</title>
    <link rel="stylesheet" href="agent_profile.css">
    
</head>
<body>

<!-- NAVBAR -->
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
