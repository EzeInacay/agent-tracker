<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    echo "Not authorized";
    exit();
}

if (isset($_POST['rate'])) {
    $rate = floatval($_POST['rate']);
    if ($rate < 0 || $rate > 100) {
        echo "Invalid commission rate";
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET commission_rate = ?");
    $stmt->bind_param("d", $rate);
    if ($stmt->execute()) {
        echo "Commission rate updated for all agents successfully.";
    } else {
        echo "Failed to update commission rates.";
    }
    $stmt->close();
} else {
    echo "No rate provided";
}
?>
