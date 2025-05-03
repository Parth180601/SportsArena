<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debug information
if (!function_exists('debug_log')) {
    function debug_log($message) {
        error_log(date('[Y-m-d H:i:s] ') . print_r($message, true) . "\n", 3, __DIR__ . '/admin_dashboard_debug.log');
    }
}
?> 