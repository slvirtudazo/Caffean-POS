<?php
/**
 * Database Configuration File
 * This file establishes the connection to the MySQL database for the Purge Coffee Shop system.
 * It uses MySQLi extension for secure database operations.
 */

// Database connection parameters
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'coffee_shop_db');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set charset to utf8mb4 for proper handling of special characters
mysqli_set_charset($conn, "utf8mb4");

// Session configuration for user authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>