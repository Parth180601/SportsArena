<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Function to output debug messages directly to the page as HTML comments
function debug_to_page($message) {
    echo "<!-- Debug: " . htmlspecialchars($message) . " -->\n";
}

// Initialize booking stats array
$GLOBALS['booking_stats'] = [
    'total_bookings' => 0,
    'occupancy_rate' => 0,
    'turfs_by_location' => [],
    'recent_bookings' => []
];

try {
    require_once 'dbconnect.php';
    debug_to_page("Database connection established");
    
    // Get total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $total = $result->fetch_assoc();
    $GLOBALS['booking_stats']['total_bookings'] = $total['total'];
    debug_to_page("Total bookings: " . $total['total']);

    // Calculate today's occupancy rate
    // Total possible slots per day = 9 turfs × 12 time slots = 108
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT ROUND((COUNT(*) * 100.0 / 108), 1) as rate 
        FROM bookings 
        WHERE booking_date = ?
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $occupancy = $result->fetch_assoc();
    $GLOBALS['booking_stats']['occupancy_rate'] = $occupancy['rate'] ?? 0;
    debug_to_page("Today's occupancy rate: " . $GLOBALS['booking_stats']['occupancy_rate'] . "%");

    // Get turf distribution by location
    $result = $conn->query("
        SELECT 
            l.name as location_name,
            COALESCE(COUNT(b.id), 0) as booking_count
        FROM locations l
        LEFT JOIN turfs t ON l.id = t.location_id
        LEFT JOIN bookings b ON t.id = b.turf_id
        GROUP BY l.id, l.name
        ORDER BY l.name
    ");
    while ($row = $result->fetch_assoc()) {
        $GLOBALS['booking_stats']['turfs_by_location'][$row['location_name']] = (int)$row['booking_count'];
    }
    debug_to_page("Turf distribution: " . json_encode($GLOBALS['booking_stats']['turfs_by_location']));

    // Get recent bookings
    $result = $conn->query("
        SELECT 
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
        LIMIT 10
    ");
    $GLOBALS['booking_stats']['recent_bookings'] = [];
    while ($row = $result->fetch_assoc()) {
        $GLOBALS['booking_stats']['recent_bookings'][] = $row;
    }
    debug_to_page("Recent bookings count: " . count($GLOBALS['booking_stats']['recent_bookings']));

    // Log the final booking stats array
    debug_to_page("Final booking stats array: " . json_encode($GLOBALS['booking_stats']));

} catch (Exception $e) {
    debug_to_page("Database error: " . $e->getMessage());
    // Initialize with empty data on error
    $GLOBALS['booking_stats'] = [
        'total_bookings' => 0,
        'occupancy_rate' => 0,
        'turfs_by_location' => [],
        'recent_bookings' => []
    ];
}
?> 