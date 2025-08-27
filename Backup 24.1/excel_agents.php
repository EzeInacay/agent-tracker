<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch agents
$sql = "SELECT agent_id, agent_name, email, contact_number, address FROM users";
$result = $conn->query($sql);

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=agents.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output column headers
echo "Agent ID\tAgent Name\tEmail\tContact\tAddress\n";

// Output rows
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['agent_id'] . "\t" .
             $row['agent_name'] . "\t" .
             $row['email'] . "\t" .
             $row['contact_number'] . "\t" .
             $row['address'] . "\n";
    }
}

$conn->close();
?>