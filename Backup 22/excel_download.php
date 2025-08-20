<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'katravel_system';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=booking_history.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output table with headers
echo "<table border='1'>";
echo "<tr>
        <th>Client Name</th>
        <th>Hotel Booked</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Total Price</th>
        <th>Ratehawk Price</th>
        <th>Final Price</th>
        <th>Booking Status</th>
      </tr>";

// ✅ Fetch data with JOIN to include booking status
$sql = "
    SELECT 
        b.client_name,
        b.hotel_booked,
        b.start_date,
        b.end_date,
        b.total_price,
        b.ratehawk_price,
        b.final_price,
        bs.booking_status
    FROM bookings AS b
    LEFT JOIN booking_status AS bs ON bs.booking_id = b.booking_id
    ORDER BY b.booking_id DESC
";

$result = $conn->query($sql);

// ✅ Output each row
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['client_name']) . "</td>
                <td>" . htmlspecialchars($row['hotel_booked']) . "</td>
                <td>{$row['start_date']}</td>
                <td>{$row['end_date']}</td>
                <td>{$row['total_price']}</td>
                <td>{$row['ratehawk_price']}</td>
                <td>{$row['final_price']}</td>
                <td>" . (!empty($row['booking_status']) ? $row['booking_status'] : 'N/A') . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No booking data found.</td></tr>";
}

echo "</table>";
?>
