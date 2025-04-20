<?php
// Railway MySQL Credentials
$host = "centerbeam.proxy.rlwy.net"; // Replace with your actual Railway host
$port = 46637; // Replace with the Railway port
$user = "root"; // Or your Railway-generated username
$password = "zeAxeZUrCXRasGgPorApVkRTkleygjzy"; // Railway-generated password
$dbname = "railway"; // Usually 'railway' by default

// Create MySQL connection
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set character encoding
$conn->set_charset("utf8mb4");
?>
