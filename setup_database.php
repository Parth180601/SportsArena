<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost:4306';
$username = 'root';
$password = '';

// Create connection without database
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS login_system";
if ($conn->query($sql)) {
    echo "Database 'login_system' created successfully or already exists<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db('login_system');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create turfs table
$sql = "DROP TABLE IF EXISTS turfs";
if ($conn->query($sql)) {
    echo "Old turfs table dropped successfully<br>";
}

$sql = "CREATE TABLE turfs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(50) NOT NULL,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Turfs table created successfully<br>";
} else {
    die("Error creating turfs table: " . $conn->error . "<br>");
}

// Create bookings table
$sql = "DROP TABLE IF EXISTS bookings";
if ($conn->query($sql)) {
    echo "Old bookings table dropped successfully<br>";
}

$sql = "CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    turf_id INT,
    location VARCHAR(50) NOT NULL,
    turf VARCHAR(50) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    payment_status ENUM('paid', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (turf_id) REFERENCES turfs(id)
)";

if ($conn->query($sql)) {
    echo "Bookings table created successfully<br>";
} else {
    die("Error creating bookings table: " . $conn->error . "<br>");
}

// Insert default turfs
$default_turfs = [
    ['Dragon Ball', 'kothrud'],
    ['Wolves', 'kothrud'],
    ['UFS', 'kothrud'],
    ['Toronto Arena', 'baner'],
    ['Paris Sports', 'baner'],
    ['Eagles Sports', 'baner'],
    ['Viman Nagar Turf', 'viman_nagar'],
    ['Sky Sports', 'viman_nagar'],
    ['Arena Sports', 'viman_nagar']
];

$stmt = $conn->prepare("INSERT INTO turfs (name, location) VALUES (?, ?)");
foreach ($default_turfs as $turf) {
    $stmt->bind_param("ss", $turf[0], $turf[1]);
    if ($stmt->execute()) {
        echo "Added turf: " . $turf[0] . " at " . $turf[1] . "<br>";
    } else {
        echo "Error adding turf: " . $stmt->error . "<br>";
    }
}
$stmt->close();

// Create admin user if not exists
$admin_username = "Parth@18admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Default password: admin123

$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, is_admin) VALUES (?, ?, 1)");
$stmt->bind_param("ss", $admin_username, $admin_password);
if ($stmt->execute()) {
    echo "<br>Admin user created/verified successfully<br>";
    echo "Admin username: " . $admin_username . "<br>";
    echo "Admin password: admin123<br>";
} else {
    echo "Error creating admin user: " . $stmt->error . "<br>";
}
$stmt->close();

// Update admin_dashboard.php
$dashboard_content = '<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

require_once "dbconnect.php";

function getBookingStats($conn) {
    $stats = [];
    
    // Total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $stats["total_bookings"] = $result->fetch_assoc()["total"];
    
    // Get turf counts by location
    $result = $conn->query("SELECT location, COUNT(*) as count FROM turfs GROUP BY location");
    $stats["turfs_by_location"] = [];
    $stats["total_turfs"] = 0;
    while ($row = $result->fetch_assoc()) {
        $stats["turfs_by_location"][$row["location"]] = $row["count"];
        $stats["total_turfs"] += $row["count"];
    }

    // Calculate occupancy rate
    $total_slots = $stats["total_turfs"] * 24;
    $result = $conn->query("SELECT COUNT(*) as booked_slots FROM bookings WHERE DATE(booking_date) = CURDATE()");
    $booked_slots = $result->fetch_assoc()["booked_slots"];
    $stats["occupancy_rate"] = ($total_slots > 0) ? round(($booked_slots / $total_slots) * 100, 1) : 0;

    // Get recent bookings
    $result = $conn->query("SELECT b.*, u.username, t.name as turf_name 
                           FROM bookings b 
                           LEFT JOIN users u ON b.user_id = u.id 
                           LEFT JOIN turfs t ON b.turf_id = t.id
                           ORDER BY b.booking_date DESC, b.booking_time DESC 
                           LIMIT 10");
    $stats["recent_bookings"] = [];
    while ($row = $result->fetch_assoc()) {
        $stats["recent_bookings"][] = $row;
    }
    
    return $stats;
}

$booking_stats = getBookingStats($conn);
?>';

file_put_contents('admin_dashboard_functions.php', $dashboard_content);
echo "<br>Admin dashboard functions file created successfully<br>";

echo "<br>Setup complete! Please follow these steps:<br>";
echo "1. Go to login.php<br>";
echo "2. Login with:<br>";
echo "   Username: " . $admin_username . "<br>";
echo "   Password: admin123<br>";
echo "3. You should now be able to access the admin dashboard<br>";

$conn->close();
?> 