<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure session settings
ini_set('session.cookie_lifetime', 0); // Session cookie expires when browser closes
ini_set('session.gc_maxlifetime', 3600); // Session data expires after 1 hour
ini_set('session.use_only_cookies', 1); // Force sessions to only use cookies
ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing session data
if (isset($_SESSION)) {
    error_log("Clearing existing session data");
    session_unset();
    session_destroy();
    session_start();
}

// Set a flag to indicate user is on login page
$_SESSION['on_login_page'] = true;

// Debug session information
error_log("Login page - Session ID: " . session_id());
error_log("Login page - Session status: " . session_status());
error_log("Login page - Session save path: " . session_save_path());
if (isset($_SESSION)) {
    error_log("Login page - Session contents: " . print_r($_SESSION, true));
}

require_once 'dbconnect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Debug login attempt
    error_log("Login attempt - Username: " . $username);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
        error_log("Login failed - Empty fields");
    } else {
        // Check user in database - removed BINARY to make case-insensitive
        $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE LOWER(username) = LOWER(?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $error = "Login failed - Database error";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Debug query results
            error_log("Login query - Number of results: " . $result->num_rows);
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                error_log("User found - ID: " . $user['id'] . ", Username: " . $user['username']);
                
                if (password_verify($password, $user['password'])) {
                    // Clear any existing session data
                    session_unset();
                    session_destroy();
                    session_start();
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['loggedin'] = true;
                    $_SESSION['role'] = $user['is_admin'] ? 'admin' : 'user';
                    $_SESSION['last_activity'] = time();
                    unset($_SESSION['on_login_page']); // Remove login page flag
                    
                    // Debug session after login
                    error_log("Login successful - Session ID: " . session_id());
                    error_log("Login successful - Session contents: " . print_r($_SESSION, true));
                    error_log("Login successful - Session save path: " . session_save_path());
                    
                    // Redirect based on role
                    if ($user['is_admin']) {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: booking.php");
                    }
                    exit();
                } else {
                    error_log("Login failed - Incorrect password for user: " . $username);
                    $error = "Incorrect password.";
                }
            } else {
                error_log("Login failed - Username not found: " . $username);
                $error = "Username is incorrect.";
            }
            $stmt->close();
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
    <title>Login</title>
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
        .Login-button {
            display: flex;
            gap: 10px;
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
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            background-color: #ffe6e6;
            display: <?php echo !empty($error) ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="img/ArenaLOGO.png" alt="Logo" style="height: 40px;">
        <div class="right-buttons">
            <a href="Home_page.html" style="color: white; text-decoration: none;">Home</a>
        </div>
    </div>

    <div class="container">
        <h1>Login</h1>
        <?php if(!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="submit-btn">Login</button>
        </form>
    </div>
</body>
</html>