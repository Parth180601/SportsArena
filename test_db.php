<?php
require_once 'dbconnect.php';

try {
    echo "<h2>Database Connection Test</h2>";
    
    // Test connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "Database connection successful!<br><br>";
    
    // List all tables
    echo "<h3>Database Tables:</h3>";
    $result = $conn->query("SHOW TABLES");
    if ($result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "No tables found in database!";
    }
    
    // Check each table's structure
    echo "<h3>Table Structures:</h3>";
    $tables = ['users', 'locations', 'turfs', 'bookings', 'payments'];
    foreach ($tables as $table) {
        echo "<h4>$table table:</h4>";
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Table $table does not exist!<br>";
        }
    }
    
    // Check for data in tables
    echo "<h3>Data in Tables:</h3>";
    foreach ($tables as $table) {
        echo "<h4>$table data:</h4>";
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "Number of records: $count<br>";
            if ($count > 0) {
                $result = $conn->query("SELECT * FROM $table LIMIT 5");
                echo "<table border='1'>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<td>$key: $value</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 