<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xammp/htdocs/TBS/booking_errors.log');
error_reporting(E_ALL);

// Set timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Clear any existing session data
    $_SESSION = array();
    session_destroy();
    
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Add JavaScript to handle back button
?>
<script>
    // Clear browser history and prevent back navigation
    window.history.pushState(null, '', window.location.href);
    window.onpopstate = function() {
        window.history.pushState(null, '', window.location.href);
        window.location.replace('Home_page.html');
    };

    // Prevent caching
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };

    // Check session on page load
    window.onload = function() {
        // Make an AJAX call to check session
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.loggedIn) {
                    window.location.replace('login.php');
                }
            })
            .catch(error => {
                console.error('Error checking session:', error);
                window.location.replace('login.php');
            });
    };
</script>
<?php

// Debug session information
error_log("Booking page - Session ID: " . session_id());
error_log("Booking page - Session status: " . session_status());
if (isset($_SESSION)) {
    error_log("Booking page - Session contents: " . print_r($_SESSION, true));
}

require_once 'dbconnect.php';

// Try different possible paths for TCPDF
$tcpdf_paths = [
    'tcpdf/tcpdf.php',                          // Local directory
    'vendor/tecnickcom/tcpdf/tcpdf.php',        // Composer installation
    '../tcpdf/tcpdf.php',                       // One level up
    'C:/xammp/htdocs/TBS/tcpdf/tcpdf.php'      // Absolute path
];

$tcpdf_loaded = false;
foreach ($tcpdf_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $tcpdf_loaded = true;
        break;
    }
}

if (!$tcpdf_loaded) {
    die("TCPDF library not found. Please install TCPDF using composer or manually place it in the tcpdf directory.");
}

// Make sure we're using the login_system database
$conn->select_db('login_system');

// Debug session information
if (isset($_SESSION['user_id'])) {
    error_log("User ID in session: " . $_SESSION['user_id']);
    error_log("Username in session: " . $_SESSION['username']);
    error_log("Logged in status: " . (isset($_SESSION['loggedin']) ? 'true' : 'false'));
}

// Verify user exists in database
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("User not found in database, destroying session");
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

$error = '';
$success = '';
$rate_per_hour = 500; // Base rate per hour

// Get current date and time in Indian timezone
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$current_timestamp = time();

// Format for JavaScript
$js_current_datetime = json_encode([
    'date' => $current_date,
    'time' => $current_time,
    'timestamp' => $current_timestamp,
    'timezone' => 'Asia/Kolkata'
]);

// Debug current date and time
error_log("Current date: " . $current_date);
error_log("Current time: " . $current_time);

// Initialize booked slots array
$booked_slots = [];

// Get existing bookings for the next 7 days
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+7 days'));
$stmt = $conn->prepare("SELECT booking_date, booking_time FROM bookings WHERE booking_date BETWEEN ? AND ? ORDER BY booking_date, booking_time");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Initialize array for each date
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $booked_slots[$date] = [];
}

// Populate booked slots
while ($row = $result->fetch_assoc()) {
    if (!isset($booked_slots[$row['booking_date']])) {
        $booked_slots[$row['booking_date']] = [];
    }
    $booked_slots[$row['booking_date']][] = $row['booking_time'];
}
$stmt->close();

// Debug booked slots
error_log("Booked slots: " . print_r($booked_slots, true));

// Debug all bookings in database
$debug_query = "SELECT * FROM bookings WHERE booking_date BETWEEN ? AND ?";
$debug_stmt = $conn->prepare($debug_query);
$debug_stmt->bind_param("ss", $start_date, $end_date);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
error_log("All bookings in database:");
while ($row = $debug_result->fetch_assoc()) {
    error_log(print_r($row, true));
}
$debug_stmt->close();

// Get list of locations and their turfs
$locations = [
    'kothrud' => [
        'name' => 'Kothrud',
        'turfs' => [
            'dragon_ball' => 'Dragon Ball',
            'wolves' => 'Wolves',
            'ufs' => 'UFS'
        ]
    ],
    'baner' => [
        'name' => 'Baner',
        'turfs' => [
            'toronto_arena' => 'Toronto Arena',
            'paris_sports' => 'Paris Sports',
            'eagles_sports' => 'Eagles Sports'
        ]
    ],
    'viman_nagar' => [
        'name' => 'Viman Nagar',
        'turfs' => [
            'viman_nagar_turf' => 'Viman Nagar Turf',
            'sky_sports' => 'Sky Sports',
            'arena_sports' => 'Arena Sports'
        ]
    ]
];

