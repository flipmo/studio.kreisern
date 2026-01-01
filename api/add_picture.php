<?php
/**
 * Add Picture API
 * Uploads new picture, creates three sizes, stores metadata
 * 
 * Method: POST (multipart/form-data)
 * Parameters:
 *   - image (file, required): Image file
 *   - creator (string, required): Artist name
 *   - date (string, required): Date in YYYY-MM-DD format
 *   - project (string, required): Project name
 *   - color (string, optional): Dominant color
 *   - description (text, optional): Detailed description
 * 
 * Returns: JSON with success status and picture ID
 */

require_once 'config.php';
require_once 'image_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$creator = $_POST['creator'] ?? '';
$date = $_POST['date'] ?? '';
$project = $_POST['project'] ?? '';
$color = $_POST['color'] ?? '';
$description = $_POST['description'] ?? '';

// Validate required fields
if (empty($creator) || empty($date) || empty($project)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: creator, date, and project are required']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

// Handle file upload
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No image uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']);
    exit;
}

// Generate unique base filename
$baseFilename = uniqid() . '_' . time();
$extension = getImageExtension($file['tmp_name']);

// Define filenames for three sizes
$filename_thumb = $baseFilename . '_thumb.' . $extension;
$filename_medium = $baseFilename . '_medium.' . $extension;
$filename_original = $baseFilename . '_original.' . $extension;

// Define paths
$path_thumb = '../uploads/thumb/' . $filename_thumb;
$path_medium = '../uploads/medium/' . $filename_medium;
$path_original = '../uploads/original/' . $filename_original;

// Create directories if they don't exist
$directories = ['../uploads/thumb', '../uploads/medium', '../uploads/original'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Create three versions
$success = true;
$errors = [];

// Thumbnail: 300px max dimension
if (!resizeImage($file['tmp_name'], $path_thumb, 300, 300, 85)) {
    $success = false;
    $errors[] = 'Failed to create thumbnail';
}

// Medium: 1200px max dimension
if (!resizeImage($file['tmp_name'], $path_medium, 1200, 1200, 85)) {
    $success = false;
    $errors[] = 'Failed to create medium size';
}

// Original: 2400px max dimension (space-saving compression)
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

// Insert into database
$stmt = $conn->prepare("INSERT INTO pictures (filename_thumb, filename_medium, filename_original, creator, date, project, color, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $filename_thumb, $filename_medium, $filename_original, $creator, $date, $project, $color, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'id' => $conn->insert_id,
        'filenames' => [
            'thumb' => $filename_thumb,
            'medium' => $filename_medium,
            'original' => $filename_original
        ]
    ]);
} else {
    // Delete uploaded files if database insert fails
    @unlink($path_thumb);
    @unlink($path_medium);
    @unlink($path_original);
    
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>