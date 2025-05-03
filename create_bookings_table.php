<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Make sure we're using the login_system database
$conn->select_db('login_system');

// Drop the existing bookings table if it exists
$conn->query("DROP TABLE IF EXISTS bookings");

// Create the bookings table with all required columns
$sql = "CREATE TABLE bookings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    location VARCHAR(50) NOT NULL,
    turf VARCHAR(50) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Bookings table created successfully!<br>";
    
    // Now insert a test booking for tomorrow
    $user_id = 1; // Using ID 1 for test
    $date = date('Y-m-d', strtotime('+1 day')); // Tomorrow's date
    $time = '17:00:00'; // 5 PM
    $location = 'kothrud';
    $turf = 'dragon_ball';
    
    $conn->begin_transaction();
    try {
        $insert_sql = "INSERT INTO bookings (user_id, booking_date, booking_time, location, turf) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issss", $user_id, $date, $time, $location, $turf);
        
        if ($stmt->execute()) {
            echo "Test booking inserted successfully for tomorrow at 5 PM at Dragon Ball turf.<br>";
            echo "Date: " . $date . "<br>";
            echo "Time: " . $time . "<br>";
            echo "Location: " . $location . "<br>";
            echo "Turf: " . $turf;
            $conn->commit();
        } else {
            echo "Error inserting booking: " . $stmt->error;
            $conn->rollback();
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating booking: " . $e->getMessage();
    }
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 