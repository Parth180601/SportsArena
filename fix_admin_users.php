<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // Check if is_admin column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($result->num_rows == 0) {
        // Add is_admin column
        $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo "Added is_admin column<br>";
    }
    
    // Update the admin user
    $admin_username = "18@admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing admin
        $stmt = $conn->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE username = ?");
        $stmt->bind_param("ss", $admin_password, $admin_username);
        $stmt->execute();
        echo "Updated admin user<br>";
    } else {
        // Create new admin
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $admin_username, $admin_password, $admin_username);
        $stmt->execute();
        echo "Created new admin user<br>";
    }
    
    // Show current admin users
    $result = $conn->query("SELECT id, username, email, is_admin FROM users WHERE is_admin = 1");
    echo "<h3>Current Admin Users:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>Yes</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>Admin credentials:<br>";
    echo "Username: 18@admin<br>";
    echo "Password: admin123";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 