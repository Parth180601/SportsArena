<?php
require_once 'dbconnect.php';

// Add is_admin column to users table
$sql = "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE";
if ($conn->query($sql) === TRUE) {
    echo "Added is_admin column to users table<br>";
} else {
    echo "Error adding is_admin column: " . $conn->error . "<br>";
}

// Create payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_method ENUM('cash', 'online', 'card') NOT NULL,
    payment_status ENUM('paid', 'pending', 'cancelled') NOT NULL DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Created payments table<br>";
} else {
    echo "Error creating payments table: " . $conn->error . "<br>";
}

// Add location and turf columns to bookings table if they don't exist
$sql = "ALTER TABLE bookings 
        ADD COLUMN IF NOT EXISTS location VARCHAR(50) NOT NULL,
        ADD COLUMN IF NOT EXISTS turf VARCHAR(50) NOT NULL";
if ($conn->query($sql) === TRUE) {
    echo "Added location and turf columns to bookings table<br>";
} else {
    echo "Error adding columns to bookings table: " . $conn->error . "<br>";
}

// Create an admin user (password: admin123)
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, is_admin) 
        VALUES (?, ?, TRUE) 
        ON DUPLICATE KEY UPDATE is_admin = TRUE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $admin_username, $admin_password);
if ($stmt->execute()) {
    echo "Created admin user<br>";
} else {
    echo "Error creating admin user: " . $stmt->error . "<br>";
}
$stmt->close();

echo "Database update complete!";
?> 