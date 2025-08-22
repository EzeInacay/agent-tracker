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

// If confirmed, proceed to delete
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirm_delete'])) {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Delete</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('raw.png') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            margin: 100px auto;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
        }

        h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }

        p {
            margin-bottom: 30px;
        }

        form {
            display: inline-block;
        }

        button {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            margin: 0 10px;
        }

        .confirm {
            background-color: #dc3545;
            color: white;
        }

        .confirm:hover {
            background-color: #c82333;
        }

        .cancel {
            background-color: #6c757d;
            color: white;
        }

        .cancel:hover {
            background-color: #5a6268;
        }

        a {
            text-decoration: none;
        }
        /* Responsive Design */
@media (max-width: 1024px) {
  body {
    flex-direction: column;
    height: auto;
  }

  .left-side,
  .right-side {
    width: 100%;
    padding: 30px;
  }

  .left-side {
    text-align: center;
    align-items: center;
    justify-content: center;
  }

  nav a {
    display: inline-block;
    margin: 10px 15px;
  }

  .contact-box {
    margin: 20px auto;
  }
}

@media (max-width: 768px) {
  body {
    flex-direction: column;
    padding: 10px;
  }

  .left-side {
    padding: 20px;
    font-size: 0.9rem;
  }

  .right-side {
    padding: 20px;
  }

  .login-form {
    padding: 20px;
    max-width: 100%;
  }

  .login-form h2 {
    font-size: 20px;
  }

  .login-form input,
  .login-form select,
  .login-form button {
    font-size: 14px;
    padding: 10px;
  }
}

@media (max-width: 480px) {
  body {
    background-position: center;
  }

  .left-side h1 {
    font-size: 24px;
  }

  nav a {
    margin: 8px;
    font-size: 14px;
  }

  .login-form h2 {
    font-size: 18px;
  }

  .login-form button {
    font-size: 14px;
    padding: 10px;
  }
}
    </style>
</head>
<body>

    <div class="container">
        <h2>Confirm Delete</h2>
        <p>Are you sure you want to delete agent with ID: <strong><?php echo htmlspecialchars($agent_id); ?></strong>?</p>

        <form method="POST">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="confirm">Yes, Delete</button>
        </form>

        <a href="admin_dashboard.php"><button class="cancel">Cancel</button></a>
    </div>

</body>
</html>
