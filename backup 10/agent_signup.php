<?php
$host = "localhost";
$db = "katravel_system";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$full_name = $_POST['full_name'];
$email = $_POST['email'];
$password_raw = $_POST['password'];
$contact = $_POST['contact'];
$address = $_POST['address'];

$password = password_hash($password_raw, PASSWORD_BCRYPT);

$targetDir = "uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$pictureName = basename($_FILES["picture"]["name"]);
$targetFile = $targetDir . time() . "_" . $pictureName;

if (!move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {
    die("Failed to upload picture.");
}

$sql = "INSERT INTO requests (full_name, email, password, contact_number, address, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $full_name, $email, $password, $contact, $address, $targetFile);

if ($stmt->execute()) {
    echo "<script>alert('Registration submitted! Please wait for admin approval.'); window.location='login.html';</script>";
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
