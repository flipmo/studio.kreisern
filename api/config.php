<?php
// config.php - Update these with your hosting credentials
$servername = "localhost";  // Usually 'localhost'
$username = "web322_3";  // From your hosting control panel
$password = "vup!xka8YFC!fnw5xpz";  // From your hosting control panel
$dbname = "web322_db3";        // From your hosting control panel

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// CORS headers for API access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
?>