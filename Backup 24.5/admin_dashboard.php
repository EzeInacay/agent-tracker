<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['admin_name'];

require_once "db_connect.php";


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

// Delete Agent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_agent"])) {
    $agent_id = $_POST["agent_id"];
    $stmt = $conn->prepare("DELETE FROM users WHERE agent_id = ?");
    $stmt->bind_param("s", $agent_id);
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
  <link rel="stylesheet" href="responsive.css">
  <link rel="stylesheet" href="admin_dashboard.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-left">Welcome, <?php echo htmlspecialchars($adminName); ?></div>
  <div class="navbar-right">
    <a href="analytics.php">Analytics</a>
    <a href="admin_requests.php" id="requestsLink">
      Payout Requests <span id="notifDot" style="display:none;color:red;font-size:18px;">●</span>
    </a>
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
                <td data-label='Status'>" . htmlspecialchars($row['status']) . "</td>
                <td data-label='Profile'><img src='" . htmlspecialchars($row['profile_pic']) . "' alt='Profile'></td>
                <td data-label='Agent ID'>
                    <form method='POST' style='display:inline-block'>
                        <input type='hidden' name='request_id' value='" . $row['id'] . "'>
                        <input type='text' name='agent_id' required placeholder='Enter Agent ID'>
                </td>
                <td data-label='Action'>
                        <button type='submit' name='accept_request'>Accept</button>
                    </form>
                    <form method='POST' style='display:inline-block' onsubmit='event.preventDefault(); openDeleteModal(this);'>
                        <input type='hidden' name='request_id' value='" . $row['id'] . "'>
                        <button type='submit' name='delete_request'>Delete</button>
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

<div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">

  <!-- 🔹 Left: Search + Clear -->
  <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">
    <input type="text" name="search_agent" placeholder="Search agents..." 
           value="<?php echo isset($_GET['search_agent']) ? htmlspecialchars($_GET['search_agent']) : ''; ?>"
           style="padding:6px; border-radius:5px; border:1px solid #ccc;">
    <button type="submit" style="background:#007bff; color:#fff; padding:6px 12px; border:none; border-radius:5px;">Search</button>
    <a href="admin_dashboard.php" style="background:#6c757d; color:#fff; padding:6px 12px; border-radius:5px; text-decoration:none;">Clear</a>
  </form>

  <!-- 🔹 Right: Buttons (Excel + Edit All Commission Rates) -->
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <button style="background:#ffc107; color:#000; padding:8px 15px; border:none; border-radius:5px; font-weight:bold;"
            onclick="openEditCommissionModal()">
      Edit All Commission Rates
    </button>
    <a href="excel_agents.php">
      <button style="background:#28a745; color:#fff; padding:8px 15px; border:none; border-radius:5px; font-weight:bold;">
        Download Excel File
      </button>
    </a>
  </div>

</div>

<!-- Edit All Commission Modal -->
<div id="editCommissionModal" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="background: #fff8e1; border: 2px solid #ffc107;">
        <h4 style="color: #ff9800; margin-bottom: 10px;">Edit All Commission Rates</h4>
        <p style="color: #555;">Enter the new commission rate (%) for all agents:</p>
        <input type="number" id="newCommissionRate" min="0" max="100" step="0.01" placeholder="e.g., 5.5"
               style="padding:6px; width:80px; border-radius:5px; border:1px solid #ccc;">

        <div class="modal-buttons" style="margin-top:20px;">
            <button class="btn" onclick="submitCommissionRate()" 
                    style="background:#ffc107; color:#000; padding:8px 20px; border-radius:5px; font-weight:bold; transition: 0.3s;">
                Save
            </button>
            <button class="btn btn-secondary" onclick="closeEditCommissionModal()" 
                    style="background:#6c757d; color:#fff; padding:8px 20px; border-radius:5px; font-weight:bold;">
                Cancel
            </button>
        </div>
    </div>
