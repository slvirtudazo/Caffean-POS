<?php

// Database credentials
$host = "localhost";
$user = "root";
$pass = "";
$db   = "coffee_shop_db";

// Open connection
$conn = mysqli_connect($host, $user, $pass, $db);

// Abort on failure
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

// Use utf8mb4 for full Unicode and emoji support
mysqli_set_charset($conn, "utf8mb4");

// Start session once per request
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}