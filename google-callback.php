<?php
require_once 'config.php';
session_start();

if (isset($_GET['code'])) {
    // Exchange the authorization code for an access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $_GET['code'],
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    $response = curl_exec($ch);
    curl_close($ch);

    $token = json_decode($response, true);

    if (isset($token['access_token'])) {
        // Get user info from Google
        $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token['access_token']]);
        $response = curl_exec($ch);
        curl_close($ch);

        $userinfo = json_decode($response, true);

        if (isset($userinfo['id'])) {
            // Check if user already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ? OR email = ?");
            $stmt->bind_param("ss", $userinfo['id'], $userinfo['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // User exists, log them in
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $userinfo['name'];
                header("Location: booking.php");
                exit();
            } else {
                // Create new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, google_id) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $userinfo['name'], $userinfo['email'], $userinfo['id']);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['username'] = $userinfo['name'];
                    header("Location: booking.php");
                    exit();
                } else {
                    $error = "Error creating account. Please try again.";
                }
            }
        }
    }
}

// If something went wrong, redirect to register page
header("Location: register.php?error=google_auth_failed");
exit();
?> 