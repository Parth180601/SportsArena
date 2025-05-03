<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    $admin_username = "18@admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $admin_password, $admin_username);
    
    if ($stmt->execute()) {
        echo "Admin password updated successfully!<br>";
        echo "Username: 18@admin<br>";
        echo "Password: admin123";
    } else {
        echo "Failed to update admin password.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$stmt->close();
$conn->close();
?> 