<?php
require_once 'config.php';

$sql = "SELECT id, filename, creator, date, project, color FROM pictures ORDER BY date DESC";
$result = $conn->query($sql);

$pictures = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pictures[] = array(
            'id' => $row['id'],
            'url' => '../uploads/' . $row['filename'],
            'creator' => $row['creator'],
            'date' => $row['date'],
            'project' => $row['project'],
            'color' => $row['color']
        );
    }
}

echo json_encode($pictures);
$conn->close();
?>