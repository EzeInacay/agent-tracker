<?php
session_start();

if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentId = $_SESSION['agent_id'];
$agentName = $_SESSION['agent_name'];

$conn = new mysqli("localhost", "root", "", "katravel_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get booking history
$sql = "
    SELECT 
        b.client_name, 
        b.hotel_booked, 
        bs.booking_status, 
        bs.earnings, 
        bs.payout_date
    FROM bookings b
    JOIN booking_status bs ON b.booking_id = bs.booking_id
    WHERE b.agent_id = ?
    ORDER BY b.booking_id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $agentId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Agent Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-image: url('raw.png'); /* Set your background image here */
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    /* Top navigation bar with blue background and white text */
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

    /* Wrapper for content */
    .dashboard-container {
      max-width: 900px;
      margin: 30px auto;
      padding: 20px;
    }

    /* New Booking Form Box (semi-transparent) */
    .booking-form-box {
      background: rgba(255, 255, 255, 0.85); /* slightly see-through */
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
      margin-bottom: 40px;
    }

    .booking-form-box h2 {
      margin-bottom: 20px;
    }

    .booking-form label {
      display: block;
      margin-bottom: 15px;
    }

    .booking-form input {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    .booking-form button {
      background-color: #28a745;
      color: white;
      padding: 12px 20px;
      border: none;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 10px;
    }

    .booking-form button:hover {
      background-color: #218838;
    }

    /* Booking History Box */
    .history-box {
      background: rgba(255, 255, 255, 0.85);
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }

    .history-box h2 {
      margin-bottom: 15px;
    }

    .booking-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .booking-table th, .booking-table td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: center;
    }

    .booking-table th {
      background-color: #f2f2f2;
    }
  </style>
</head>
<body>

  <!-- Top Navigation Bar -->
  <div class="navbar">
    <div>Agent: <?php echo htmlspecialchars($agentName); ?></div>
    <div>
      <a href="agent_payout.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="dashboard-container">

    <!-- New Booking Form -->
    <div class="booking-form-box">
      <h2>New Booking</h2>
      <form class="booking-form" method="POST" action="submit_booking.php">
        <label>Client Name:
          <input type="text" name="client_name" required>
        </label>

        <label>Hotel Booked:
          <input type="text" name="hotel_booked" required>
        </label>

        <label>Start Date:
          <input type="date" name="start_date" required>
        </label>

        <label>End Date:
          <input type="date" name="end_date" required>
        </label>

        <label>Total Price:
          <input type="number" name="total_price" step="0.01" required>
        </label>

        <label>RateHawk Price:
          <input type="number" name="ratehawk_price" step="0.01" required>
        </label>

        <label>Final Price:
          <input type="number" name="final_price" step="0.01" required>
        </label>

        <button type="submit">Submit Booking</button>
      </form>
    </div>

    <!-- Booking History -->
    <div class="history-box">
      <h2>Booking History</h2>
      <table class="booking-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Hotel</th>
            <th>Status</th>
            <th>Earnings</th>
            <th>Payout Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                <td><?php echo htmlspecialchars($row['hotel_booked']); ?></td>
                <td><?php echo htmlspecialchars($row['booking_status']); ?></td>
                <td>₱<?php echo number_format($row['earnings'], 2); ?></td>
                <td><?php echo $row['payout_date'] ?: 'N/A'; ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5">No bookings found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>

<?php
$conn->close();
?>