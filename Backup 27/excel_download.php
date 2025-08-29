<?php
// Database connection
require_once "db_connect.php";

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=booking_history.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output table with headers
echo "<table border='1'>";
echo "<tr>
        <th>Client Name</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Contracting Price</th>
        <th>Published Price</th>
        <th>Earnings</th>
        <th>Booking Status</th>
      </tr>";

// Fetch data with JOIN to include booking status
$sql = "
    SELECT 
        b.client_name,
        b.start_date,
        b.end_date,
        b.contracting_rate,
        b.published_rate,
        (b.published_rate - b.contracting_rate) AS earnings,
        bs.booking_status
    FROM bookings AS b
    LEFT JOIN booking_status AS bs ON bs.booking_id = b.booking_id
    ORDER BY b.booking_id DESC
";

$result = $conn->query($sql);

// Output each row
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['client_name']) . "</td>
                <td>{$row['start_date']}</td>
                <td>{$row['end_date']}</td>
                <td>{$row['contracting_rate']}</td>
                <td>{$row['published_rate']}</td>
                <td>{$row['earnings']}</td>
                <td>" . (!empty($row['booking_status']) ? $row['booking_status'] : 'N/A') . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No booking data found.</td></tr>";
}

echo "</table>";
?>
