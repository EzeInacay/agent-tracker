<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "unseen" => 0]); 
    exit;
}

// ✅ include centralized DB connection
require_once "db_connect.php";

/* Count unseen Pending requests */
$sql = "SELECT COUNT(*) AS cnt
        FROM payout_requests
        WHERE TRIM(status) = 'Pending'
          AND seen_admin = 0";

$result = $conn->query($sql);
$data = $result ? $result->fetch_assoc() : ["cnt" => 0];

echo json_encode([
    "success" => true,
    "unseen" => (int)$data['cnt']
]);

$conn->close();
