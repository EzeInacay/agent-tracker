<?php
session_start();

if (!isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

$agentId = $_SESSION['agent_id'];

require_once "db_connect.php";

// Validate POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mode     = $_POST['mode'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $details  = $_POST['details'] ?? '';
    $remarks  = $_POST['remarks'] ?? '';
    $amount   = $_POST['amount'] ?? 0;

    // Validate input
    if (empty($mode) || empty($provider) || empty($details) || empty($remarks) || $amount <= 0) {
        $_SESSION['payout_error'] = "⚠️ Invalid input. Please fill in all fields.";
        header("Location: agent_payout.php");
        exit();
    }

    // Insert payout request
    $sql = "INSERT INTO payout_requests (agent_id, mode, provider, details, remarks, amount, status, request_date) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssd", $agentId, $mode, $provider, $details, $remarks, $amount);

    if ($stmt->execute()) {
        $_SESSION['payout_success'] = "✅ Payout request submitted successfully.";
    } else {
        $_SESSION['payout_error'] = "❌ Error submitting request. Please try again.";
    }

    $stmt->close();
}

$conn->close();

// Redirect back always
header("Location: agent_payout.php");
exit();
?>
