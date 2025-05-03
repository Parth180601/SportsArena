<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    echo "<h2>User Account Status</h2>";
    
    // First, let's check all users in the database
    $result = $conn->query("SELECT id, username, email, password FROM users");
    
    echo "<h3>Current Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Password Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        
        // Check if password is properly hashed
        $is_hashed = (strlen($row['password']) > 40 && strpos($row['password'], '$2y$') === 0);
        echo "<td>" . ($is_hashed ? 'Properly Hashed' : 'Needs Update') . "</td>";
        echo "</tr>";
        
        // If password is not properly hashed, update it
        if (!$is_hashed) {
            $new_password = password_hash($row['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $row['id']);
            $stmt->execute();
            echo "<tr><td colspan='4' style='color: green;'>Updated password hash for user: " . htmlspecialchars($row['username']) . "</td></tr>";
        }
    }
    echo "</table>";
    
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
    
    echo "<p>If you're having trouble logging in, please try these steps:</p>";
    echo "<ol>";
    echo "<li>Make sure you're using the exact username (case sensitive)</li>";
    echo "<li>If you still can't log in, try registering again</li>";
    echo "<li>Contact admin if problems persist</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 