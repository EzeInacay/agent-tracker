<?php
// DATABASE CONNECTION
$host = 'localhost';
$user = 'root';
$password = ''; // Set your MySQL password if needed
$database = 'katravel_system';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Handle search query
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$whereClauses = [];

// Search filter
if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $whereClauses[] = "(
        b.agent_id LIKE '%$searchEscaped%' 
        OR b.client_name LIKE '%$searchEscaped%' 
        OR b.hotel_booked LIKE '%$searchEscaped%' 
        OR b.start_date LIKE '%$searchEscaped%' 
        OR b.end_date LIKE '%$searchEscaped%' 
        OR b.total_price LIKE '%$searchEscaped%' 
        OR b.ratehawk_price LIKE '%$searchEscaped%' 
        OR b.final_price LIKE '%$searchEscaped%' 
        OR bs.booking_status LIKE '%$searchEscaped%'
    )";
}

// Status filter
if (!empty($status_filter)) {
    $statusEscaped = $conn->real_escape_string($status_filter);
    $whereClauses[] = "bs.booking_status = '$statusEscaped'";
}

// Combine WHERE conditions
$searchQuery = '';
if (!empty($whereClauses)) {
    $searchQuery = "WHERE " . implode(" AND ", $whereClauses);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking History</title>
    <link rel="stylesheet" href="admin_bookings.css">
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

<!-- Booking Table -->
<div class="container">
    <h2>Booking History</h2>

<!-- Search + Filter + Download Row -->
<div class="search-download-bar">
    <form method="GET" action="" class="search-form">
        <input 
            type="text" 
            name="search" 
            placeholder="Search bookings..." 
            value="<?php echo htmlspecialchars($search); ?>"
        >
        <select name="status">
            <option value="">All Statuses</option>
            <option value="Confirmed" <?php if($status_filter == 'Confirmed') echo 'selected'; ?>>Confirmed</option>
            <option value="Completed" <?php if($status_filter == 'Completed') echo 'selected'; ?>>Completed</option>
            <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending</option>
            <option value="Cancelled" <?php if($status_filter == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
        </select>
        <button type="submit" class="search-btn">Search</button>
        <a href="admin_bookings.php" class="clear-btn">Clear</a>
    </form>

    <form action="excel_download.php" method="post" class="download-form">
        <button type="submit" class="btn-success">Download Excel File</button>
    </form>
</div>


<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Agent ID</th>
                <th>Client Name</th>
                <th>Hotel Booked</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Total Price</th>
                <th>Ratehawk Price</th>
                <th>Final Price</th>
                <th>Booking Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "
                SELECT 
                    b.agent_id,
                    b.client_name,
                    b.hotel_booked,
                    b.start_date,
                    b.end_date,
                    b.total_price,
                    b.ratehawk_price,
                    b.final_price,
                    bs.booking_status
                FROM bookings b
                LEFT JOIN booking_status bs ON b.booking_id = bs.booking_id
                $searchQuery
                ORDER BY b.booking_id DESC
            ";

            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['agent_id']}</td>
                        <td>{$row['client_name']}</td>
                        <td>{$row['hotel_booked']}</td>
                        <td>{$row['start_date']}</td>
                        <td>{$row['end_date']}</td>
                        <td>{$row['total_price']}</td>
                        <td>{$row['ratehawk_price']}</td>
                        <td>{$row['final_price']}</td>
                        <td>" . ($row['booking_status'] ?? 'N/A') . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No bookings found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

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

<script>
   
// Poll every 10 seconds
function checkNewRequests() {
    fetch("check_new_requests.php")
        .then(res => res.json())
        .then(data => {
            if (data.success && data.unseen > 0) {
                document.getElementById("notifDot").style.display = "inline";
            } else {
                document.getElementById("notifDot").style.display = "none";
            }
        });
}
setInterval(checkNewRequests, 10000);
checkNewRequests(); // initial check

// When admin opens Payout Requests, mark as seen
document.getElementById("requestsLink").addEventListener("click", function(e) {
    e.preventDefault();
    fetch("mark_new_requests.php")
        .then(() => {
            document.getElementById("notifDot").style.display = "none";
            window.location.href = "admin_requests.php";
        });
});


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

</body>
</html>