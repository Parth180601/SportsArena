<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Make sure we're using the login_system database
$conn->select_db('login_system');

// Set the booking details
$user_id = 1; // Using ID 1 for test
$date = '2025-04-23';
$time = '17:00:00';
$location = 'kothrud';
$turf = 'dragon_ball';

// First, check if the booking already exists
$check_sql = "SELECT * FROM bookings WHERE booking_date = ? AND booking_time = ? AND location = ? AND turf = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ssss", $date, $time, $location, $turf);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "Booking already exists for this slot.";
} else {
    // Prepare and execute query to insert new booking
    $insert_sql = "INSERT INTO bookings (user_id, booking_date, booking_time, location, turf) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("issss", $user_id, $date, $time, $location, $turf);

    if ($insert_stmt->execute()) {
        echo "Test booking inserted successfully for April 23rd, 5 PM at Dragon Ball turf.";
    } else {
        echo "Error inserting booking: " . $insert_stmt->error;
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?> 