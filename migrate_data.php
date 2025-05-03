<?php
require_once 'dbconnect.php';

try {
    // First, get all unique locations and turfs from the old bookings table
    $result = $conn->query("SELECT DISTINCT location, turf FROM bookings");
    
    // Create locations and turfs
    while ($row = $result->fetch_assoc()) {
        // Insert location if it doesn't exist
        $stmt = $conn->prepare("INSERT IGNORE INTO locations (name) VALUES (?)");
        $stmt->bind_param("s", $row['location']);
        $stmt->execute();
        
        // Get the location ID
        $location_id = $conn->insert_id;
        if ($location_id == 0) {
            // If the location already existed, get its ID
            $stmt = $conn->prepare("SELECT id FROM locations WHERE name = ?");
            $stmt->bind_param("s", $row['location']);
            $stmt->execute();
            $location_id = $stmt->get_result()->fetch_assoc()['id'];
        }
        
        // Insert turf if it doesn't exist
        $stmt = $conn->prepare("INSERT IGNORE INTO turfs (location_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $location_id, $row['turf']);
        $stmt->execute();
    }
    
    // Now update the bookings table with the new turf_id
    $result = $conn->query("SELECT b.*, t.id as turf_id 
                           FROM bookings b 
                           JOIN locations l ON b.location = l.name 
                           JOIN turfs t ON l.id = t.location_id AND b.turf = t.name");
    
    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE bookings SET turf_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $row['turf_id'], $row['id']);
        $stmt->execute();
    }
    
    echo "Migration completed successfully!";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?> 