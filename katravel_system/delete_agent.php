<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit();
}
if (!isset($_GET['agent_id'])) {
    die("Agent ID not specified.");
}

$agent_id = $_GET['agent_id'];

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("DELETE FROM users WHERE agent_id = ?");
$stmt->bind_param("s", $agent_id);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: admin_dashboard.php");
exit();
