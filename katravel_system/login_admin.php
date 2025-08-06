<?php
session_start();

$host = "localhost";
$db = "katravel_system";
$user = "root";
$pass = ""; // adjust based on your MySQL setup

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$role = $_POST['role'];
$user_id = $_POST['user_id'];
$password = $_POST['password'];

if ($role == 'admin') {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ? AND password = ?");
    $stmt->bind_param("ss", $user_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['admin_id'] = $row['admin_id'];
        $_SESSION['admin_name'] = $row['admin_name']; 
        header("Location: admin_dashboard.php");
        exit();
    } else {
		header("Location: login.html");
    }

} elseif ($role == 'agent') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE agent_id = ? AND password = ?");
    $stmt->bind_param("ss", $user_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc(); 
        $_SESSION['agent_id'] = $row['agent_id'];
        $_SESSION['agent_name'] = $row['agent_name'];
        header("Location: agent_dashboard.php");
        exit();
    } else {
        header("Location: login.html");
    }
}

?>