<?php 
$host = "localhost";
$db = "katravel_system";
$user = "root";
$pass = "";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$password_raw = $_POST['password'];
$contact = trim($_POST['contact']);
$address = trim($_POST['address']);

// Hash password
$password_hashed = password_hash($password_raw, PASSWORD_BCRYPT);

// Check for duplicates in full_name, email, or contact_number
$check_sql = "SELECT * FROM requests WHERE full_name = ? OR email = ? OR contact_number = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("sss", $full_name, $email, $contact);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Duplicate entry detected! Name, email, or contact number already exists.'); window.history.back();</script>";
    exit();
}

// Handle file upload
$targetDir = "uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$pictureName = basename($_FILES["picture"]["name"]);
$targetFile = $targetDir . time() . "_" . $pictureName;

if (!move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {
    die("Failed to upload picture.");
}

// Insert into database
$sql = "INSERT INTO requests (full_name, email, password, contact_number, address, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $full_name, $email, $password_hashed, $contact, $address, $targetFile);

if ($stmt->execute()) {
    echo "<script>alert('Registration submitted! Please wait for admin approval.'); window.location='login.html';</script>";
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
