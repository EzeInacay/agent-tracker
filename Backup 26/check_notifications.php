<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['agent_id'])) {
    echo json_encode(["success" => false, "unseen" => 0]); 
    exit;
}

$agentId = $_SESSION['agent_id'];

// ✅ centralized DB connection
require_once "db_connect.php";

/* Count unseen Approved/Declined (trim to avoid stray spaces) */
$sql = "SELECT COUNT(*) AS cnt
        FROM payout_requests
        WHERE agent_id = ?
          AND TRIM(status) IN ('Approved','Declined')
          AND seen = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);

if ($stmt->execute()) {
    $res = $stmt->get_result()->fetch_assoc();
    $count = (int)($res['cnt'] ?? 0);
} else {
    $count = 0;
}

echo json_encode([
    "success" => true,
    "unseen" => $count
]);

$stmt->close();
$conn->close();
