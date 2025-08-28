<?php
session_start();

if (!isset($_SESSION['agent_id'])) {
    // Redirect if not logged in
    header("Location: login.php");
    exit();
}

require_once "db_connect.php";

// Get form data
$client_name = $_POST['client_name'] ?? '';
$hotel_booked = $_POST['hotel_booked'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$total_price = $_POST['total_price'] ?? 0;
$ratehawk_price = $_POST['ratehawk_price'] ?? 0;
$final_price = $_POST['final_price'] ?? 0;

$agent_id = $_SESSION['agent_id'];

// Simple validation (optional: improve this)
if (empty($client_name) || empty($hotel_booked) || empty($start_date) || empty($end_date)) {
    die("Missing required fields.");
}

// Insert into bookings table
$booking_sql = "INSERT INTO bookings (agent_id, client_name, hotel_booked, start_date, end_date, total_price, ratehawk_price, final_price)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($booking_sql);
$stmt->bind_param("ssssssdd", $agent_id, $client_name, $hotel_booked, $start_date, $end_date, $total_price, $ratehawk_price, $final_price);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;

    // Also insert into booking_status table
    $status_sql = "INSERT INTO booking_status (booking_id, booking_status, earnings, payout_date)
                   VALUES (?, 'Pending', 0, NULL)";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $booking_id);
    $status_stmt->execute();

    // Redirect with success
    header("Location: agent_dashboard.php?success=1");
    exit();
} else {
    echo "Error: " . $stmt->error;
}
?>
