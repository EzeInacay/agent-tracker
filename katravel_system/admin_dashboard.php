<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit();
}

$adminName = $_SESSION['admin_name'];
$conn = new mysqli("localhost", "root", "", "katravel_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Accept Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_request"])) {
    $request_id = $_POST["request_id"];
    $agent_id = trim($_POST["agent_id"]);

    $check = $conn->prepare("SELECT * FROM users WHERE agent_id = ?");
    $check->bind_param("s", $agent_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        echo "<script>alert('Agent ID already exists. Please use a different one.');</script>";
        $check->close();
    } else {
        $check->close();

        $stmt = $conn->prepare("SELECT * FROM requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $request = $res->fetch_assoc();
        $stmt->close();

        if ($request) {
            $hashed_password = password_hash($request['password'], PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (agent_id, agent_name, email, password, contact_number, address, profile_pic, status, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", 
                $agent_id,
                $request['full_name'],
                $request['email'],
                $hashed_password,
                $request['contact_number'],
                $request['address'],
                $request['profile_pic'],
                $request['status'],
                $request['created_at']
            );
            $stmt->execute();
            $stmt->close();

            /* Email
            $to = $request['email'];
            $subject = "Agent Application Approved";
            $message = "Hi " . $request['full_name'] . ",\n\nYour agent application has been approved.\nYour Agent ID is: $agent_id\n\nYou may now log in to your account.\n\n- KaTravel Admin";
            $headers = "From: admin@katravel.com\r\n" .
                       "Reply-To: admin@katravel.com\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            mail($to, $subject, $message, $headers);
			*/
            $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle Delete Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_request"])) {
    $request_id = $_POST["request_id"];
    $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->close();
	header("Location: admin_dashboard.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Top navigation bar -->
  <nav class="navbar">
    <div class="navbar-left">🛠️ Welcome, <?php echo htmlspecialchars($adminName); ?></div>
    <div class="navbar-right">
      <a href="#agent-requests">Agent Requests</a>
      <a href="#">Reports</a>
      <a href="logout.php">Logout</a>
    </div>
  </nav>

  <!-- Main dashboard content -->
  <main class="dashboard-content">
    <h2 id="agent-requests">Agent Requests</h2>

    <!-- Table showing all pending agent requests -->
    <table class="booking-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Contact</th>
          <th>Address</th>
          <th>Status</th>
          <th>Profile Pic</th>
          <th>Agent ID</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM requests WHERE status = 'pending'");
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
            <td>" . htmlspecialchars($row['id']) . "</td>
            <td>" . htmlspecialchars($row['full_name']) . "</td>
            <td>" . htmlspecialchars($row['email']) . "</td>
            <td>" . htmlspecialchars($row['contact_number']) . "</td>
            <td>" . htmlspecialchars($row['address']) . "</td>
            <td>" . htmlspecialchars($row['status']) . "</td>
            <td><img src='" . htmlspecialchars($row['profile_pic']) . "' alt='Profile' style='width:50px;height:50px;'></td>
            <td>
              <form method='POST' style='display:inline-block'>
                <input type='hidden' name='request_id' value='" . $row['id'] . "'>
                <input type='text' name='agent_id' required placeholder='Enter Agent ID'>
            </td>
            <td>
                <button type='submit' name='accept_request'>Accept</button>
              </form>
              <form method='POST' style='display:inline-block'>
                <input type='hidden' name='request_id' value='" . $row['id'] . "'>
                <button type='submit' name='delete_request' onclick=\"return confirm('Are you sure you want to reject this request?');\">Delete</button>
              </form>
            </td>
          </tr>";
        }
        ?>
      </tbody>
    </table>
	
	  <!-- Table showing all registered agents -->
    <h3>Existing Agents</h3>
    <table class="booking-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Agent ID</th>
          <th>Agent Name</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Query all agents from the 'users' table
        $result = $conn->query("SELECT * FROM users");
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
        <td>" . htmlspecialchars($row['agent_id']) . "</td>
        <td>" . htmlspecialchars($row['agent_id']) . "</td>
        <td>" . htmlspecialchars($row['agent_name']) . "</td>
        <td>
          <a href='edit_agent.php?agent_id=" . urlencode($row['agent_id']) . "'><button>Edit</button></a>
          <a href='delete_agent.php?agent_id=" . urlencode($row['agent_id']) . "' onclick=\"return confirm('Are you sure you want to delete this agent?');\"><button>Delete</button></a>
        </td>
      </tr>";

        }
        $conn->close(); // Close DB connection after use
        ?>
		
      </tbody>
    </table>
  </main>
</body>
</html>
