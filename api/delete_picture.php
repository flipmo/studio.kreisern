<?php
/**
 * Delete Picture API
 * Deletes picture record and all three image files
 * 
 * Method: POST (JSON)
 * Body: {"id": 42}
 * 
 * Returns: JSON with success status
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing picture ID']);
    exit;
}

// Get filenames before deleting
$stmt = $conn->prepare("SELECT filename_thumb, filename_medium, filename_original FROM pictures WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$picture = $result->fetch_assoc();
$stmt->close();

if (!$picture) {
    http_response_code(404);
    echo json_encode(['error' => 'Picture not found']);
    exit;
}

// Delete from database first
$stmt = $conn->prepare("DELETE FROM pictures WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Delete all three image files
    $deleted_count = 0;
    
    $file_thumb = '../uploads/thumb/' . $picture['filename_thumb'];
    if (file_exists($file_thumb) && unlink($file_thumb)) {
        $deleted_count++;
    }
    
    $file_medium = '../uploads/medium/' . $picture['filename_medium'];
    if (file_exists($file_medium) && unlink($file_medium)) {
        $deleted_count++;
    }
    
    $file_original = '../uploads/original/' . $picture['filename_original'];
    if (file_exists($file_original) && unlink($file_original)) {
        $deleted_count++;
    }
    
    echo json_encode([
        'success' => true,
        'deleted_files' => $deleted_count,
        'expected_files' => 3
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>