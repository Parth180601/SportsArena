<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating MySQL Configuration</h2>";

// Path to create my.ini
$my_ini_path = "C:\\xampp\\mysql\\bin\\my.ini";

// Basic MySQL configuration content
$my_ini_content = "[mysqld]
skip-grant-tables
port=3306
basedir=\"C:/xampp/mysql\"
tmpdir=\"C:/xampp/tmp\"
datadir=\"C:/xampp/mysql/data\"
pid_file=\"mysql.pid\"
socket=\"MySQL\"
key_buffer_size=16M
max_allowed_packet=1M
sort_buffer_size=512K
net_buffer_length=8K
read_buffer_size=256K
read_rnd_buffer_size=512K
myisam_sort_buffer_size=8M
log_error=\"mysql_error.log\"

[client]
port=3306
socket=\"MySQL\"

[mysqldump]
quick
max_allowed_packet=16M

[mysql]
no-auto-rehash

[isamchk]
key_buffer=20M
sort_buffer_size=20M
read_buffer=2M
write_buffer=2M

[myisamchk]
key_buffer=20M
sort_buffer_size=20M
read_buffer=2M
write_buffer=2M

[mysqlhotcopy]
interactive-timeout";

// Try to create the directory if it doesn't exist
if (!is_dir(dirname($my_ini_path))) {
    mkdir(dirname($my_ini_path), 0777, true);
}

// Create my.ini file
if (file_put_contents($my_ini_path, $my_ini_content)) {
    echo "✓ Created my.ini file at $my_ini_path<br>";
    
    // Stop MySQL service
    echo "Stopping MySQL service...<br>";
    exec('net stop MySQL', $output, $return_var);
    if ($return_var !== 0) {
        echo "Warning: Could not stop MySQL service. Please stop it manually from XAMPP Control Panel.<br>";
    }
    
    // Start MySQL service
    echo "Starting MySQL service...<br>";
    exec('net start MySQL', $output, $return_var);
    if ($return_var !== 0) {
        echo "Warning: Could not start MySQL service. Please start it manually from XAMPP Control Panel.<br>";
    }
    
    // Wait for MySQL to start
    sleep(2);
    
    // Try to connect and reset password
    try {
        $conn = new mysqli('localhost', 'root', '');
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Reset root password
        $conn->query("ALTER USER 'root'@'localhost' IDENTIFIED BY ''");
        $conn->query("FLUSH PRIVILEGES");
        
        echo "✓ Successfully reset root password<br>";
        
        // Remove skip-grant-tables
        $content = file_get_contents($my_ini_path);
        $content = str_replace("skip-grant-tables\n", "", $content);
        file_put_contents($my_ini_path, $content);
        echo "✓ Removed skip-grant-tables from my.ini<br>";
        
        // Restart MySQL service
        echo "Restarting MySQL service...<br>";
        exec('net stop MySQL');
        exec('net start MySQL');
        
        echo "<div style='color: green;'>✓ MySQL configuration has been set up successfully!</div><br>";
        echo "You can now access MySQL with:<br>";
        echo "Username: root<br>";
        echo "Password: (leave empty)<br>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div><br>";
        echo "Please try these manual steps:<br>";
        echo "1. Open Command Prompt as Administrator<br>";
        echo "2. Run these commands:<br>";
        echo "   cd C:\\xampp\\mysql\\bin<br>";
        echo "   mysql -u root<br>";
        echo "3. In MySQL prompt, run:<br>";
        echo "   ALTER USER 'root'@'localhost' IDENTIFIED BY '';<br>";
        echo "   FLUSH PRIVILEGES;<br>";
        echo "   EXIT;<br>";
        echo "4. Restart MySQL service from XAMPP Control Panel<br>";
    }
} else {
    echo "<div style='color: red;'>Error: Could not create my.ini file</div><br>";
    echo "Please try these manual steps:<br>";
    echo "1. Open Notepad as Administrator<br>";
    echo "2. Copy and paste this content:<br>";
    echo "<pre>" . htmlspecialchars($my_ini_content) . "</pre>";
    echo "3. Save the file as 'my.ini' in C:\\xampp\\mysql\\bin\\<br>";
    echo "4. Restart MySQL service from XAMPP Control Panel<br>";
}
?> 