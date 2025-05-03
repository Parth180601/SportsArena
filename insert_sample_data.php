<?php
require_once 'dbconnect.php';

try {
    // Check if we have any locations
    $result = $conn->query("SELECT COUNT(*) as count FROM locations");
    $count = $result->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Insert sample locations
        $locations = ['Arena Sports', 'Sports Complex', 'City Stadium'];
        foreach ($locations as $location) {
            $stmt = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
            $stmt->bind_param("s", $location);
            $stmt->execute();
        }
        
        // Insert sample turfs for each location
        $result = $conn->query("SELECT id FROM locations");
        while ($row = $result->fetch_assoc()) {
            $location_id = $row['id'];
            for ($i = 1; $i <= 3; $i++) {
                $turf_name = "Turf " . $i;
                $stmt = $conn->prepare("INSERT INTO turfs (location_id, name) VALUES (?, ?)");
                $stmt->bind_param("is", $location_id, $turf_name);
                $stmt->execute();
            }
        }
        
        // Insert a sample admin user if not exists
        $username = "Parth@admin";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $email = "parth@admin.com";
        
        $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $email);
        $stmt->execute();
        
        // Get the admin user ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user_id = $stmt->get_result()->fetch_assoc()['id'];
        
        // Insert some sample bookings
        $turf_result = $conn->query("SELECT id FROM turfs LIMIT 3");
        while ($turf = $turf_result->fetch_assoc()) {
            $turf_id = $turf['id'];
            $date = date('Y-m-d');
            $time = '10:00:00';
            
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, turf_id, booking_date, booking_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $turf_id, $date, $time);
            $stmt->execute();
            
            // Insert corresponding payment
            $booking_id = $conn->insert_id;
            $amount = 100.00;
            $status = 'paid';
            
            $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, status) VALUES (?, ?, ?)");
            $stmt->bind_param("ids", $booking_id, $amount, $status);
            $stmt->execute();
        }
        
        echo "Sample data inserted successfully!";
    } else {
        echo "Data already exists in the database.";
    }
    
} catch (Exception $e) {
    echo "Error inserting sample data: " . $e->getMessage();
}
?> 