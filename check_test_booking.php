<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';
$conn->select_db('login_system');

$date = '2025-04-23';
$time = '17:00:00';
$location = 'Kothrud';
$turf = 'Dragon Ball';

$stmt = $conn->prepare("SELECT * FROM bookings WHERE date = ? AND time = ? AND location = ? AND turf = ?");
$stmt->bind_param("ssss", $date, $time, $location, $turf);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    echo "Booking found:\n";
    print_r($booking);
} else {
    echo "No booking found for the specified criteria.\n";
}

$stmt->close();
$conn->close();
?> 