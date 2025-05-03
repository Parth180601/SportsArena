<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'dbconnect.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .welcome {
            margin-bottom: 20px;
        }
        .logout {
            float: right;
        }
        .logout a {
            color: #f44336;
            text-decoration: none;
        }
        .logout a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="welcome">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>You are now logged in to your dashboard.</p>
    </div>
    
    <!-- Add your dashboard content here -->
    <div class="dashboard-content">
        <h3>Your Account Information</h3>
        <?php
        try {
            $stmt = $conn->prepare("SELECT email, created_at FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
            echo "<p>Account created: " . date('F j, Y', strtotime($user['created_at'])) . "</p>";
        } catch(PDOException $e) {
            echo "<p>Error loading user information.</p>";
        }
        ?>
    </div>
</body>
</html> 