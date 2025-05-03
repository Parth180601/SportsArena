<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xammp/htdocs/TBS/booking_errors.log');
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please log in to make a booking.'
    ]);
    exit;
}

// Debug logging
error_log("\n=== New Booking Request ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if all required fields are present
if (!isset($_POST['booking_date']) || !isset($_POST['selected_slots']) || !isset($_POST['location']) || !isset($_POST['turf'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Get the form data
$booking_date = $_POST['booking_date'];
$selected_slots = json_decode($_POST['selected_slots'], true);
$location = $_POST['location'];
$turf = $_POST['turf'];
$user_id = $_SESSION['user_id'];

// Validate the data
if (!is_array($selected_slots) || empty($selected_slots)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select at least one time slot.'
    ]);
    exit;
}

try {
    // Connect to database
    require_once 'dbconnect.php';
    
    // Make sure we're using the login_system database
    if (!$conn->select_db('login_system')) {
        throw new Exception("Failed to select database: " . $conn->error);
    }

    // Start transaction
    if (!$conn->begin_transaction()) {
        throw new Exception("Failed to start transaction: " . $conn->error);
    }

    // Check for existing bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b
        JOIN turfs t ON b.turf_id = t.id
        JOIN locations l ON t.location_id = l.id
        WHERE b.booking_date = ? 
        AND l.name = ? 
        AND t.name = ? 
        AND b.booking_time = ?
    ");

    foreach ($selected_slots as $slot) {
        $stmt->bind_param("ssss", $booking_date, $location, $turf, $slot);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => "The time slot $slot is already booked. Please select a different time."
            ]);
            exit;
        }
    }

    // Get turf_id
    $stmt = $conn->prepare("
        SELECT t.id as turf_id 
        FROM turfs t
        JOIN locations l ON t.location_id = l.id
        WHERE l.name = ? AND t.name = ?
    ");
    $stmt->bind_param("ss", $location, $turf);
    $stmt->execute();
    $result = $stmt->get_result();
    $turf_data = $result->fetch_assoc();
    
    if (!$turf_data) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid turf selection'
        ]);
        exit;
    }
    
    $turf_id = $turf_data['turf_id'];

    // Insert bookings
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, turf_id, booking_date, booking_time) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($selected_slots as $slot) {
        $stmt->bind_param("iiss", $user_id, $turf_id, $booking_date, $slot);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert booking: " . $stmt->error);
        }
    }

    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception("Failed to commit transaction: " . $conn->error);
    }

    error_log("All bookings inserted successfully, transaction committed");

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Booking successful!',
        'data' => [
            'location' => $location,
            'turf' => $turf,
            'date' => $booking_date,
            'slots' => $selected_slots,
            'amount' => count($selected_slots) * 500
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in booking process: " . $e->getMessage());
    if (isset($conn) && $conn->connect_errno) {
        error_log("Database connection error: " . $conn->connect_error);
    }
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Error creating booking: ' . $e->getMessage()
    ]);
}

// Close database connection if it exists
if (isset($conn)) {
    $conn->close();
}
?> 