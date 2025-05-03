<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // Drop tables if they exist (to ensure clean creation)
    $conn->query("DROP TABLE IF EXISTS bookings");
    $conn->query("DROP TABLE IF EXISTS users");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        google_id VARCHAR(100) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "Table 'users' created successfully<br>";
    } else {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    
    // Create bookings table
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        guests INT(11) NOT NULL,
        special_requests TEXT,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "Table 'bookings' created successfully<br>";
    } else {
        throw new Exception("Error creating bookings table: " . $conn->error);
    }
    
    echo "All tables created successfully!";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 