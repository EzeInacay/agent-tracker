<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];

    // Get request details
    $sql = "SELECT * FROM payout_requests WHERE request_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if (!$request) {
        header("Location: admin_requests.php?error=requestnotfound");
        exit();
    }

    $agentId = $request['agent_id'];
    $amount  = $request['amount'];

    if ($action === "Approved") {
        // ✅ Approve payout
        $sql = "UPDATE payout_requests 
                SET status='Approved', approval_date=NOW() 
                WHERE request_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $stmt->close();

    } elseif ($action === "Declined") {
        // ❌ Decline payout
        $sql = "UPDATE payout_requests 
                SET status='Declined', approval_date=NOW() 
                WHERE request_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $stmt->close();

    } else {
        header("Location: admin_requests.php?error=invalidaction");
        exit();
    }

    header("Location: admin_requests.php?success=updated");
    exit();
}

$conn->close();
?>
