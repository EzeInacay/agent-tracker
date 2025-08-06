<?php
session_start();

// Redirect to login if admin is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit();
}

// Save admin name to display later
$adminName = $_SESSION['admin_name'];

// Connect to katravel_system database
$conn = new mysqli("localhost", "root", "", "katravel_system");

// Stop if DB connection fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for adding new agents
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_agent"])) {
    $agent_id = $_POST["agent_id"];
    $agent_name = $_POST["agent_name"];
    $password = $_POST["password"];

    // Insert agent into 'users' table
    $stmt = $conn->prepare("INSERT INTO users (agent_id, password, agent_name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $agent_id, $password, $agent_name);
    $stmt->execute();
    $stmt->close();
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
      <a href="#manage-agents">Manage Agents</a>
      <a href="#">Reports</a>
      <a href="logout.php">Logout</a>
    </div>
  </nav>

  <!-- Main dashboard content -->
  <main class="dashboard-content">
    <h2 id="manage-agents">Manage Agents</h2>

    <!-- Form for adding a new agent -->
    <form class="agent-form" method="POST">
      <h3>Add New Agent</h3>
      <label>Agent ID:
        <input type="text" name="agent_id" required placeholder="Enter Agent ID">
      </label>
      <label>Password:
        <input type="password" name="password" required placeholder="Enter password">
      </label>
      <label>Agent Name:
        <input type="text" name="agent_name" required placeholder="Enter full name">
      </label>
      <button type="submit" name="add_agent">Add Agent</button>
    </form>

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

