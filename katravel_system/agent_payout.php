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
    </style>
</head>

<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <span class="navbar-brand text-white">Agent: <?php echo htmlspecialchars($agentName); ?></span>
    <div>
        <a href="agent_dashboard.php">New Booking</a>
        <a href="logout.php">Logout</a>
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

</body>
</html>
