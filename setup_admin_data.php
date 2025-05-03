<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // First, let's verify the admin user exists
    $admin_username = "18@admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_email = "18@admin.com";
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create admin user if not exists
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $admin_username, $admin_email, $admin_password);
        $stmt->execute();
        echo "Admin user created successfully<br>";
    } else {
        echo "Admin user already exists<br>";
    }
    
    // Insert sample locations if not exists
    $locations = [
        ['Kothrud', 'kothrud'],
        ['Baner', 'baner'],
        ['Viman Nagar', 'viman_nagar']
    ];
    
    foreach ($locations as $location) {
        $stmt = $conn->prepare("INSERT IGNORE INTO locations (name) VALUES (?)");
        $stmt->bind_param("s", $location[0]);
        $stmt->execute();
        echo "Location {$location[0]} added/verified<br>";
    }
    
    // Get location IDs
    $location_ids = [];
    $result = $conn->query("SELECT id, name FROM locations");
    while ($row = $result->fetch_assoc()) {
        $location_ids[$row['name']] = $row['id'];
    }
    
    // Insert sample turfs for each location
    $turfs = [
        'Kothrud' => ['Dragon Ball', 'Wolves', 'UFS'],
        'Baner' => ['Toronto Arena', 'Paris Sports', 'Eagles Sports'],
        'Viman Nagar' => ['Viman Nagar Turf', 'Sky Sports', 'Arena Sports']
    ];
    
    foreach ($turfs as $location => $turf_list) {
        $location_id = $location_ids[$location];
        foreach ($turf_list as $turf) {
            $stmt = $conn->prepare("INSERT IGNORE INTO turfs (location_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $location_id, $turf);
            $stmt->execute();
            echo "Turf {$turf} added/verified for {$location}<br>";
        }
    }
    
    // Get admin user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $admin_id = $stmt->get_result()->fetch_assoc()['id'];
    
    // Get some turf IDs for sample bookings
    $result = $conn->query("SELECT id FROM turfs LIMIT 3");
    $turf_ids = [];
    while ($row = $result->fetch_assoc()) {
        $turf_ids[] = $row['id'];
    }
    
    // Insert sample bookings
    $dates = [date('Y-m-d'), date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+2 day'))];
    $times = ['10:00:00', '14:00:00', '16:00:00'];
    
    foreach ($dates as $date) {
        foreach ($times as $time) {
            foreach ($turf_ids as $turf_id) {
                $stmt = $conn->prepare("INSERT IGNORE INTO bookings (user_id, turf_id, booking_date, booking_time) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $admin_id, $turf_id, $date, $time);
                $stmt->execute();
                echo "Sample booking added for date {$date} at {$time}<br>";
            }
        }
    }
    
    // Insert sample payments
    $result = $conn->query("SELECT id FROM bookings LIMIT 5");
    while ($booking = $result->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT IGNORE INTO payments (booking_id, amount, status) VALUES (?, 500.00, 'paid')");
        $stmt->bind_param("i", $booking['id']);
        $stmt->execute();
        echo "Sample payment added for booking {$booking['id']}<br>";
    }
    
    echo "<br>Admin data setup complete! You can now <a href='admin_dashboard.php'>access the admin dashboard</a>.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 