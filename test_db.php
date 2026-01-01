<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

$servername = "localhost";
$username = "web322_3";  // From your hosting control panel
$password = "vup!xka8YFC!fnw5xpz";  // From your hosting control panel
$dbname = "web322_db3";        // From your hosting control panel


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✓ Connected successfully!<br><br>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'pictures'");
if ($result->num_rows > 0) {
    echo "✓ Table 'pictures' exists<br><br>";
    
    // Check table structure
    $result = $conn->query("DESCRIBE pictures");
    echo "Table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})<br>";
    }
} else {
    echo "✗ Table 'pictures' does not exist!<br>";
    echo "Please run init.php again.";
}

$conn->close();
?>