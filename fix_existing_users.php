<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // First, let's check and fix the table structure without dropping it
    $conn->query("
        ALTER TABLE users 
        MODIFY username VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        MODIFY email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        MODIFY password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
    ");
    
    // Add is_admin column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo "Added is_admin column<br>";
    }
    
    // Get all existing users
    $result = $conn->query("SELECT * FROM users");
    echo "<h3>Existing Users:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Status</th><th>Is Admin</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        
        // Check if password needs rehashing
        $needs_hash = true;
        if (strlen($row['password']) > 40 && strpos($row['password'], '$2y$') === 0) {
            $needs_hash = false;
        }
        
        if ($needs_hash) {
            // Hash the existing password
            $hashed_password = password_hash($row['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $row['id']);
            $stmt->execute();
            echo "<td>Password hashed</td>";
        } else {
            echo "<td>Password already hashed</td>";
        }
        
        // Set admin status for users with @admin in their username
        $is_admin = (strpos($row['username'], '@admin') !== false) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_admin, $row['id']);
        $stmt->execute();
        
        echo "<td>" . ($is_admin ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><strong>All users have been updated. You can now:</strong><br>";
    echo "1. Try logging in with your existing account<br>";
    echo "2. If login fails, try registering a new account<br>";
    echo "3. For admin users, make sure to use the exact username and password<br>";
    
    // Show the final user table structure
    echo "<h3>Users Table Structure:</h3>";
    $result = $conn->query("DESCRIBE users");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 