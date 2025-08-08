<?php
session_start();

if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentName = $_SESSION['agent_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: url('raw.png') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }

    .navbar {
      display: flex;
      justify-content: space-between;
      background: #007bff;
      padding: 15px 30px;
      font-weight: bold;
      color: white;
    }

    .navbar a {
      color: white;
      margin-left: 15px;
      text-decoration: none;
    }

    .navbar a:hover {
      text-decoration: underline;
    }

    .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    text-align: center;
    max-width: 400px;
    width: 90%;
    font-family: Arial, sans-serif;
    animation: fadeIn 0.3s ease-in-out;
}

.modal-content h4 {
    margin-bottom: 10px;
    color: #333;
}

.modal-content p {
    margin-bottom: 20px;
}

.modal-buttons button {
    margin: 0 10px;
    padding: 10px 20px;
    border: none;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
}

.modal-buttons .btn-danger {
    background-color: #dc3545;
    color: white;
}

.modal-buttons .btn-secondary {
    background-color: #6c757d;
    color: white;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

    </style>
</head>

<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <span class="navbar-brand text-white">Agent: <?php echo htmlspecialchars($agentName); ?></span>
    <div>
        <a href="agent_dashboard.php">New Booking</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</nav>

<!-- Dashboard Cards -->
<div class="container mt-4">
    <div class="row">
        <!-- Booking Status -->
        <div class="col-md-4 mb-4">
            <div class="card p-3">
                <h5>📋 Booking Status</h5>
                <p>Total Bookings: <strong>--</strong></p>
                <p>Confirmed: <strong>--</strong></p>
                <p>Pending: <strong>--</strong></p>
            </div>
        </div>

        <!-- Earnings -->
        <div class="col-md-4 mb-4">
            <div class="card p-3">
                <h5>💰 Earnings</h5>
                <p>This Week: <strong>₱--</strong></p>
                <p>Total: <strong>₱--</strong></p>
            </div>
        </div>

        <!-- Payout Info -->
        <div class="col-md-4 mb-4">
            <div class="card p-3">
                <h5>📅 Payouts</h5>
                <p>Last: <strong>--</strong></p>
                <p>Next: <strong>--</strong></p>
            </div>
        </div>
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
