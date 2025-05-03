<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Verification</h2>";

// Database connection with correct password
$conn = new mysqli('localhost', 'root', 'password');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✓ Connected to MySQL server<br>";

// Show all databases
echo "<h3>All Databases:</h3>";
$result = $conn->query("SHOW DATABASES");
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Select login_system database
if (!$conn->select_db('login_system')) {
    die("Error selecting database: " . $conn->error);
}
echo "✓ Selected login_system database<br>";

// Show all tables with their details
echo "<h3>Tables in login_system database:</h3>";
$result = $conn->query("SHOW TABLES");
echo "<ul>";
while ($row = $result->fetch_array()) {
    $table = $row[0];
    echo "<li><strong>" . $table . "</strong>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE " . $table);
    echo "<table border='1' cellpadding='5' style='margin-left: 20px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($field = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . $field['Default'] . "</td>";
        echo "<td>" . $field['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</li>";
}
echo "</ul>";

// Check table permissions
echo "<h3>Table Permissions:</h3>";
$result = $conn->query("SHOW GRANTS FOR CURRENT_USER");
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

$conn->close();
?> 