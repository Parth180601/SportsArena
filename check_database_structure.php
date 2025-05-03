<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Make sure we're using the login_system database
$conn->select_db('login_system');

echo "<h2>Database Structure Check</h2>";

// Check if bookings table exists
$result = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($result->num_rows > 0) {
    echo "✓ Bookings table exists<br>";
    
    // Show table structure
    echo "<h3>Bookings Table Structure:</h3>";
    $result = $conn->query("DESCRIBE bookings");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Show current bookings
    echo "<h3>Current Bookings:</h3>";
    $result = $conn->query("SELECT * FROM bookings ORDER BY booking_date, booking_time");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Location</th><th>Turf</th><th>Date</th><th>Time</th><th>Created At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['location']) . "</td>";
            echo "<td>" . htmlspecialchars($row['turf']) . "</td>";
            echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['booking_time']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No bookings found in the database.";
    }
} else {
    echo "✗ Bookings table does not exist!<br>";
    
    // Create the bookings table
    echo "<br>Creating bookings table...<br>";
    $sql = "CREATE TABLE bookings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        location VARCHAR(50) NOT NULL,
        turf VARCHAR(50) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "✓ Bookings table created successfully!";
    } else {
        echo "✗ Error creating bookings table: " . $conn->error;
    }
}

$conn->close();
?> 