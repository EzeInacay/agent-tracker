<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['agent_id'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]); 
    exit;
}
$agentId = $_SESSION['agent_id'];

// ✅ Use centralized DB connection
require_once "db_connect.php";

/* Mark ONLY unseen Approved/Declined as seen */
$sql = "UPDATE payout_requests
        SET seen = 1
        WHERE agent_id = ?
          AND TRIM(status) IN ('Approved','Declined')
          AND seen = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agentId);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "updated" => $stmt->affected_rows  // number of rows updated
    ]);
} else {
    echo json_encode([
        "success" => false,
        "msg" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
