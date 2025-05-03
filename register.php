<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
error_log("Register page - Session ID: " . session_id());
error_log("Register page - Session status: " . session_status());

require_once 'dbconnect.php';

// Verify database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Verify database exists
$result = $conn->query("SELECT DATABASE() as db");
if (!$result) {
    error_log("Failed to get current database: " . $conn->error);
    die("Database error. Please try again later.");
}

$current_db = $result->fetch_assoc()['db'];
error_log("Current database: " . $current_db);

if ($current_db !== "login_system") {
    error_log("Wrong database selected: " . $current_db);
    die("Database configuration error. Please try again later.");
}

// Verify users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows === 0) {
    error_log("Users table does not exist");
    die("Database configuration error. Please try again later.");
}

// Debug database connection
error_log("Database connection status: " . ($conn->connect_error ? "Failed" : "Success"));
error_log("Database name: " . $conn->select_db("login_system") ? "Selected" : "Not selected");

// Verify database and table structure
$check_db = $conn->query("SELECT DATABASE() as db");
$db_name = $check_db->fetch_assoc()['db'];
error_log("Current database: " . $db_name);

$check_table = $conn->query("SHOW TABLES LIKE 'users'");
error_log("Users table exists: " . ($check_table->num_rows > 0 ? "Yes" : "No"));

$check_columns = $conn->query("SHOW COLUMNS FROM users");
error_log("Users table columns:");
while ($column = $check_columns->fetch_assoc()) {
    error_log("Column: " . $column['Field'] . " Type: " . $column['Type']);
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Debug input
    error_log("Registration attempt - Username: " . $username);
    error_log("Registration attempt - Email: " . $email);
    error_log("Registration attempt - Password length: " . strlen($password));
    
    // Check if this is an admin account
    $is_admin = (strpos($username, '@admin') !== false) ? 1 : 0;
    error_log("Is admin account: " . ($is_admin ? "Yes" : "No"));
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
        error_log("Registration failed - Empty fields");
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        error_log("Registration failed - Passwords don't match");
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
        error_log("Registration failed - Password too short");
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            error_log("Prepare failed for SELECT: " . $conn->error);
            $error = "Registration failed - Database error";
        } else {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username or email already exists.";
                error_log("Registration failed - Username or email already exists");
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                error_log("Password hashed successfully");
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    error_log("Prepare failed for INSERT: " . $conn->error);
                    $error = "Registration failed - Database error";
                } else {
                    $stmt->bind_param("sssi", $username, $email, $hashed_password, $is_admin);
                    
                    // Log the values being inserted
                    error_log("Attempting to insert - Username: " . $username . ", Email: " . $email . ", Is Admin: " . $is_admin);
                    
                    if ($stmt->execute()) {
                        $user_id = $stmt->insert_id;
                        error_log("Registration successful - User ID: " . $user_id);
                        
                        // Verify the insert by selecting the user
                        $verify = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $verify->bind_param("i", $user_id);
                        $verify->execute();
                        $result = $verify->get_result();
                        
                        if ($result->num_rows > 0) {
                            error_log("User verified in database - ID: " . $user_id);
                            $user_data = $result->fetch_assoc();
                            error_log("User data: " . print_r($user_data, true));
                            
                            // Set a success message in session
                            $_SESSION['registration_success'] = "Registration successful! Please login with your credentials.";
                            
                            // Redirect to login page
                            header("Location: login.php");
                            exit();
                        } else {
                            error_log("WARNING: User not found after insert - ID: " . $user_id);
                            $error = "Registration failed - User not found after insert";
                        }
                    } else {
                        error_log("Execute failed: " . $stmt->error . " - SQL State: " . $stmt->sqlstate);
                        $error = "Registration failed - Please try again";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        .Login-button a {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border: 1px solid white;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .Login-button a:hover {
            background: white;
            color: #0a0f2c;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            border-color: #0a0f2c;
            outline: none;
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
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #0a0f2c;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .login-link a:hover {
            color: #333;
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
        <div class="Login-button">
            <a href="login.php">Login</a>
        </div>
    </header>
    <div class="container">
        <h1>Register</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="submit-btn">Register</button>
        </form>
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>