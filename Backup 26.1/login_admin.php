<?php
session_start();

require_once "db_connect.php";


$role = $_POST['role'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$password_raw = $_POST['password'] ?? '';
$remember = isset($_POST['remember']); // ✅ check if Remember Me is ticked

// --- ADMIN LOGIN ---
if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password_raw, $row['password'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['admin_name'];

            // ✅ Remember Me: store cookie for 7 days
            if ($remember) {
                setcookie("remember_user", $user_id, time() + (7 * 24 * 60 * 60), "/");
            } else {
                setcookie("remember_user", "", time() - 3600, "/");
            }

            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // ❌ Wrong login
    header("Location: login.php?error=1");
    exit();

// --- AGENT LOGIN ---
} elseif ($role === 'agent') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE agent_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password_raw, $row['password'])) {
            $_SESSION['agent_id'] = $row['agent_id'];
            $_SESSION['agent_name'] = $row['agent_name'];

            // ✅ Remember Me
            if ($remember) {
                setcookie("remember_user", $user_id, time() + (7 * 24 * 60 * 60), "/");
            } else {
                setcookie("remember_user", "", time() - 3600, "/");
            }

            header("Location: agent_dashboard.php");
            exit();
        }
    }

    // ❌ Wrong login
    header("Location: login.php?error=1");
    exit();
}
?>
