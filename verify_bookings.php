<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Make sure we're using the login_system database
$conn->select_db('login_system');

// Get all bookings
$query = "SELECT * FROM bookings ORDER BY booking_date, booking_time";
$result = $conn->query($query);

echo "<h2>All Bookings in Database</h2>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Location</th><th>Turf</th><th>Date</th><th>Time</th><th>Created At</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['location'] . "</td>";
        echo "<td>" . $row['turf'] . "</td>";
        echo "<td>" . $row['booking_date'] . "</td>";
        echo "<td>" . $row['booking_time'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No bookings found in the database.";
}

$conn->close();
?> 