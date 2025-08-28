<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db_connect.php";


// Fetch processed payout requests (excluding Request ID)
$sql = "SELECT agent_id, mode, details, amount, status, request_date 
        FROM payout_requests 
        WHERE status!='Pending' 
        ORDER BY request_date DESC";

$result = $conn->query($sql);

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=processed_payouts.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output column headers
echo "Agent ID\tMode\tDetails\tAmount\tStatus\tRequest Date\n";

// Output rows
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['agent_id'] . "\t" .
             $row['mode'] . "\t" .
             $row['details'] . "\t" .
             $row['amount'] . "\t" .   
             $row['status'] . "\t" .
             $row['request_date'] . "\n";
    }
}

$conn->close();
?>
