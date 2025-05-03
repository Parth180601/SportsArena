<?php
// Set proper HTTP headers
header('Content-Type: text/html; charset=utf-8');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection parameters
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "login_system";
$port = getenv('DB_PORT') ?: 3306;

// Log connection parameters (without password)
error_log("Attempting database connection with: host=$servername, user=$username, db=$dbname, port=$port");

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    error_log("Database connection successful");
    
    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Setting charset failed: " . $conn->error);
    }
    
    error_log("Character set set to utf8mb4");
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    error_log("Database created/verified");
    
    // Select the database
    if (!$conn->select_db($dbname)) {
        throw new Exception("Error selecting database: " . $conn->error);
    }
    
    error_log("Database selected successfully");
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
        password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    
    error_log("Users table created/verified");
    
    // Verify table structure
    $result = $conn->query("SHOW COLUMNS FROM users");
    error_log("Users table structure:");
    while ($column = $result->fetch_assoc()) {
        error_log("Column: " . $column['Field'] . " Type: " . $column['Type']);
    }
    
    // Create locations table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS locations (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating locations table: " . $conn->error);
    }

    // Create turfs table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS turfs (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        location_id INT(11) NOT NULL,
        name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (location_id) REFERENCES locations(id),
        UNIQUE KEY unique_turf (location_id, name)
    )";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating turfs table: " . $conn->error);
    }

    // Create bookings table with correct structure
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        turf_id INT(11) NOT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (turf_id) REFERENCES turfs(id),
        UNIQUE KEY unique_booking (booking_date, booking_time, turf_id)
    )";
    
    if (!$conn->query($sql)) {
        // If the ALTER fails, it might be because the columns don't exist yet
        // So we'll try to create the table with the new structure
        $sql = "CREATE TABLE IF NOT EXISTS bookings (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            turf_id INT(11) NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (turf_id) REFERENCES turfs(id),
            UNIQUE KEY unique_booking (booking_date, booking_time, turf_id)
        )";
        
        if (!$conn->query($sql)) {
            throw new Exception("Error creating/updating bookings table: " . $conn->error);
        }
    }
    
    // Create payments table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        booking_id INT(11) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'pending',
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
    )";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating payments table: " . $conn->error);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Database Error: ' . $e->getMessage());
    
    // Send proper HTTP response
    http_response_code(500);
    die('Database Error: ' . htmlspecialchars($e->getMessage()));
}
?>