// Debug: Log the locations data structure
error_log('Locations data structure: ' . print_r($locations, true));

// Convert locations to JavaScript object
$js_locations = json_encode($locations);
error_log('JavaScript locations: ' . $js_locations);

// Initialize variables with empty values
$selected_location = '';
$selected_turf = '';

// Only set values if they are explicitly selected via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['location']) && $_POST['location'] !== '') {
    $selected_location = $_POST['location'];
    if (isset($_POST['turf']) && $_POST['turf'] !== '') {
        $selected_turf = $_POST['turf'];
    }
}

// Handle booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Set header to expect JSON response
    header('Content-Type: application/json');
    
    error_log("\n=== New Booking Request ===");
    error_log("Time: " . date('Y-m-d H:i:s'));
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        error_log("Error: User not logged in");
        echo json_encode([
            'status' => 'error',
            'message' => 'Please log in to make a booking.'
        ]);
        exit;
    }

    if (isset($_POST['booking_date']) && isset($_POST['selected_slots']) && isset($_POST['location']) && isset($_POST['turf'])) {
        $booking_date = $_POST['booking_date'];
        $selected_slots = json_decode($_POST['selected_slots'], true);
        $location = $_POST['location'];
        $turf = $_POST['turf'];
        
        error_log("Decoded booking data:");
        error_log("Date: " . $booking_date);
        error_log("Slots: " . print_r($selected_slots, true));
        error_log("Location: " . $location);
        error_log("Turf: " . $turf);
        error_log("User ID: " . $_SESSION['user_id']);
        
        if (!is_array($selected_slots) || empty($selected_slots)) {
            error_log("Error: No slots selected");
            echo json_encode([
                'status' => 'error',
                'message' => 'Please select at least one time slot.'
            ]);
            exit;
        }

        try {
            // Make sure we're using the login_system database
            if (!$conn->select_db('login_system')) {
                throw new Exception("Failed to select database: " . $conn->error);
            }

            // Start transaction
            error_log("Starting transaction");
            if (!$conn->begin_transaction()) {
                throw new Exception("Failed to start transaction: " . $conn->error);
            }
            
            // Insert booking for each selected slot
            foreach ($selected_slots as $slot) {
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, location, turf, booking_date, booking_time) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }
                
                error_log("Preparing to insert booking: User={$_SESSION['user_id']}, Date=$booking_date, Time=$slot");
                
                if (!$stmt->bind_param("issss", $_SESSION['user_id'], $location, $turf, $booking_date, $slot)) {
                    throw new Exception("Failed to bind parameters: " . $stmt->error);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute statement: " . $stmt->error);
                }
                
                error_log("Successfully inserted booking. Insert ID: " . $stmt->insert_id);
                $stmt->close();
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
            if ($conn->connect_errno) {
                error_log("Database connection error: " . $conn->connect_error);
            }
            $conn->rollback();
            echo json_encode([
                'status' => 'error',
                'message' => 'Error creating booking: ' . $e->getMessage()
            ]);
        }
    } else {
        error_log("Missing required fields in POST data");
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields'
        ]);
    }
    exit;
} else {
    error_log("Non-POST request received");
}

// Add this function before the HTML
function generatePDF($bookingData) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('TBS');
    $pdf->SetAuthor('TBS Booking System');
    $pdf->SetTitle('Booking Receipt');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Add logo
    $pdf->Image('img/ArenaLOGO.png', 15, 15, 50);
    
    // Add title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 20, 'Booking Receipt', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add booking details
    $pdf->SetFont('helvetica', '', 12);
    
    // Create a table-like structure
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('helvetica', 'B', 12);
    
    // Booking ID
    $pdf->Cell(60, 10, 'Booking ID:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['booking_id'], 0, 1, 'L');
    
    // Booking Date
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Booking Date:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['date'], 0, 1, 'L');
    
    // Location
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Location:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['location'], 0, 1, 'L');
    
    // Turf
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Turf:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['turf'], 0, 1, 'L');
    
    // Time Slots
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Time Slots:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['time_slots'], 0, 1, 'L');
    
    // Number of Hours
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Number of Hours:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['hours'], 0, 1, 'L');
    
    // Total Amount
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Total Amount:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $bookingData['amount'], 0, 1, 'L');
    
    // Payment Status
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 10, 'Payment Status:', 0, 0, 'L');
    $pdf->SetTextColor(255, 0, 0); // Red color for PENDING
    $pdf->Cell(0, 10, 'PENDING', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0); // Reset to black
    
    $pdf->Ln(10);
    
    // Add terms and conditions
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 10, '1. Please arrive 15 minutes before your slot time.
2. Payment must be made before using the facility.
3. Cancellation should be done 24 hours prior to the booking time.
4. Please carry a valid ID proof.
5. Follow all safety guidelines and facility rules.', 0, 'L');
    
    return $pdf;
}

