<?php
session_start();

$host = "localhost";
$db = "katravel_system";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$role = $_POST['role'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$password_raw = $_POST['password'] ?? '';

if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password_raw, $row['password'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['admin_name'];
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // If login fails
    header("Location: login.html");
    exit();

} elseif ($role === 'agent') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE agent_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password_raw, $row['password'])) {
            $_SESSION['agent_id'] = $row['agent_id'];
            $_SESSION['agent_name'] = $row['agent_name'];
            header("Location: agent_dashboard.php");
            exit();
        }
    }

    // If login fails
    header("Location: login.html");
    exit();
}
?>
