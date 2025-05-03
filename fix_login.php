<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // First, let's modify the users table to ensure proper structure
    $conn->query("
        ALTER TABLE users 
        MODIFY username VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        MODIFY email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        MODIFY password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    
    echo "Updated table structure for proper character handling<br>";
    
    // Add a test user if not exists
    $test_username = "testuser";
    $test_password = password_hash("test123", PASSWORD_DEFAULT);
    $test_email = "test@example.com";
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $test_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $test_username, $test_password, $test_email);
        $stmt->execute();
        echo "Created test user account<br>";
    }
    
    echo "<h3>Test Account Credentials:</h3>";
    echo "Username: testuser<br>";
    echo "Password: test123<br><br>";
    
    echo "<h3>Login Instructions:</h3>";
    echo "<ol>";
    echo "<li>Make sure you're using the exact username (case sensitive)</li>";
    echo "<li>Clear your browser cache and cookies</li>";
    echo "<li>Try the test account above to verify the login system</li>";
    echo "<li>If the test account works but your account doesn't:</li>";
    echo "<ul>";
    echo "<li>Your password might need to be reset</li>";
    echo "<li>Try registering a new account</li>";
    echo "</ul>";
    echo "</ol>";
    
    // Show current users for verification
    $result = $conn->query("SELECT id, username, email FROM users ORDER BY id");
    echo "<h3>Current Users in Database:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 