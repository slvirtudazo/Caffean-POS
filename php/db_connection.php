<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "coffee_shop_db";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    die("ERROR: Database connection failed! " . mysqli_connect_error());
}

// For handling special characters/symbols
mysqli_set_charset($conn, "utf8mb4");

// Check if a session is already active to avoid errors
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// echo "Connected successfully!"; 

?>