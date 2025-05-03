<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('localhost', 'root', '');
$conn->select_db('login_system');

echo "<h2>Database Setup Verification</h2>";

// Verify tables
$tables = ['users', 'bookings', 'payments'];
echo "<h3>Tables Check:</h3>";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<div style='color: green;'>✓ Table '$table' exists</div>";
    } else {
        echo "<div style='color: red;'>✗ Table '$table' is missing</div>";
    }
}

// Verify admin user
echo "<h3>Admin User Check:</h3>";
$result = $conn->query("SELECT * FROM users WHERE username = 'admin@18' AND is_admin = TRUE");
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<div style='color: green;'>✓ Admin user exists</div>";
    echo "Username: " . $admin['username'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Created at: " . $admin['created_at'] . "<br>";
} else {
    echo "<div style='color: red;'>✗ Admin user not found</div>";
}

// Test login
echo "<h3>Login Test:</h3>";
$username = 'admin@18';
$password = 'admin@18';
$result = $conn->query("SELECT * FROM users WHERE username = '$username'");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        echo "<div style='color: green;'>✓ Login credentials are valid</div>";
        echo "You can now <a href='login.php'>login</a> with:<br>";
        echo "Username: admin@18<br>";
        echo "Password: admin@18<br>";
    } else {
        echo "<div style='color: red;'>✗ Password verification failed</div>";
    }
} else {
    echo "<div style='color: red;'>✗ Username not found</div>";
}

$conn->close();
?> 