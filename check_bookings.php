<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xammp/htdocs/TBS/booking_errors.log');
error_reporting(E_ALL);

// Set header to return JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging
error_log("\n=== Checking Booked Slots ===");
error_log("Time: " . date('Y-m-d H:i:s'));

// Get JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

error_log("Received data: " . print_r($data, true));

// Validate input data
if (!isset($data['date']) || !isset($data['location']) || !isset($data['turf'])) {
    error_log("Missing required fields");
    echo json_encode([]);
    exit;
}

$date = $data['date'];
$location = $data['location'];
$turf = $data['turf'];

error_log("Checking bookings for:");
error_log("Date: $date");
error_log("Location: $location");
error_log("Turf: $turf");

try {
    // Connect to database
    require_once 'dbconnect.php';
    
    // Make sure we're using the login_system database
    if (!$conn->select_db('login_system')) {
        throw new Exception("Failed to select database: " . $conn->error);
    }

    // Prepare SQL statement to get booked slots
    $stmt = $conn->prepare("
        SELECT DISTINCT booking_time 
        FROM bookings 
        WHERE booking_date = ? 
        AND location = ? 
        AND turf = ?
    ");

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind parameters
    if (!$stmt->bind_param("sss", $date, $location, $turf)) {
        throw new Exception("Failed to bind parameters: " . $stmt->error);
    }

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    // Get results
    $result = $stmt->get_result();
    $booked_slots = [];

    // Fetch all booked time slots
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['booking_time'];
    }

    error_log("Found booked slots: " . print_r($booked_slots, true));

    // Return the booked slots as JSON
    echo json_encode($booked_slots);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error checking booked slots: " . $e->getMessage());
    echo json_encode([]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Check total bookings
$result = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total = $result->fetch_assoc();
echo "Total bookings in database: " . $total['total'] . "<br><br>";

// Get recent bookings with details
$query = "
    SELECT 
        b.id,
        b.booking_date,
        b.booking_time,
        l.name as location_name,
        t.name as turf_name,
        u.username,
        COALESCE(p.status, 'pending') as payment_status
    FROM bookings b
    JOIN turfs t ON b.turf_id = t.id
    JOIN locations l ON t.location_id = l.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN payments p ON b.id = p.booking_id
    ORDER BY b.booking_date DESC, b.booking_time DESC
    LIMIT 5
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "Recent Bookings:<br>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>Location</th><th>Turf</th><th>User</th><th>Payment</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['booking_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['location_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['turf_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No bookings found in the database.";
}
?> 