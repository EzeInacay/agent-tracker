<?php
// DATABASE CONNECTION
$host = 'localhost';
$user = 'root';
$password = ''; // Set your MySQL password if needed
$database = 'katravel_system';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
session_start();
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking History</title>
    <link rel="stylesheet" href="admin_bookings.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="nav-left">
        <strong>🛠️ Welcome, <?php echo isset($adminName) ? htmlspecialchars($adminName) : 'Admin'; ?></strong>
    </div>
    <div class="nav-right">
        <a href="admin_dashboard.php">Booking History</a>
        <a href="login.html">Logout</a>
    </div>
</nav>



<!-- Booking Table -->
<div class="container">
    <h2>Booking History</h2>
    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Hotel Booked</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Total Price</th>
                <th>Ratehawk Price</th>
                <th>Final Price</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT client_name, hotel_booked, start_date, end_date, total_price, ratehawk_price, final_price FROM bookings";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['client_name']}</td>
                            <td>{$row['hotel_booked']}</td>
                            <td>{$row['start_date']}</td>
                            <td>{$row['end_date']}</td>
                            <td>{$row['total_price']}</td>
                            <td>{$row['ratehawk_price']}</td>
                            <td>{$row['final_price']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No bookings found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <form action="excel_download.php" method="post">
    <button type="submit" class="download-btn">Download Excel File</button>
</form>
</div>

</body>
</html>
