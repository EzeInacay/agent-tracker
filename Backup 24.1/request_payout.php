<?php
session_start();

if (!isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

$agentId = $_SESSION['agent_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mode     = $_POST['mode'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $details  = $_POST['details'] ?? '';
    $remarks  = $_POST['remarks'] ?? '';
    $amount   = $_POST['amount'] ?? 0;

    // Validate input
    if (empty($mode) || empty($provider) || empty($details) || empty($remarks) || $amount <= 0) {
        echo "<script>alert('Invalid input. Please fill in all fields.'); window.location.href='agent_payout.php';</script>";
        exit();
    }

    // Insert payout request
    $sql = "INSERT INTO payout_requests (agent_id, mode, provider, details, remarks, amount, status, request_date) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssd", $agentId, $mode, $provider, $details, $remarks, $amount);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Payout request submitted successfully.'); window.location.href='agent_payout.php';</script>";
    } else {
        echo "<script>alert('❌ Error submitting request. Please try again.'); window.location.href='agent_payout.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
