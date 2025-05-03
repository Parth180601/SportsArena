<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create turfs table
$sql = "CREATE TABLE IF NOT EXISTS turfs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(50) NOT NULL,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Turfs table created successfully<br>";
} else {
    echo "Error creating turfs table: " . $conn->error . "<br>";
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

$stmt = $conn->prepare("INSERT IGNORE INTO turfs (name, location) VALUES (?, ?)");
foreach ($default_turfs as $turf) {
    $stmt->bind_param("ss", $turf[0], $turf[1]);
    if ($stmt->execute()) {
        echo "Added turf: " . $turf[0] . " at " . $turf[1] . "<br>";
    } else {
        echo "Error adding turf: " . $stmt->error . "<br>";
    }
}
$stmt->close();

// Modify the bookings table to include turf_id if it doesn't exist
$sql = "SHOW COLUMNS FROM bookings LIKE 'turf_id'";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE bookings ADD COLUMN turf_id INT AFTER user_id,
            ADD FOREIGN KEY (turf_id) REFERENCES turfs(id)";
    if ($conn->query($sql)) {
        echo "Added turf_id column to bookings table<br>";
    } else {
        echo "Error adding turf_id column: " . $conn->error . "<br>";
    }
}

// Update admin_dashboard.php getBookingStats function
echo "<br>Now updating admin_dashboard.php...<br>";

// Read current admin_dashboard.php
$dashboard_file = file_get_contents('admin_dashboard.php');

// Update the getBookingStats function
$new_function = '
function getBookingStats($conn) {
    $stats = [];
    
    // Total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $stats[\'total_bookings\'] = $result->fetch_assoc()[\'total\'];
    
    // Get turf counts by location
    $result = $conn->query("SELECT t.location, COUNT(*) as count 
                           FROM turfs t 
                           GROUP BY t.location");
    $stats[\'turfs_by_location\'] = [];
    $stats[\'total_turfs\'] = 0;
    while ($row = $result->fetch_assoc()) {
        $stats[\'turfs_by_location\'][$row[\'location\']] = $row[\'count\'];
        $stats[\'total_turfs\'] += $row[\'count\'];
    }

    // Calculate occupancy rate
    $total_slots = $stats[\'total_turfs\'] * 24; // Assuming 24 slots per turf per day
    $result = $conn->query("SELECT COUNT(*) as booked_slots FROM bookings WHERE DATE(booking_date) = CURDATE()");
    $booked_slots = $result->fetch_assoc()[\'booked_slots\'];
    $stats[\'occupancy_rate\'] = ($total_slots > 0) ? round(($booked_slots / $total_slots) * 100, 1) : 0;
    $stats[\'available_slots\'] = $total_slots - $booked_slots;

    // Get recent bookings with payment info
    $result = $conn->query("SELECT b.*, u.username, t.name as turf_name 
                           FROM bookings b 
                           LEFT JOIN users u ON b.user_id = u.id 
                           LEFT JOIN turfs t ON b.turf_id = t.id
                           ORDER BY b.booking_date DESC, b.booking_time DESC 
                           LIMIT 10");
    $stats[\'recent_bookings\'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats[\'recent_bookings\'][] = $row;
    }
    
    return $stats;
}';

// Replace the old function with the new one
$pattern = '/function getBookingStats\([^\{]+\{.*?\}/s';
$dashboard_file = preg_replace($pattern, $new_function, $dashboard_file);

// Save the updated file
file_put_contents('admin_dashboard.php', $dashboard_file);
echo "admin_dashboard.php has been updated<br>";

echo "<br>Setup complete! You can now refresh your admin dashboard.";
?> 