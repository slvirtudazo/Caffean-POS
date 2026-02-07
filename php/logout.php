<?php
/**
 * Purge Coffee Shop - Logout Script
 * This file handles user logout by destroying the session and redirecting to the homepage.
 * It ensures proper cleanup of session data for security purposes.
 */

// Start the session to access session variables
session_start();

// Unset all session variables to clear user data
$_SESSION = array();

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session completely
session_destroy();

// Redirect to the homepage
header("location: ../index.php");
exit();
?>