<?php
require_once 'dbconnect.php';

// Set the specific date, time, location, and turf we want to check
$date = '2024-04-23';
$time = '17:00:00';
$location = 'kothrud';
$turf = 'dragon_ball';

// Prepare and execute query
$query = "SELECT * FROM bookings WHERE booking_date = ? AND booking_time = ? AND location = ? AND turf = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $date, $time, $location, $turf);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "BOOKED: The slot at 5 PM on April 23rd for Dragon Ball turf is booked.";
} else {
    echo "AVAILABLE: The slot at 5 PM on April 23rd for Dragon Ball turf is available.";
}

$stmt->close();
$conn->close();
?> 