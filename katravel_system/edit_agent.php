<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $agent_id = $_POST['agent_id'];
    $agent_name = $_POST['agent_name'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("UPDATE users SET agent_name = ?, password = ? WHERE agent_id = ?");
    $stmt->bind_param("sss", $agent_name, $password, $agent_id);
    $stmt->execute();

    $stmt->close();
    $conn->close();
    header("Location: admin_dashboard.php");
    exit();
}

if (!isset($_GET['agent_id'])) {
    die("Agent ID missing");
}

$agent_id = $_GET['agent_id'];
$result = $conn->query("SELECT * FROM users WHERE agent_id = '$agent_id'");
$agent = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Agent</title>
</head>
<body>
    <h2>Edit Agent</h2>
    <form method="POST">
        <input type="hidden" name="agent_id" value="<?php echo htmlspecialchars($agent['agent_id']); ?>">
        <label>Agent Name:
            <input type="text" name="agent_name" value="<?php echo htmlspecialchars($agent['agent_name']); ?>" required>
        </label><br>
        <label>Password:
            <input type="text" name="password" value="<?php echo htmlspecialchars($agent['password']); ?>" required>
        </label><br>
        <button type="submit">Save Changes</button>
    </form>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
</body>
</html>
