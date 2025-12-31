<?php
// init.php - Run this once to create your database table
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS pictures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    creator VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    project VARCHAR(100) NOT NULL,
    color VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_creator (creator),
    INDEX idx_date (date),
    INDEX idx_project (project),
    INDEX idx_color (color)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table 'pictures' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>