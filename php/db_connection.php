<?php

// Database credentials
$host = "localhost";
$user = "root";
$pass = "";
$db   = "coffee_shop_db";

// Open the database connection.
$conn = mysqli_connect($host, $user, $pass, $db);

// Abort if the connection fails.
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

// Use utf8mb4 for full Unicode and emoji support.
mysqli_set_charset($conn, "utf8mb4");

// Start the session once per request.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
