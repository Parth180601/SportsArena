<?php
// Google OAuth Configuration
$google_client_id = 'YOUR_GOOGLE_CLIENT_ID';
$google_client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$google_redirect_uri = 'http://localhost/TBS/google-callback.php';

// Database Configuration
$host = "localhost";
$dbname = "login_system";
$username = "root";
$password = "";
$port = 4306;

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?> 