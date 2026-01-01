<?php
/**
 * Update Picture API
 * Updates picture metadata and optionally replaces image
 * 
 * Method: POST (multipart/form-data)
 * Parameters:
 *   - id (int, required): Picture ID
 *   - creator (string, required): Artist name
 *   - date (string, required): Date in YYYY-MM-DD format
 *   - project (string, required): Project name
 *   - color (string, optional): Dominant color
 *   - description (text, optional): Detailed description
 *   - image (file, optional): New image file (only if replacing)
 * 
 * Returns: JSON with success status
 */

require_once 'config.php';
require_once 'image_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id = $_POST['id'] ?? '';
$creator = $_POST['creator'] ?? '';
$date = $_POST['date'] ?? '';
$project = $_POST['project'] ?? '';
$color = $_POST['color'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($id) || empty($creator) || empty($date) || empty($project)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: id, creator, date, and project are required']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

$imageReplaced = false;

// Check if new image is uploaded
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']);
        exit;
    }
    
    // Get old filenames to delete
    $stmt = $conn->prepare("SELECT filename_thumb, filename_medium, filename_original FROM pictures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_files = $result->fetch_assoc();
    $stmt->close();
    
    if (!$old_files) {
        http_response_code(404);
        echo json_encode(['error' => 'Picture not found']);
        exit;
    }
    
    // Generate new filenames
    $baseFilename = uniqid() . '_' . time();
    $extension = getImageExtension($file['tmp_name']);
    
    $filename_thumb = $baseFilename . '_thumb.' . $extension;
    $filename_medium = $baseFilename . '_medium.' . $extension;
    $filename_original = $baseFilename . '_original.' . $extension;
    
    $path_thumb = '../uploads/thumb/' . $filename_thumb;
    $path_medium = '../uploads/medium/' . $filename_medium;
    $path_original = '../uploads/original/' . $filename_original;
    
    // Create three versions
    $success = true;
    $errors = [];
    
    if (!resizeImage($file['tmp_name'], $path_thumb, 300, 300, 85)) {
        $success = false;
        $errors[] = 'Failed to create thumbnail';
    }
    
    if (!resizeImage($file['tmp_name'], $path_medium, 1200, 1200, 85)) {
        $success = false;
        $errors[] = 'Failed to create medium size';
    }
    
    if (!resizeImage($file['tmp_name'], $path_original, 2400, 2400, 85)) {
        $success = false;
        $errors[] = 'Failed to create original size';
    }
    
    if (!$success) {
        // Clean up any created files
        @unlink($path_thumb);
        @unlink($path_medium);
        @unlink($path_original);
        
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to process image',
            'details' => $errors
        ]);
        exit;
    }
    
    // Delete old files
    @unlink('../uploads/thumb/' . $old_files['filename_thumb']);
    @unlink('../uploads/medium/' . $old_files['filename_medium']);
    @unlink('../uploads/original/' . $old_files['filename_original']);
    
    // Update database with new filenames
    $stmt = $conn->prepare("UPDATE pictures SET filename_thumb = ?, filename_medium = ?, filename_original = ?, creator = ?, date = ?, project = ?, color = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssssssssi", $filename_thumb, $filename_medium, $filename_original, $creator, $date, $project, $color, $description, $id);
    
    $imageReplaced = true;
} else {
    // Update only metadata (no new image)
    $stmt = $conn->prepare("UPDATE pictures SET creator = ?, date = ?, project = ?, color = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $creator, $date, $project, $color, $description, $id);
}

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Picture not found or no changes made']);
    } else {
        echo json_encode([
            'success' => true,
            'updated' => [
                'metadata' => true,
                'image' => $imageReplaced
            ]
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>