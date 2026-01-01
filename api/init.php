<?php
/**
 * Database Initialization Script
 * Run this file ONCE to create the pictures table
 * After running successfully, DELETE or RENAME this file for security
 */

require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS pictures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename_thumb VARCHAR(255) NOT NULL COMMENT 'Thumbnail filename (300px max)',
    filename_medium VARCHAR(255) NOT NULL COMMENT 'Medium size filename (1200px max)',
    filename_original VARCHAR(255) NOT NULL COMMENT 'Original size filename (2400px max, compressed)',
    creator VARCHAR(100) NOT NULL COMMENT 'Artist/creator name',
    date DATE NOT NULL COMMENT 'Picture date',
    project VARCHAR(100) NOT NULL COMMENT 'Project name',
    color VARCHAR(50) DEFAULT NULL COMMENT 'Dominant color (optional)',
    description TEXT DEFAULT NULL COMMENT 'Detailed description (optional)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    INDEX idx_creator (creator),
    INDEX idx_date (date),
    INDEX idx_project (project),
    INDEX idx_color (color)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Picture gallery metadata'";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'pictures' created successfully<br>";
    echo "<br>";
    echo "Table structure:<br>";
    echo "- id (INT, AUTO_INCREMENT, PRIMARY KEY)<br>";
    echo "- filename_thumb (VARCHAR 255) - Thumbnail image<br>";
    echo "- filename_medium (VARCHAR 255) - Medium size image<br>";
    echo "- filename_original (VARCHAR 255) - Original size image<br>";
    echo "- creator (VARCHAR 100) - Artist name<br>";
    echo "- date (DATE) - Picture date<br>";
    echo "- project (VARCHAR 100) - Project name<br>";
    echo "- color (VARCHAR 50, NULLABLE) - Dominant color<br>";
    echo "- description (TEXT, NULLABLE) - Detailed description<br>";
    echo "- created_at (TIMESTAMP) - Creation time<br>";
    echo "- updated_at (TIMESTAMP) - Last update time<br>";
    echo "<br>";
    echo "Indexes created on: creator, date, project, color<br>";
    echo "<br>";
    echo "<strong style='color: red;'>IMPORTANT: Please DELETE or RENAME this file (init.php) now for security!</strong><br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
    echo "Please check your database credentials in config.php";
}

$conn->close();
?>