// Add this endpoint for PDF generation
if (isset($_POST['generate_pdf'])) {
    $bookingData = json_decode($_POST['booking_data'], true);
    $pdf = generatePDF($bookingData);
    $pdf->Output('TBS_Booking_Receipt.pdf', 'D');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking</title>
    <!-- Add Razorpay SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        body {
            background-color: gold;
            font-family: Arial, sans-serif;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #0a0f2c;
        }
        .right-buttons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .welcome-message {
            color: white;
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            flex-grow: 1;
        }
        .right-buttons img {
            height: 40px;
        }
        .right-buttons button {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .right-buttons button:hover {
            background: white;
            color: #0a0f2c;
        }
        .auth-buttons {
            display: flex;
            gap: 10px;
        }
        .auth-buttons a {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border: 1px solid white;
            border-radius: 5px;
            transition: all 0.3s ease;
            position: relative;
        }
        .auth-buttons a:hover {
            background: white;
            color: #0a0f2c;
        }
        .auth-buttons a::after {
            display: none;  /* Remove any potential pseudo-element tick marks */
        }
        .auth-buttons a::before {
            display: none;  /* Remove any potential pseudo-element tick marks */
        }
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        .booking-receipt {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        .receipt-title {
            text-align: center;
            color: #0a0f2c;
            font-size: 1.2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0a0f2c;
        }
        .receipt-detail {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .receipt-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        .receipt-value {
            font-weight: bold;
            color: #0a0f2c;
        }
        .receipt-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #0a0f2c;
            font-weight: bold;
            font-size: 1.1em;
        }
        .container h1 {
            text-align: center;
            color: black;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 1.1em;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        .form-group select:focus, .form-group input:focus {
            border-color: #0a0f2c;
            outline: none;
        }
        .date-container {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .date-box {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .date-box:hover {
            border-color: #0a0f2c;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .date-box.selected {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: black;
            color: gold;
            border: none;
            border-radius: 30px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            background: #333;
            transform: translateY(-3px);
        }
        .time-slots {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 12px;
            margin-top: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }
        .time-slot {
            padding: 15px 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            background: white;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60px;
        }
        .time-slot:hover:not(.disabled):not(.booked) {
            border-color: #00C853;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 200, 83, 0.2);
        }
        .time-slot.selected {
            background: #00C853;
            color: white;
            border-color: #00C853;
            font-weight: bold;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 200, 83, 0.4);
            animation: selectPulse 0.3s ease-in-out;
        }
        @keyframes selectPulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        .time-slot.selected .time-slot-price {
            color: white;
        }
        .time-slot.selected::after {
            content: '✓';
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 14px;
            color: white;
            font-weight: bold;
        }
        .time-slot.disabled {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            border-color: #ddd;
            opacity: 0.7;
        }
        .time-slot.booked {
            background-color: #ffe8e8;
            color: #d32f2f;
            border-color: #ffcdd2;
            cursor: not-allowed;
        }
        .time-slot.booked::after {
            content: '×';
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
            color: #d32f2f;
        }
        .time-slot-price {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
        .time-slot-label {
            text-align: center;
            margin-top: 20px;
            color: #333;
            font-weight: bold;
            font-size: 1.2em;
        }

        /* Small status indicators */
        .slot-status {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 10px 0;
            font-size: 0.9em;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .status-color {
            width: 12px;
            height: 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .status-vacant { background: white; }
        .status-expired { background: #f5f5f5; }
        .status-booked { background: #ffe8e8; }
        .status-selected { background: #4CAF50; }

        /* Dialog styles */
        .dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .dialog-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .dialog-content h2 {
            color: #0a0f2c;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .booking-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        .booking-details div {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-details strong {
            color: #0a0f2c;
        }
        .dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }
        .pay-now, .pay-later {
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        .pay-now {
            background: #00C853;
            color: white;
            border: none;
        }
        .pay-now:hover {
            background: #00a844;
            transform: translateY(-2px);
        }
        .pay-later {
            background: white;
            color: #0a0f2c;
            border: 2px solid #0a0f2c;
        }
        .pay-later:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        #dialog-message {
            color: #666;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="right-buttons">
            <img src="img/ArenaLOGO.png" alt="Logo">
            <a href="Home_page.html"><button>Home</button></a>
            <a href="Need Help.html"><button>Need Help?</button></a>
        </div>
        <div class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></div>
        <div class="auth-buttons">
            <a href="logout.php">Logout</a>
        </div>
    </header>
    <div class="container">
        <div class="booking-receipt">
            <div class="receipt-title">Booking Summary</div>
            <div class="receipt-detail">
                <div class="receipt-label">Location</div>
                <div class="receipt-value" id="receipt-location">-</div>
            </div>
            <div class="receipt-detail">
                <div class="receipt-label">Turf Name</div>
                <div class="receipt-value" id="receipt-turf">-</div>
            </div>
            <div class="receipt-detail">
                <div class="receipt-label">Date</div>
                <div class="receipt-value" id="receipt-date">-</div>
            </div>
            <div class="receipt-detail">
                <div class="receipt-label">Time Slots</div>
                <div class="receipt-value" id="receipt-time">-</div>
            </div>
            <div class="receipt-detail">
                <div class="receipt-label">Number of Hours</div>
                <div class="receipt-value" id="receipt-hours">-</div>
            </div>
            <div class="receipt-total">
                <div class="receipt-label">Total Amount</div>
                <div class="receipt-value" id="receipt-total">₹0</div>
            </div>
        </div>
        <div>
            <h1>Book Your Turf</h1>
            <form action="booking.php" method="POST">
                <div class="form-group">
                    <label for="location">Location</label>
                    <select id="location" name="location" required>
                        <option value="">Select Location</option>
                        <option value="kothrud">Kothrud</option>
                        <option value="baner">Baner</option>
                        <option value="viman_nagar">Viman Nagar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="turf">Turf</label>
                    <select id="turf" name="turf" required>
                        <option value="">Select Turf</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Date</label>
                    <div class="date-container" id="dateContainer">
                        <?php
                        for ($i = 0; $i < 7; $i++) {
                            $date = date('Y-m-d', strtotime("+$i days"));
                            $day = date('D', strtotime($date));
                            $formatted_date = date('d M', strtotime($date));
                            echo "<div class='date-box' data-date='$date'>";
                            echo "<div>$day</div>";
                            echo "<div>$formatted_date</div>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
                <div class="slot-status">
                    <div class="status-item">
                        <div class="status-color status-vacant"></div>
                        <span>Vacant</span>
                    </div>
                    <div class="status-item">
                        <div class="status-color status-expired"></div>
                        <span>Expired</span>
                    </div>
                    <div class="status-item">
                        <div class="status-color status-booked"></div>
                        <span>Booked</span>
                    </div>
                    <div class="status-item">
                        <div class="status-color status-selected"></div>
                        <span>Selected</span>
                    </div>
                </div>
                <div class="time-slots" id="timeSlots">
                    <?php
                    $start_time = strtotime('06:00');
                    $end_time = strtotime('22:00');
                    $interval = 60 * 60; // 1 hour interval
                    
                    for ($time = $start_time; $time <= $end_time; $time += $interval) {
                        $time_str = date('h:i A', $time);
                        $time_24h = date('H:i:s', $time);
                        $price = "₹500/hr";
                        echo "<div class='time-slot' data-time='$time_str' data-time-24='$time_24h'>";
                        echo "<div class='time-text'>$time_str</div>";
                        echo "<div class='time-slot-price'>$price</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
                <input type="hidden" name="booking_date" id="bookingDate">
                <input type="hidden" name="selected_slots" id="selectedSlots">
                <button type="submit" class="submit-btn">Book Now</button>
            </form>
        </div>
    </div>
    <div id="successDialog" class="dialog">
        <div class="dialog-content">
            <h2>Booking Successful!</h2>
            <div class="booking-details">
                <div>
                    <strong>Location:</strong>
                    <span id="dialog-location"></span>
                </div>
                <div>
                    <strong>Turf:</strong>
                    <span id="dialog-turf"></span>
                </div>
                <div>
                    <strong>Date:</strong>
                    <span id="dialog-date"></span>
                </div>
                <div>
                    <strong>Time Slots:</strong>
                    <span id="dialog-slots"></span>
                </div>
                <div>
                    <strong>Total Amount:</strong>
                    <span id="dialog-amount"></span>
                </div>
            </div>
            <p id="dialog-message">Please choose your payment option:</p>
            <div class="dialog-buttons">
                <button onclick="handlePayNow()" class="pay-now">Pay Now</button>
                <button onclick="handlePayLater()" class="pay-later">Pay Later</button>
            </div>
        </div>
    </div>
    <script>
        // Get PHP current time variables
        const currentTime = <?php echo $js_current_datetime; ?>;
        
        // Function to check if a time slot should be disabled
        function shouldDisableTimeSlot(timeStr, selectedDate) {
            // Get current date and time from PHP
            const serverDateTime = <?php echo $js_current_datetime; ?>;
            const now = new Date(serverDateTime.date + ' ' + serverDateTime.time);
            const selectedDateObj = new Date(selectedDate);
            
            // Reset hours to compare just dates
            const todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const selectDate = new Date(selectedDateObj.getFullYear(), selectedDateObj.getMonth(), selectedDateObj.getDate());
            
            // If selected date is before today
            if (selectDate < todayDate) {
                return true;
            }
            
            // If selected date is today
            if (selectDate.getTime() === todayDate.getTime()) {
                // Convert time string (e.g., "06:00 AM") to 24-hour format
                const [time, period] = timeStr.split(' ');
                let [hours, minutes] = time.split(':');
                hours = parseInt(hours);
                minutes = parseInt(minutes);
                
                // Convert to 24-hour format if PM
                if (period === 'PM' && hours !== 12) {
                    hours += 12;
                }
                // Handle 12 AM case
                if (period === 'AM' && hours === 12) {
                    hours = 0;
                }
                
                // Get current hours and minutes
                const currentHours = now.getHours();
                const currentMinutes = now.getMinutes();
                
                // Compare times - disable if the slot time is less than or equal to current time
                if (hours < currentHours || (hours === currentHours && minutes <= currentMinutes)) {
                    return true;
                }
                return false;
            }
            
            // For future dates, don't disable any slots
            return false;
        }

        // Function to convert 12-hour time to 24-hour format
        function convertTo24Hour(timeStr) {
            const [time, period] = timeStr.split(' ');
            let [hours, minutes] = time.split(':');
            hours = parseInt(hours);
            
            if (period === 'PM' && hours !== 12) {
                hours += 12;
            } else if (period === 'AM' && hours === 12) {
                hours = 0;
            }
            
            return `${hours.toString().padStart(2, '0')}:${minutes}:00`;
        }

        // Function to update time slots based on current time
        function updateTimeSlots() {
            const selectedDate = document.getElementById('bookingDate').value;
            const selectedLocation = document.getElementById('location').value;
            const selectedTurf = document.getElementById('turf').value;
            
            // Only proceed if all required fields are selected
            if (!selectedDate || !selectedLocation || !selectedTurf) {
                console.log('Missing required fields');
                return;
            }
            
            console.log('Checking booked slots for:');
            console.log('Date:', selectedDate);
            console.log('Location:', selectedLocation);
            console.log('Turf:', selectedTurf);
            
            // Reset all slots first
            const allSlots = document.querySelectorAll('.time-slot');
            allSlots.forEach(slot => {
                slot.classList.remove('disabled', 'booked', 'selected');
                slot.style.backgroundColor = '';
                slot.style.color = '';
                slot.style.borderColor = '';
                slot.style.cursor = 'pointer';
                if (slot.querySelector('.time-slot-price')) {
                    slot.querySelector('.time-slot-price').style.color = '#666';
                }
            });

            // First disable slots based on current time
            allSlots.forEach(slot => {
                if (shouldDisableTimeSlot(slot.dataset.time, selectedDate)) {
                    slot.classList.add('disabled');
                    slot.style.backgroundColor = '#f5f5f5';
                    slot.style.color = '#999';
                    slot.style.cursor = 'not-allowed';
                    if (slot.querySelector('.time-slot-price')) {
                        slot.querySelector('.time-slot-price').style.color = '#999';
                    }
                }
            });

            // Then check for booked slots
            fetch('check_bookings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    date: selectedDate,
                    location: selectedLocation,
                    turf: selectedTurf
                })
            })
            .then(response => response.json())
            .then(bookedSlots => {
                console.log('Received booked slots:', bookedSlots);
                
                // Mark booked slots
                allSlots.forEach(slot => {
                    const timeStr = slot.dataset.time;
                    const time24 = convertTo24Hour(timeStr);
                    console.log('Checking slot:', timeStr, '(24h format:', time24, ')');
                    
                    if (bookedSlots.includes(time24)) {
                        console.log('Marking as booked:', timeStr);
                        slot.classList.add('booked');
                        slot.style.backgroundColor = '#ffe8e8';
                        slot.style.color = '#d32f2f';
                        slot.style.borderColor = '#ffcdd2';
                        slot.style.cursor = 'not-allowed';
                        if (slot.querySelector('.time-slot-price')) {
                            slot.querySelector('.time-slot-price').style.color = '#d32f2f';
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error checking booked slots:', error);
            });

            // Update receipt
            updateReceipt();
        }

        // Add interval to update time slots every minute
        setInterval(updateTimeSlots, 60000);

        function updateReceipt() {
            const selectedSlots = Array.from(document.querySelectorAll('.time-slot.selected'));
            const hours = selectedSlots.length;
            const totalAmount = hours * 500;
            
            // Update receipt fields
            const timeSlotsText = selectedSlots.length ? 
                selectedSlots.map(slot => slot.dataset.time).join(', ') : '-';
            
            document.getElementById('receipt-time').textContent = timeSlotsText;
            document.getElementById('receipt-hours').textContent = hours || '-';
            document.getElementById('receipt-total').textContent = hours ? `₹${totalAmount}` : '₹0';
            
            // Update hidden input for form submission
            const selectedTimes = selectedSlots.map(slot => slot.dataset.time).sort();
            document.getElementById('selectedSlots').value = JSON.stringify(selectedTimes);
            
            // Debug log
            console.log('Selected slots:', selectedTimes);
            console.log('Receipt updated with hours:', hours, 'and amount:', totalAmount);
        }

        // Location data
        const locationData = {
            'kothrud': {
                name: 'Kothrud',
                turfs: {
                    'dragon_ball': 'Dragon Ball',
                    'wolves': 'Wolves',
                    'ufs': 'UFS'
                }
            },
            'baner': {
                name: 'Baner',
                turfs: {
                    'toronto_arena': 'Toronto Arena',
                    'paris_sports': 'Paris Sports',
                    'eagles_sports': 'Eagles Sports'
                }
            },
            'viman_nagar': {
                name: 'Viman Nagar',
                turfs: {
                    'viman_nagar_turf': 'Viman Nagar Turf',
                    'sky_sports': 'Sky Sports',
                    'arena_sports': 'Arena Sports'
                }
            }
        };

        // Function to handle location change
        document.getElementById('location').addEventListener('change', function() {
            const selectedLocation = this.value;
            const turfSelect = document.getElementById('turf');
            
            // Clear turf dropdown
            turfSelect.innerHTML = '<option value="">Select Turf</option>';
            
            if (selectedLocation && locationData[selectedLocation]) {
                const turfs = locationData[selectedLocation].turfs;
                Object.entries(turfs).forEach(([key, name]) => {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = name;
                    turfSelect.appendChild(option);
                });
                
                // Update receipt location
                document.getElementById('receipt-location').textContent = locationData[selectedLocation].name;
            } else {
                document.getElementById('receipt-location').textContent = '-';
            }
            
            // Reset turf in receipt
            document.getElementById('receipt-turf').textContent = '-';
            
            // Clear selected time slots
            document.querySelectorAll('.time-slot.selected').forEach(slot => 
                slot.classList.remove('selected'));
            
            // Update time slots only if date and turf are selected
            const selectedDate = document.getElementById('bookingDate').value;
            const selectedTurf = document.getElementById('turf').value;
            if (selectedDate && selectedTurf) {
                updateTimeSlots();
            }
            updateReceipt();
        });

        // Turf change handler
        document.getElementById('turf').addEventListener('change', function() {
            const selectedLocation = document.getElementById('location').value;
            const selectedTurf = this.value;
            
            if (selectedLocation && selectedTurf && locationData[selectedLocation]) {
                document.getElementById('receipt-turf').textContent = 
                    locationData[selectedLocation].turfs[selectedTurf];
                    
                // Clear selected time slots
                document.querySelectorAll('.time-slot.selected').forEach(slot => 
                    slot.classList.remove('selected'));
                
                // Update time slots only if date is selected
                const selectedDate = document.getElementById('bookingDate').value;
                if (selectedDate) {
                    updateTimeSlots();
                }
            } else {
                document.getElementById('receipt-turf').textContent = '-';
            }
            updateReceipt();
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date selection
            const serverDateTime = <?php echo $js_current_datetime; ?>;
            const today = new Date(serverDateTime.date);
            
            const dateBoxes = document.querySelectorAll('.date-box');
            dateBoxes.forEach(box => {
                const boxDate = new Date(box.dataset.date);
                // Disable past dates
                if (boxDate < today) {
                    box.classList.add('disabled');
                }
                // Do not auto-select any date initially
                document.getElementById('bookingDate').value = '';
                document.getElementById('receipt-date').textContent = '-';
            });
            
            // Initial updates
            updateReceipt();
            
            // Clear location and turf selections
            document.getElementById('location').value = '';
            document.getElementById('turf').value = '';
        });

        // Date selection handler
        document.querySelectorAll('.date-box').forEach(box => {
            box.addEventListener('click', function() {
                const selectedLocation = document.getElementById('location').value;
                const selectedTurf = document.getElementById('turf').value;
                
                if (!selectedLocation || !selectedTurf) {
                    alert('Please select a location and turf first');
                    return;
                }
                
                if (!this.classList.contains('disabled')) {
                    // Remove selected class from all date boxes
                    document.querySelectorAll('.date-box').forEach(b => 
                        b.classList.remove('selected'));
                    
                    // Add selected class to clicked box
                    this.classList.add('selected');
                    
                    // Update booking date input and receipt
                    const selectedDate = this.dataset.date;
                    console.log('Selected date from box:', selectedDate);
                    document.getElementById('bookingDate').value = selectedDate;
                    document.getElementById('receipt-date').textContent = selectedDate;
                    
                    // Clear selected time slots
                    document.querySelectorAll('.time-slot.selected').forEach(slot => 
                        slot.classList.remove('selected'));
                    
                    // Update time slots and receipt
                    updateTimeSlots();
                    updateReceipt();
                }
            });
        });

        // Time slot selection handler
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                if (!this.classList.contains('disabled') && !this.classList.contains('booked')) {
                    // Toggle selection
                    this.classList.toggle('selected');
                    
                    // Update visual feedback
                    if (this.classList.contains('selected')) {
                        this.style.backgroundColor = '#00C853';
                        this.style.color = 'white';
                        this.querySelector('.time-slot-price').style.color = 'white';
                    } else {
                        this.style.backgroundColor = 'white';
                        this.style.color = 'black';
                        this.querySelector('.time-slot-price').style.color = '#666';
                    }
                    
                    // Immediately update receipt
                    const selectedSlots = Array.from(document.querySelectorAll('.time-slot.selected'));
                    const hours = selectedSlots.length;
                    const totalAmount = hours * 500;
                    
                    // Update receipt fields directly
                    document.getElementById('receipt-time').textContent = selectedSlots.length ? 
                        selectedSlots.map(s => s.querySelector('.time-text').textContent).join(', ') : '-';
                    document.getElementById('receipt-hours').textContent = hours || '-';
                    document.getElementById('receipt-total').textContent = hours ? `₹${totalAmount}` : '₹0';
                    
                    // Update hidden input
                    document.getElementById('selectedSlots').value = JSON.stringify(
                        selectedSlots.map(s => s.querySelector('.time-text').textContent)
                    );
                    
                    // Debug log
                    console.log('Time slot clicked:', this.querySelector('.time-text').textContent);
                    console.log('Selected slots:', document.getElementById('selectedSlots').value);
                }
            });
        });

        // Form submission handler
        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const selectedDate = document.getElementById('bookingDate').value;
            const selectedSlotsElement = document.querySelectorAll('.time-slot.selected');
            const location = document.getElementById('location').value;
            const turf = document.getElementById('turf').value;
            
            // Debug log the form data
            console.log('Form submission data:');
            console.log('Date:', selectedDate);
            console.log('Location:', location);
            console.log('Turf:', turf);
            console.log('Selected slots:', Array.from(selectedSlotsElement).map(slot => slot.dataset.time));
            
            if (!selectedDate) {
                alert('Please select a date');
                return;
            }
            if (!selectedSlotsElement || selectedSlotsElement.length === 0) {
                alert('Please select at least one time slot');
                return;
            }
            if (!location) {
                alert('Please select a location');
                return;
            }
            if (!turf) {
                alert('Please select a turf');
                return;
            }

            try {
                // Get selected slots in 24-hour format
                const selectedSlots = Array.from(selectedSlotsElement).map(slot => convertTo24Hour(slot.dataset.time));
                console.log('Converted slots to 24h format:', selectedSlots);
                
                // Create FormData object
                const formData = new FormData();
                formData.append('booking_date', selectedDate);
                formData.append('selected_slots', JSON.stringify(selectedSlots));
                formData.append('location', location);
                formData.append('turf', turf);

                // Submit form using fetch
                console.log('Submitting booking to server...');
                const response = await fetch('process_booking.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('Server response received');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Processing server response:', data);
                
                if (data.status === 'success') {
                    console.log('Booking successful');
                    // Update dialog content
                    document.getElementById('dialog-location').textContent = locationData[location].name;
                    document.getElementById('dialog-turf').textContent = locationData[location].turfs[turf];
                    document.getElementById('dialog-date').textContent = selectedDate;
                    document.getElementById('dialog-slots').textContent = Array.from(selectedSlotsElement)
                        .map(slot => slot.querySelector('.time-text').textContent)
                        .join(', ');
                    document.getElementById('dialog-amount').textContent = `₹${data.data.amount}`;
                    
                    // Show success dialog
                    document.getElementById('successDialog').style.display = 'flex';
                    
                    // Update time slots to reflect the new booking
                    updateTimeSlots();
                } else {
                    console.error('Booking failed:', data.message);
                    alert(data.message || 'An error occurred while processing your booking. Please try again.');
                }
            } catch (error) {
                console.error('Error submitting booking:', error);
                alert('An error occurred while processing your booking. Please try again.');
            }
        });

        // Function to handle Pay Now button
        function handlePayNow() {
            const amount = parseInt(document.getElementById('dialog-amount').textContent.replace('₹', ''));
            const options = {
                key: 'YOUR_RAZORPAY_KEY',
                amount: amount * 100,
                currency: 'INR',
                name: 'TBS Booking',
                description: 'Turf Booking Payment',
                handler: function(response) {
                    alert('Payment successful! Payment ID: ' + response.razorpay_payment_id);
                    window.location.href = 'Home_page.html';
                },
                prefill: {
                    name: '<?php echo htmlspecialchars($_SESSION['username']); ?>',
                    email: '<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>'
                },
                theme: {
                    color: '#00C853'
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
            
            // Close the success dialog
            document.getElementById('successDialog').style.display = 'none';
        }

        // Function to handle Pay Later button
        function handlePayLater() {
            // Get booking details
            const bookingData = {
                booking_id: 'TBS' + Date.now(),
                date: document.getElementById('dialog-date').textContent,
                location: document.getElementById('dialog-location').textContent,
                turf: document.getElementById('dialog-turf').textContent,
                time_slots: document.getElementById('dialog-slots').textContent,
                hours: document.querySelectorAll('.time-slot.selected').length,
                amount: document.getElementById('dialog-amount').textContent
            };
            
            // Create form for PDF generation
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'generate_receipt.php';
            form.target = '_blank';
            
            // Add booking data
            const bookingDataInput = document.createElement('input');
            bookingDataInput.type = 'hidden';
            bookingDataInput.name = 'booking_data';
            bookingDataInput.value = JSON.stringify(bookingData);
            form.appendChild(bookingDataInput);
            
            // Add form to document and submit
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Close the success dialog
            document.getElementById('successDialog').style.display = 'none';
            
            // Show confirmation and redirect
            alert('Booking confirmed! Your receipt will open in a new tab.');
            window.location.href = 'booking.php';
        }

        // Function to close dialogs
        function closeDialog(dialogId) {
            document.getElementById(dialogId).style.display = 'none';
        }

        // Close dialog when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('dialog')) {
                event.target.style.display = 'none';
            }
        };
    </script>
</body>
</html>