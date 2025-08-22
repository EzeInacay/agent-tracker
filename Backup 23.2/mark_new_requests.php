<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]); 
    exit;
}

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "msg" => $conn->connect_error]); 
    exit;
}

/* Mark all unseen Pending requests as seen */
$sql = "UPDATE payout_requests
        SET seen_admin = 1
        WHERE TRIM(status) = 'Pending'
          AND seen_admin = 0";
$ok = $conn->query($sql);

echo json_encode([
    "success" => $ok,
    "updated" => $conn->affected_rows
]);

$conn->close();
