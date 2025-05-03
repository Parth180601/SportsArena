<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Status Check</h2>";

// Database connection with correct password
$conn = new mysqli('localhost', 'root', 'password');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✓ Connected to MySQL server<br>";

// Show all databases
echo "<h3>All Databases:</h3>";
$result = $conn->query("SHOW DATABASES");
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Check if login_system exists
$result = $conn->query("SHOW DATABASES LIKE 'login_system'");
if ($result->num_rows > 0) {
    echo "✓ login_system database exists<br>";
    
    // Select database
    if ($conn->select_db('login_system')) {
        echo "✓ Selected login_system database<br>";
        
        // Show all tables
        echo "<h3>Tables in login_system:</h3>";
        $result = $conn->query("SHOW TABLES");
        if ($result->num_rows > 0) {
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                $table = $row[0];
                echo "<li>" . $table;
                
                // Show table status
                $status = $conn->query("SHOW TABLE STATUS LIKE '$table'");
                if ($status && $row = $status->fetch_assoc()) {
                    echo " (Engine: " . $row['Engine'] . ", Rows: " . $row['Rows'] . ")";
                }
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "No tables found in login_system database<br>";
        }
    } else {
        echo "✗ Could not select login_system database<br>";
    }
} else {
    echo "✗ login_system database does not exist<br>";
}

// Show MySQL version and configuration
echo "<h3>MySQL Information:</h3>";
$result = $conn->query("SELECT VERSION()");
$version = $result->fetch_array()[0];
echo "MySQL Version: " . $version . "<br>";

$result = $conn->query("SHOW VARIABLES LIKE 'character_set_database'");
$charset = $result->fetch_assoc();
echo "Database Character Set: " . $charset['Value'] . "<br>";

// Show current user and permissions
echo "<h3>Current User Permissions:</h3>";
$result = $conn->query("SELECT CURRENT_USER()");
$user = $result->fetch_array()[0];
echo "Current User: " . $user . "<br>";

$result = $conn->query("SHOW GRANTS FOR CURRENT_USER");
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

$conn->close();

echo "<h3>Next Steps:</h3>";
echo "1. Open XAMPP Control Panel<br>";
echo "2. Click 'Admin' next to MySQL to open phpMyAdmin<br>";
echo "3. In phpMyAdmin:<br>";
echo "   - Look for 'login_system' in the left sidebar<br>";
echo "   - If it doesn't exist, create it manually<br>";
echo "   - Create the following tables:<br>";
echo "     a. users (id, username, email, password, is_admin, created_at)<br>";
echo "     b. bookings (id, user_id, location, turf, booking_date, booking_time, created_at)<br>";
echo "     c. payments (id, booking_id, payment_method, payment_status, amount, payment_date)<br>";
?> 