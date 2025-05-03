<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    $admin_username = "18@admin";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Admin user found:<br>";
        echo "Username: " . htmlspecialchars($user['username']) . "<br>";
        echo "Email: " . htmlspecialchars($user['email']) . "<br>";
        echo "Created at: " . htmlspecialchars($user['created_at']) . "<br>";
    } else {
        echo "Admin user not found!";
    }
    
    // Also check total number of users
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $total = $result->fetch_assoc();
    echo "<br>Total users in database: " . $total['total'];
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 