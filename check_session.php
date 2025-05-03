<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Content-Type: application/json');

// Check if user is logged in
$response = [
    'loggedIn' => isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true
];

// Debug session information
error_log("Session check - Session ID: " . session_id());
error_log("Session check - Session status: " . session_status());
error_log("Session check - Logged in: " . ($response['loggedIn'] ? 'true' : 'false'));
if (isset($_SESSION)) {
    error_log("Session check - Session contents: " . print_r($_SESSION, true));
}

// Return JSON response
echo json_encode($response);
?> 