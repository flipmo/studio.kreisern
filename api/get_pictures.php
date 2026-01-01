<?php
/**
 * Get Pictures API
 * Retrieves all pictures with metadata and three image sizes
 * 
 * Method: GET
 * Returns: JSON array of pictures
 */

require_once 'config.php';

$sql = "SELECT 
    id, 
    filename_thumb, 
    filename_medium, 
    filename_original,
    creator, 
    date, 
    project, 
    color,
    description
FROM pictures 
ORDER BY date DESC, id DESC";

$result = $conn->query($sql);

$pictures = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pictures[] = array(
            'id' => (int)$row['id'],
            'url_thumb' => 'uploads/thumb/' . $row['filename_thumb'],
            'url_medium' => 'uploads/medium/' . $row['filename_medium'],
            'url_original' => 'uploads/original/' . $row['filename_original'],
            'creator' => $row['creator'],
            'date' => $row['date'],
            'project' => $row['project'],
            'color' => $row['color'],
            'description' => $row['description']
        );
    }
}

echo json_encode($pictures);
$conn->close();
?>