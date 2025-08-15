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
    <link rel="stylesheet" href="responsive.css">

</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="nav-left">
        <strong>Welcome, <?php echo htmlspecialchars($adminName); ?></strong>
    </div>
    <div class="nav-right">
        <a href="admin_bookings.php">Booking History</a>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="#" onclick="openLogoutModal()">Logout</a>
    </div>
</nav>

<!-- Booking Table -->
<div class="container">
    <h2>Booking History</h2>
    <table>
        <thead>
            <tr>
                <th>Agent ID</th>
                <th>Client Name</th>
                <th>Hotel Booked</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Total Price</th>
                <th>Ratehawk Price</th>
                <th>Final Price</th>
                <th>Booking Status</th> <!-- NEW COLUMN -->
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "
                SELECT 
                    b.agent_id,
                    b.client_name,
                    b.hotel_booked,
                    b.start_date,
                    b.end_date,
                    b.total_price,
                    b.ratehawk_price,
                    b.final_price,
                    bs.booking_status
                FROM bookings b
                LEFT JOIN booking_status bs ON b.booking_id = bs.booking_id
                ORDER BY b.booking_id DESC
            ";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['agent_id']}</td>
                        <td>{$row['client_name']}</td>
                        <td>{$row['hotel_booked']}</td>
                        <td>{$row['start_date']}</td>
                        <td>{$row['end_date']}</td>
                        <td>{$row['total_price']}</td>
                        <td>{$row['ratehawk_price']}</td>
                        <td>{$row['final_price']}</td>
                        <td>" . ($row['booking_status'] ?? 'N/A') . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No bookings found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <form action="excel_download.php" method="post">
        <button type="submit" class="download-btn">Download Excel File</button>
    </form>
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
