<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a flag to indicate user is on login page
$_SESSION['on_login_page'] = true;

// Debug session information
error_log("Login page - Session ID: " . session_id());
error_log("Login page - Session status: " . session_status());
if (isset($_SESSION)) {
    error_log("Login page - Session contents: " . print_r($_SESSION, true));
}

require_once 'dbconnect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Check user in database
        $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE BINARY username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['loggedin'] = true;
                $_SESSION['role'] = $user['is_admin'] ? 'admin' : 'user';
                unset($_SESSION['on_login_page']); // Remove login page flag
                
                // Debug session after login
                error_log("User login - Session ID: " . session_id());
                error_log("User login - Session contents: " . print_r($_SESSION, true));
                
                // Redirect based on role
                if ($user['is_admin']) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: booking.php");
                }
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Username is incorrect.";
        }
        $stmt->close();
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