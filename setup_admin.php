<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'dbconnect.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful<br>";

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE
)";
if ($conn->query($sql) === TRUE) {
    echo "Users table created/verified<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    location VARCHAR(50) NOT NULL,
    turf VARCHAR(50) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Bookings table created/verified<br>";
} else {
    echo "Error creating bookings table: " . $conn->error . "<br>";
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
    echo "Payments table created/verified<br>";
} else {
    echo "Error creating payments table: " . $conn->error . "<br>";
}

// Create admin user
$admin_username = "admin@18";
$admin_password = password_hash("admin@18", PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, is_admin) 
        VALUES (?, ?, TRUE) 
        ON DUPLICATE KEY UPDATE password = ?, is_admin = TRUE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $admin_username, $admin_password, $admin_password);
if ($stmt->execute()) {
    echo "Admin user created/updated<br>";
} else {
    echo "Error creating admin user: " . $stmt->error . "<br>";
}
$stmt->close();

// Verify tables exist
$tables = ['users', 'bookings', 'payments'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "Table '$table' exists<br>";
    } else {
        echo "Table '$table' does NOT exist<br>";
    }
}

echo "<br>Setup complete! You can now <a href='login.php'>login</a> with admin credentials:<br>";
echo "Username: admin@18<br>";
echo "Password: admin@18";
?> 