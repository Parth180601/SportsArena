<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // Get all users
    $result = $conn->query("SELECT id, username, email, is_admin FROM users");
    
    echo "<h2>All Users in Database:</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
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
    
    // Check if is_admin column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>Warning: 'is_admin' column is missing from users table!</p>";
    }
    
    // Show table structure
    echo "<h3>Users Table Structure:</h3>";
    $result = $conn->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
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