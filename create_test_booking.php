<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

// Make sure we're using the login_system database
$conn->select_db('login_system');

// First, delete any existing test bookings
$delete_sql = "DELETE FROM bookings WHERE booking_date IN ('2025-04-25', '2025-04-26')";
if ($conn->query($delete_sql)) {
    echo "Deleted existing test bookings.<br>";
} else {
    echo "Error deleting existing bookings: " . $conn->error . "<br>";
}

// Set up test booking for tomorrow (26.04.2025)
$user_id = 1; // Using ID 1 for test
$tomorrow = '2025-04-26'; // Tomorrow's date
$time = '17:00:00'; // 5 PM
$location = 'kothrud';
$turf = 'dragon_ball';

$conn->begin_transaction();
try {
    $insert_sql = "INSERT INTO bookings (user_id, booking_date, booking_time, location, turf) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("issss", $user_id, $tomorrow, $time, $location, $turf);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo "<h3>Test booking created successfully!</h3>";
        echo "<strong>Details:</strong><br>";
        echo "Date: " . $tomorrow . "<br>";
        echo "Time: " . $time . "<br>";
        echo "Location: " . $location . "<br>";
        echo "Turf: " . $turf . "<br><br>";
        
        // Show all current bookings
        echo "<h3>Current Bookings in Database:</h3>";
        $result = $conn->query("SELECT * FROM bookings ORDER BY booking_date, booking_time");
        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Location</th><th>Turf</th><th>Date</th><th>Time</th><th>Created At</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                echo "<td>" . htmlspecialchars($row['turf']) . "</td>";
                echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['booking_time']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No bookings found in the database.";
        }
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    echo "Error creating booking: " . $e->getMessage();
}

$conn->close();
?> 