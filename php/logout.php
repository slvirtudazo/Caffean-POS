<?php

// Logout handler — clears the session and redirects to the homepage.

// Start the session.
session_start();

// Clear all session data.
$_SESSION = array();

// Expire the session cookie if one exists.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session.
session_destroy();

// Redirect to the homepage.
header("location: ../index.php");
exit();
