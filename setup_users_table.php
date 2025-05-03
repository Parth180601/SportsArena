<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // First, disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop and recreate the users table
    $conn->query("DROP TABLE IF EXISTS users");
    
    // Create users table with proper structure
    $sql = "CREATE TABLE users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
        email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
        password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "Users table created successfully<br>";
        
        // Create admin user
        $admin_username = "18@admin";
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        $admin_email = "18@admin.com";
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $admin_username, $admin_email, $admin_password);
        
        if ($stmt->execute()) {
            echo "Admin user created successfully<br>";
        }
        
        // Create test user
        $test_username = "testuser";
        $test_password = password_hash("test123", PASSWORD_DEFAULT);
        $test_email = "test@example.com";
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $test_username, $test_email, $test_password);
        
        if ($stmt->execute()) {
            echo "Test user created successfully<br>";
        }
        
        // Show current users
        $result = $conn->query("SELECT id, username, email, is_admin FROM users");
        echo "<h3>Current Users:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . ($row['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><strong>You can now try to:</strong><br>";
        echo "1. Register a new user account<br>";
        echo "2. Login with admin account (18@admin / admin123)<br>";
        echo "3. Login with test account (testuser / test123)<br>";
    } else {
        echo "Error creating table: " . $conn->error;
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    // Make sure to re-enable foreign key checks even if there's an error
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}
?> 