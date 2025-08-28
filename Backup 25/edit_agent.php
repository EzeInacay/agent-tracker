<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit();
}

require_once "db_connect.php";

// Save changes
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $agent_id = $_POST['agent_id'];
    $agent_name = $_POST['agent_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $commission_rate = $_POST['commission_rate']; // NEW FIELD
    $original_agent_id = $_POST['original_agent_id'];

    $stmt = $conn->prepare("UPDATE users 
                            SET agent_id=?, agent_name=?, email=?, contact_number=?, address=?, commission_rate=? 
                            WHERE agent_id=?");
    $stmt->bind_param("sssssis", $agent_id, $agent_name, $email, $contact_number, $address, $commission_rate, $original_agent_id);

    if ($stmt->execute()) {
        header("Location: view_agent.php?agent_id=" . urlencode($agent_id));
        exit();
    } else {
        die("Failed to update agent: " . $stmt->error);
    }
}

// Display form
if (!isset($_GET['agent_id'])) {
    die("Agent ID missing in URL.");
}

$agent_id = $_GET['agent_id'];

// Use prepared statement to fetch agent
$stmt = $conn->prepare("SELECT * FROM users WHERE agent_id = ?");
$stmt->bind_param("s", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();
$stmt->close();

if (!$agent) {
    die("Agent not found. Please check the agent_id in the URL.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Agent</title>
    <style>
        body {
            background: url('raw.png') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            margin: 80px auto;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
            text-align: center;
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"], input[type="email"], input[type="number"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }

        img.profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Agent</h2>

        <?php if (!empty($agent['profile_pic']) && file_exists($agent['profile_pic'])): ?>
            <img class="profile-pic" src="<?= htmlspecialchars($agent['profile_pic']) ?>" alt="Profile Picture">
        <?php else: ?>
            <img class="profile-pic" src="default_avatar.png" alt="Profile Picture">
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('Are you sure you want to save changes?');">
            <input type="hidden" name="original_agent_id" value="<?= htmlspecialchars($agent['agent_id']) ?>">

            <label>Agent Name</label>
            <input type="text" name="agent_name" value="<?= htmlspecialchars($agent['agent_name']) ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($agent['email']) ?>" required>

            <label>Agent ID</label>
            <input type="text" name="agent_id" value="<?= htmlspecialchars($agent['agent_id']) ?>" required>

            <label>Contact</label>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($agent['contact_number']) ?>" required>

            <label>Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($agent['address']) ?>" required>

            <!-- NEW FIELD -->
            <label>Commission Rate (%)</label>
            <input type="number" name="commission_rate" value="<?= htmlspecialchars($agent['commission_rate'] ?? 60) ?>" min="1" max="100" required>

            <button type="submit">Save Changes</button>
        </form>

        <a href="view_agent.php?agent_id=<?= urlencode($agent['agent_id']) ?>" style="display:inline-block; margin-top:15px; padding:10px 15px; background:#007bff; color:white; border-radius:8px; text-decoration:none; font-weight:bold;">View Profile</a>
    </div>
</body>
</html>
