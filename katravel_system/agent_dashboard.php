<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['agent_id']) || !isset($_SESSION['agent_name'])) {
    header("Location: login.html");
    exit();
}

$agentId = $_SESSION['agent_id'];
$agentName = $_SESSION['agent_name'];
// Connect to katravel_system database
$conn = new mysqli("localhost", "root", "", "katravel_system");

// Stop if DB connection fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Fetch booking history for the logged-in agent
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
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="navbar">
    <div class="navbar-left">👤 Agent: <?php echo htmlspecialchars($agentName); ?></div>
    <div class="navbar-right">
      <a href="#">Dashboard</a>
      <a href="#">Bookings</a>
      <a href="logout.php">Logout</a>
    </div>
  </nav>

  <main class="dashboard-content">
    <h2>New Booking</h2>
    <form class="booking-form" method="POST" action="submit_booking.php">
      <label>Client Name:
        <input type="text" name="client_name" placeholder="Enter client name" required>
      </label>

      <label>Hotel Booked:
        <input type="text" name="hotel_booked" placeholder="Enter hotel name" required>
      </label>

      <label>Start Date:
        <input type="date" name="start_date" required>
      </label>

      <label>End Date:
        <input type="date" name="end_date" required>
      </label>

      <label>Total Price:
        <input type="number" name="total_price" step="0.01" placeholder="₱0.00" required>
      </label>

      <label>RateHawk Price:
        <input type="number" name="ratehawk_price" step="0.01" placeholder="₱0.00" required>
      </label>

      <label>Final Price:
        <input type="number" name="final_price" step="0.01" placeholder="₱0.00" required>
      </label>

      <button type="submit">Submit Booking</button>
    </form>

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
          <tr>
            <td colspan="5">No bookings found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</body>
</html>
<?php
$conn->close();
?>
