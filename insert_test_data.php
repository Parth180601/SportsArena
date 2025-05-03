<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    echo "<h2>Database Test Data Insertion</h2>";
    
    // 1. First insert admin user if not exists
    $admin_username = "18@admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_email = "18@admin.com";
    
    echo "<h3>1. Adding Admin User</h3>";
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $admin_username, $admin_password, $admin_email);
        $stmt->execute();
        echo "Admin user created successfully.<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    
    // 2. Insert locations if not exists
    echo "<h3>2. Adding Locations</h3>";
    $locations = ['Arena Sports', 'Sports Complex', 'City Stadium'];
    foreach ($locations as $location) {
        $stmt = $conn->prepare("INSERT IGNORE INTO locations (name) VALUES (?)");
        $stmt->bind_param("s", $location);
        $stmt->execute();
        echo "Added/Verified location: $location<br>";
    }
    
    // 3. Insert turfs for each location
    echo "<h3>3. Adding Turfs</h3>";
    $result = $conn->query("SELECT id, name FROM locations");
    while ($location = $result->fetch_assoc()) {
        for ($i = 1; $i <= 3; $i++) {
            $turf_name = "Turf " . $i;
            $stmt = $conn->prepare("INSERT IGNORE INTO turfs (location_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $location['id'], $turf_name);
            $stmt->execute();
            echo "Added/Verified turf: {$turf_name} for location: {$location['name']}<br>";
        }
    }
    
    // 4. Insert sample bookings
    echo "<h3>4. Adding Sample Bookings</h3>";
    // Get admin user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $user_id = $stmt->get_result()->fetch_assoc()['id'];
    
    // Get all turf IDs
    $result = $conn->query("SELECT id FROM turfs");
    $turf_ids = [];
    while ($row = $result->fetch_assoc()) {
        $turf_ids[] = $row['id'];
    }
    
    // Add bookings for today and tomorrow
    $dates = [date('Y-m-d'), date('Y-m-d', strtotime('+1 day'))];
    $times = ['09:00:00', '10:00:00', '11:00:00', '14:00:00', '15:00:00'];
    
    foreach ($dates as $date) {
        foreach ($times as $time) {
            foreach ($turf_ids as $turf_id) {
                $stmt = $conn->prepare("INSERT IGNORE INTO bookings (user_id, turf_id, booking_date, booking_time) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $user_id, $turf_id, $date, $time);
                if ($stmt->execute()) {
                    $booking_id = $stmt->insert_id;
                    if ($booking_id > 0) {
                        // Add payment for this booking
                        $amount = 100.00;
                        $status = 'paid';
                        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, status) VALUES (?, ?, ?)");
                        $stmt->bind_param("ids", $booking_id, $amount, $status);
                        $stmt->execute();
                        echo "Added booking for date: $date, time: $time, turf_id: $turf_id with payment<br>";
                    }
                }
            }
        }
    }
    
    // 5. Display current data counts
    echo "<h3>5. Current Data Status:</h3>";
    $tables = ['users', 'locations', 'turfs', 'bookings', 'payments'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $result->fetch_assoc()['count'];
        echo "$table: $count records<br>";
    }
    
    echo "<br><strong>Data insertion completed successfully!</strong>";
    echo "<br><br><a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
    
} catch (Exception $e) {
    echo "<br>Error: " . $e->getMessage();
}
?> 