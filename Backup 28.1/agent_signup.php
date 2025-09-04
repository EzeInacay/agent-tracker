<?php 
require_once "db_connect.php";

$duplicateError = false;
$successSubmit  = false;

// Only process when form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get POST data
    $full_name    = trim($_POST['full_name']);
    $email        = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $contact      = trim($_POST['contact']);
    $address      = trim($_POST['address']);

    // Hash password
    $password_hashed = password_hash($password_raw, PASSWORD_BCRYPT);

    // Check for duplicates
    $check_sql = "SELECT * FROM requests WHERE full_name = ? OR email = ? OR contact_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sss", $full_name, $email, $contact);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $duplicateError = true;
    } else {
        // Handle file upload
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $pictureName = basename($_FILES["picture"]["name"]);
        $targetFile  = $targetDir . time() . "_" . $pictureName;

        if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {
            // Insert into database
            $sql = "INSERT INTO requests (full_name, email, password, contact_number, address, profile_pic)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $full_name, $email, $password_hashed, $contact, $address, $targetFile);

            if ($stmt->execute()) {
                $successSubmit = true;
            }
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Agent Signup</title>
  <link rel="stylesheet" href="agent_signup.css">

  <script>
    function validateForm() {
      const pw1 = document.getElementById("password").value;
      const pw2 = document.getElementById("retype_password").value;
      if (pw1 !== pw2) {
        alert("Passwords do not match!");
        return false;
      }
      openSubmitModal();
      return false;
    }

    // Cancel Modal
    function openCancelModal() {
      document.getElementById("cancelModal").style.display = "flex";
    }
    function closeCancelModal() {
      document.getElementById("cancelModal").style.display = "none";
    }
    function confirmCancel() {
      window.location.href = "login.php";
    }

    // Submit Modal
    function openSubmitModal() {
      document.getElementById("submitModal").style.display = "flex";
    }
    function closeSubmitModal() {
      document.getElementById("submitModal").style.display = "none";
    }
    function confirmSubmit() {
      document.getElementById("signupForm").submit();
    }
  </script>
</head>
<body>

<div class="container">
  <h2>New Agent Signup</h2>
  <form id="signupForm" action="agent_signup.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
    <label>Full Name:</label>
    <input type="text" name="full_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Password:</label>
    <input type="password" name="password" id="password" required>

    <label>Retype Password:</label>
    <input type="password" id="retype_password" required>

    <label>Contact Number:</label>
    <input type="text" name="contact" required>

    <label>Address:</label>
    <textarea name="address" rows="3" required></textarea>

    <label>Upload 2x2 Picture:</label>
    <input type="file" name="picture" accept="image/*" required>

    <button type="submit" class="submit-btn">Submit Registration</button>
  </form>
  <button onclick="openCancelModal()" class="cancel-btn">Cancel Registration</button>
</div>

<!-- Cancel Modal -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal-content">
    <h4>Cancel Registration</h4>
    <p>Are you sure you want to cancel your registration?</p>
    <div class="modal-buttons">
      <button onclick="confirmCancel()" class="btn btn-danger">Yes, Cancel</button>
      <button onclick="closeCancelModal()" class="btn btn-secondary">No, Stay</button>
    </div>
  </div>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="submitModal">
  <div class="modal-content">
    <h4>Confirm Submission</h4>
    <p>Do you want to submit your registration details?</p>
    <div class="modal-buttons">
      <button onclick="confirmSubmit()" class="btn btn-success">Yes, Submit</button>
      <button onclick="closeSubmitModal()" class="btn btn-secondary">Review Again</button>
    </div>
  </div>
</div>

<!-- Duplicate Entry Modal -->
<div class="modal-overlay" id="duplicateModal"
     style="<?= $duplicateError ? 'display:flex;' : 'display:none;' ?>">
  <div class="modal-content">
    <h4>Duplicate Entry Detected!</h4>
    <p>Name, email, or contact number already exists.</p>
    <div class="modal-buttons">
      <a href="agent_signup.php" class="btn btn-duplicate">OK</a>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal-overlay" id="successModal"
     style="<?= $successSubmit ? 'display:flex;' : 'display:none;' ?>">
  <div class="modal-content">
    <h4>Registration Submitted!</h4>
    <p>Please wait for admin approval.</p>
    <div class="modal-buttons">
      <a href="login.php" class="btn btn-submitted">OK</a>
    </div>
  </div>
</div>



</body>
</html>
