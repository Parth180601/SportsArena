<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'dbconnect.php';

try {
    // Database connection parameters
    $host = 'localhost';
    $port = 4306;
    $dbname = 'login_system';
    $username = 'root';
    $password = '';

    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "<h2>Database Tables:</h2>";
    $tables = $conn->query("SHOW TABLES")->fetchAll();
    echo "<pre>";
    print_r($tables);
    echo "</pre>";

    echo "<h2>Bookings Table Structure:</h2>";
    $structure = $conn->query("DESCRIBE bookings")->fetchAll();
    echo "<pre>";
    print_r($structure);
    echo "</pre>";

    echo "<h2>Bookings Data:</h2>";
    $bookings = $conn->query("SELECT * FROM bookings")->fetchAll();
    echo "<pre>";
    print_r($bookings);
    echo "</pre>";

    echo "<h2>Users Data:</h2>";
    $users = $conn->query("SELECT id, username, is_admin FROM users")->fetchAll();
    echo "<pre>";
    print_r($users);
    echo "</pre>";

    echo "<h2>Test Query - Turf Distribution:</h2>";
    $query = "
        WITH base_locations AS (
            SELECT 'kothrud' as loc UNION ALL
            SELECT 'baner' UNION ALL
            SELECT 'viman_nagar'
        )
        SELECT 
            CASE bl.loc
                WHEN 'kothrud' THEN 'Kothrud'
                WHEN 'baner' THEN 'Baner'
                WHEN 'viman_nagar' THEN 'Viman Nagar'
            END as location_name,
            COUNT(b.id) as booking_count
        FROM base_locations bl
        LEFT JOIN bookings b ON bl.loc = b.location
        GROUP BY bl.loc";
    $distribution = $conn->query($query)->fetchAll();
    echo "<pre>";
    print_r($distribution);
    echo "</pre>";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?> 