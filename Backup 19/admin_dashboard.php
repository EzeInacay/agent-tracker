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

// Accept Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_request"])) {
    $request_id = $_POST["request_id"];
    $agent_id = trim($_POST["agent_id"]);

    $check = $conn->prepare("SELECT * FROM users WHERE agent_id = ?");
    $check->bind_param("s", $agent_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Agent ID already exists. Please use a different one.');</script>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $request = $res->fetch_assoc();
        $stmt->close();

        if ($request) {
            $hashed_password = $request['password'];
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

            $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Delete Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_request"])) {
    $request_id = $_POST["request_id"];
    $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="responsive.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-left">Welcome, <?php echo htmlspecialchars($adminName); ?></div>
  <div class="navbar-right">
	<a href="analytics.php">Analytics</a>
    <a href="admin_requests.php">Payout Requests</a>
    <a href="admin_bookings.php">Booking History</a>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="#" onclick="openLogoutModal()">Logout</a>
  </div>
</nav>

<main class="dashboard-content">

<!-- Agent Requests -->
<div class="semi-transparent-box">
  <h2>Agent Requests</h2>
  <div class="table-container">
    <table class="booking-table">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Email</th>
          <th>Contact</th>
          <th>Address</th>
          <th>Status</th>
          <th>Profile</th>
          <th>Agent ID</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM requests WHERE status = 'pending'");
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
            <td data-label='Full Name'>" . htmlspecialchars($row['full_name']) . "</td>
            <td data-label='Email'>" . htmlspecialchars($row['email']) . "</td>
            <td data-label='Contact'>" . htmlspecialchars($row['contact_number']) . "</td>
            <td data-label='Address'>" . htmlspecialchars($row['address']) . "</td>
            <td data-label='Status'>" . htmlspecialchars($row['status']) . "</td>
            <td data-label='Profile'>
              <img src='" . htmlspecialchars($row['profile_pic']) . "' alt='Profile'>
            </td>
            <td data-label='Agent ID'>
              <form method='POST' style='display:inline-block'>
                <input type='hidden' name='request_id' value='" . $row['id'] . "'>
                <input type='text' name='agent_id' required placeholder='Enter Agent ID'>
            </td>
            <td data-label='Action'>
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
  </div>
</div>

<!-- Existing Agents -->
<div class="semi-transparent-box">
  <h2>Existing Agents</h2>
  <div class="table-container">
    <table class="booking-table">
      <thead>
        <tr>
          <th>Agent ID</th>
          <th>Agent Name</th>
          <th>Email</th>
          <th>Contact</th>
          <th>Address</th>
          <th>Profile</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM users");
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
            <td data-label='Agent ID'>" . htmlspecialchars($row['agent_id']) . "</td>
            <td data-label='Agent Name'>" . htmlspecialchars($row['agent_name']) . "</td>
            <td data-label='Email'>" . htmlspecialchars($row['email']) . "</td>
            <td data-label='Contact'>" . htmlspecialchars($row['contact_number']) . "</td>
            <td data-label='Address'>" . htmlspecialchars($row['address']) . "</td>
            <td data-label='Profile'>
              <img src='" . htmlspecialchars($row['profile_pic']) . "' alt='Profile'>
            </td>
            <td data-label='Action'>
              <a href='view_agent.php?agent_id=" . urlencode($row['agent_id']) . "'><button>View Profile</button></a>
              <a href='delete_agent.php?agent_id=" . urlencode($row['agent_id']) . "' onclick=\"return confirm('Are you sure you want to delete this agent?');\"><button>Delete</button></a>
            </td>
          </tr>";
        }
        $conn->close();
        ?>
      </tbody>
    </table>
  </div>
</div>


<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h4>Confirm Logout</h4>
        <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
            <button onclick="confirmLogout()" class="btn btn-danger">Yes, Logout</button>
            <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<!-- Embedded JavaScript -->
<script>
function openLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function confirmLogout() {
    window.location.href = 'logout.php';
}
</script>
</main>

</body>
</html>


