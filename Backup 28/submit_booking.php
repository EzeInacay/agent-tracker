<?php
session_start();

if (!isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db_connect.php";

$client_name      = $_POST['client_name']    ?? '';
$start_date       = $_POST['start_date']     ?? '';
$end_date         = $_POST['end_date']       ?? '';
$contracting_rate = $_POST['contracting_rate'] ?? 0;
$published_rate   = $_POST['published_rate']   ?? 0;

$agent_id = $_SESSION['agent_id'];

if (empty($client_name) || empty($start_date) || empty($end_date)) {
    die("Missing required fields.");
}

// Fetch agent's commission rate
$stmt = $conn->prepare("SELECT commission_rate FROM users WHERE agent_id = ?");
$stmt->bind_param("s", $agent_id);
$stmt->execute();
$stmt->bind_result($commission_rate);
$stmt->fetch();
$stmt->close();

// Insert new booking
$sql = "INSERT INTO bookings 
        (agent_id, client_name, start_date, end_date, contracting_rate, published_rate, commission_rate)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssddd", 
    $agent_id, $client_name, $start_date, $end_date, 
    $contracting_rate, $published_rate, $commission_rate
);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;

    // Calculate earnings
    $earnings = $published_rate - $contracting_rate;

    // Insert into booking_status with correct earnings
    $status_sql = "INSERT INTO booking_status (booking_id, booking_status, earnings)
                   VALUES (?, 'Pending', ?)";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("id", $booking_id, $earnings);
    $status_stmt->execute();

    header("Location: agent_dashboard.php?success=1");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