</div>




  <div class="table-container">
    <table class="booking-table">
      <thead>
        <tr>
          <th>Agent ID</th>
          <th>Agent Name</th>
          <th>Email</th>
          <th>Contact</th>
          <th>Commission Rate</th>
          <th>Profile</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $search = isset($_GET['search_agent']) ? $conn->real_escape_string($_GET['search_agent']) : '';
        $query = "SELECT * FROM users";
        if (!empty($search)) {
            $query .= " WHERE agent_id LIKE '%$search%' OR agent_name LIKE '%$search%' OR email LIKE '%$search%'";
        }
        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td data-label='Agent ID'>" . htmlspecialchars($row['agent_id']) . "</td>
                <td data-label='Agent Name'>" . htmlspecialchars($row['agent_name']) . "</td>
                <td data-label='Email'>" . htmlspecialchars($row['email']) . "</td>
                <td data-label='Contact'>" . htmlspecialchars($row['contact_number']) . "</td>
                <td data-label='Commission Rate'>" . number_format($row['commission_rate'], 2) . "%</td>
                <td data-label='Profile'><img src='" . htmlspecialchars($row['profile_pic']) . "' alt='Profile'></td>
                <td data-label='Action'>
                    <a href='view_agent.php?agent_id=" . urlencode($row['agent_id']) . "'><button>View Profile</button></a>
                    <form method='POST' style='display:inline-block' onsubmit='event.preventDefault(); openDeleteModal(this);'>
                        <input type='hidden' name='delete_request' value='" . $row['agent_id'] . "'>
                        <button type='submit' name='delete_agent' class='btn-delete'>Delete</button>
                    </form>
                </td>
            </tr>";
        }
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <h4>Confirm Delete</h4>
        <p id="deleteMessage">Are you sure you want to delete this item?</p>
        <div class="modal-buttons">
            <button onclick="confirmDelete()" class="btn btn-danger">Yes, Delete</button>
            <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<!-- Edit All Commission Modal -->
<div id="editCommissionModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h4>Edit All Commission Rates</h4>
        <p>Enter the new commission rate (%) for all agents:</p>
        <input type="number" id="newCommissionRate" min="0" max="100" step="0.01" placeholder="e.g., 5.5" style="padding:6px; width:80px; border-radius:5px; border:1px solid #ccc;">
        <div class="modal-buttons" style="margin-top:20px;">
            <button class="btn btn-success" onclick="submitCommissionRate()">Save</button>
            <button class="btn btn-secondary" onclick="closeEditCommissionModal()">Cancel</button>
        </div>
    </div>
</div>


<script>
  // Open and close modal
function openEditCommissionModal() {
    document.getElementById('editCommissionModal').style.display = 'flex';
}

function closeEditCommissionModal() {
    document.getElementById('editCommissionModal').style.display = 'none';
}

// Submit new commission rate
function submitCommissionRate() {
    let rate = document.getElementById('newCommissionRate').value;
    if (rate === '' || isNaN(rate) || rate < 0 || rate > 100) {
        alert('Please enter a valid commission rate between 0 and 100.');
        return;
    }

    // Send rate to PHP via fetch (AJAX)
    fetch('update_commission.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'rate=' + encodeURIComponent(rate)
    })
    .then(res => res.text())
    .then(data => {
        alert(data);  // server response
        closeEditCommissionModal();
        location.reload(); // reload to show updated rates
    })
    .catch(err => alert('Error: ' + err));
}

// Poll every 10 seconds
function checkNewRequests() {
    fetch("check_new_requests.php")
        .then(res => res.json())
        .then(data => {
            document.getElementById("notifDot").style.display = (data.success && data.unseen > 0) ? "inline" : "none";
        });
}
setInterval(checkNewRequests, 10000);
checkNewRequests();

document.getElementById("requestsLink").addEventListener("click", function(e) {
    e.preventDefault();
    fetch("mark_new_requests.php").then(() => {
        document.getElementById("notifDot").style.display = "none";
        window.location.href = "admin_requests.php";
    });
});

// Logout Modal
function openLogoutModal() { document.getElementById('logoutModal').style.display = 'flex'; }
function closeLogoutModal() { document.getElementById('logoutModal').style.display = 'none'; }
function confirmLogout() { window.location.href = 'logout.php'; }

// Delete Modal
let deleteForm = null;
function openDeleteModal(form) {
    deleteForm = form;
    const agentIdInput = form.querySelector("input[name='agent_id']");
    const requestIdInput = form.querySelector("input[name='request_id']");
    let message = "Are you sure you want to delete this item?";
    if (agentIdInput) message = "Are you sure you want to delete agent with ID: " + agentIdInput.value + "?";
    if (requestIdInput) message = "Are you sure you want to reject request ID: " + requestIdInput.value + "?";
    document.getElementById("deleteMessage").innerText = message;
    document.getElementById("deleteModal").style.display = "flex";
}
function closeDeleteModal() {
    deleteForm = null;
    document.getElementById("deleteModal").style.display = "none";
}
function confirmDelete() {
    if (deleteForm) deleteForm.submit();
    closeDeleteModal();
}
</script>
</main>
</body>
</html>
