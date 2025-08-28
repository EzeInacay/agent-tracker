<?php
session_start();

if (!isset($_SESSION['agent_id'])) {
    // Redirect if not logged in
    header("Location: login.php");
    exit();
}

require_once "db_connect.php";

$client_name   = $_POST['client_name']   ?? '';
$hotel_booked  = $_POST['hotel_booked']  ?? '';
$start_date    = $_POST['start_date']    ?? '';
$end_date      = $_POST['end_date']      ?? '';
$total_price   = $_POST['total_price']   ?? 0;
$ratehawk_price= $_POST['ratehawk_price']?? 0;
$final_price   = $_POST['final_price']   ?? 0;

$agent_id = $_SESSION['agent_id'];

// ✅ Validate required fields
if (empty($client_name) || empty($hotel_booked) || empty($start_date) || empty($end_date)) {
    die("Missing required fields.");
}

// ✅ Fetch agent’s commission rate from users table
$stmt = $conn->prepare("SELECT commission_rate FROM users WHERE agent_id = ?");
$stmt->bind_param("s", $agent_id);
$stmt->execute();
$stmt->bind_result($commission_rate);
$stmt->fetch();
$stmt->close();

// ✅ Insert new booking with commission_rate included
$sql = "INSERT INTO bookings 
        (agent_id, client_name, hotel_booked, start_date, end_date, total_price, ratehawk_price, final_price, commission_rate)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssddd", 
    $agent_id, $client_name, $hotel_booked, $start_date, $end_date, 
    $total_price, $ratehawk_price, $final_price, $commission_rate
);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;

    // ✅ Insert into booking_status as before
    $status_sql = "INSERT INTO booking_status (booking_id, booking_status, earnings, payout_date)
                   VALUES (?, 'Pending', 0, NULL)";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $booking_id);
    $status_stmt->execute();

    header("Location: agent_dashboard.php?success=1");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

