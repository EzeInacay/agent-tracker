<?php
session_start(); // Start session to access session variables
session_destroy(); // Destroy session to log out user
header("Location: login.html"); // Redirect to login page
exit();
?>
