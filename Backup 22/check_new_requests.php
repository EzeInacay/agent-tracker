<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "unseen" => 0]); 
    exit;
}

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "msg" => $conn->connect_error]); 
    exit;
}

/* Count unseen Pending requests */
$sql = "SELECT COUNT(*) AS cnt
        FROM payout_requests
        WHERE TRIM(status) = 'Pending'
          AND seen_admin = 0";
$result = $conn->query($sql)->fetch_assoc();

echo json_encode([
    "success" => true,
    "unseen" => (int)($result['cnt'] ?? 0)
]);

$conn->close();
