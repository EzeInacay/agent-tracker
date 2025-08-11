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

// Get POST data
$role = $_POST['role'] ?? 'agent'; // Default to agent
$user_id = $_POST['user_id'] ?? '';
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($user_id) || empty($current_password) || empty($new_password) || empty($confirm_password)) {
    die("Error: All fields are required.");
}

if ($new_password !== $confirm_password) {
    die("Error: New passwords do not match.");
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
        die("Error: Current password is incorrect.");
    }

    // Hash the new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in DB
    $update_stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $id_column = ?");
    $update_stmt->bind_param("ss", $new_hash, $user_id);

    if ($update_stmt->execute()) {
        echo "Password updated successfully.";
    } else {
        echo "Error updating password.";
    }
} else {
    die("Error: User not found.");
}

$conn->close();
?>
