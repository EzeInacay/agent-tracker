<?php
session_start();

if (!isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'];
    $newStatus = $_POST['booking_status'];

    // Validate input
    $validStatuses = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
    if (!in_array($newStatus, $validStatuses)) {
        die("Invalid status.");
    }

    $conn = new mysqli("localhost", "root", "", "katravel_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Only allow the logged-in agent to update their own bookings
    $stmt = $conn->prepare("
        UPDATE booking_status bs
        JOIN bookings b ON bs.booking_id = b.booking_id
        SET bs.booking_status = ?
        WHERE bs.booking_id = ? AND b.agent_id = ?
    ");
    $stmt->bind_param("sis", $newStatus, $bookingId, $_SESSION['agent_id']);

    if ($stmt->execute()) {
        header("Location: agent_dashboard.php");
        exit();
    } else {
        echo "Failed to update status.";
    }

    $stmt->close();
    $conn->close();
}
?>
