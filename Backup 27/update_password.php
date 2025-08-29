<?php
session_start();

require_once "db_connect.php";


// Get POST data
$role = $_POST['role'] ?? 'agent'; // Default to agent
$user_id = $_POST['user_id'] ?? '';
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($user_id) || empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: agent_profile.php?error=empty_fields");
    exit();
}

if ($new_password !== $confirm_password) {
    header("Location: agent_profile.php?error=password_mismatch");
    exit();
}

// Determine table and column based on role
$table = ($role === 'admin') ? 'admins' : 'users';
$id_column = ($role === 'admin') ? 'admin_id' : 'agent_id';

// Get current stored password
$stmt = $conn->prepare("SELECT password FROM $table WHERE $id_column = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Verify current password
    if (!password_verify($current_password, $row['password'])) {
        header("Location: agent_profile.php?error=wrong_password");
        exit();
    }

    // Hash the new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in DB
    $update_stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $id_column = ?");
    $update_stmt->bind_param("ss", $new_hash, $user_id);

    if ($update_stmt->execute()) {
        header("Location: agent_profile.php?password_changed=1");
        exit();
    } else {
        header("Location: agent_profile.php?error=update_failed");
        exit();
    }
} else {
    header("Location: agent_profile.php?error=user_not_found");
    exit();
}

$conn->close();
?>
