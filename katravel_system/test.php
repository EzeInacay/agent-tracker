<?php
$conn = new mysqli("localhost", "root", "", "katravel_system");

if ($conn->connect_error) {
    die("❌ MySQL connection failed: " . $conn->connect_error);
} else {
    echo "✅ MySQL server is running!";
}
?>
