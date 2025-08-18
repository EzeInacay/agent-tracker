<?php
include 'db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=booking_history.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<tr>
        <th>Client Name</th>
        <th>Hotel Booked</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Total Price</th>
        <th>Ratehawk Price</th>
        <th>Final Price</th>
      </tr>";

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
}

echo "</table>";
?>
