<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]); 
    exit;
}

// ✅ Centralized DB connection
require_once "db_connect.php";

/* Mark all unseen Pending requests as seen */
$sql = "UPDATE payout_requests
        SET seen_admin = 1
        WHERE TRIM(status) = 'Pending'
          AND seen_admin = 0";

if ($conn->query($sql) === TRUE) {
    echo json_encode([
        "success" => true,
        "updated" => $conn->affected_rows
    ]);
} else {
    echo json_encode([
        "success" => false,
        "msg" => $conn->error
    ]);
}

$conn->close();
