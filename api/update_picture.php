<?php
require_once 'config.php';

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

if (empty($id) || empty($creator) || empty($date) || empty($project)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// If new image is uploaded
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Get old filename to delete
    $stmt = $conn->prepare("SELECT filename FROM pictures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_file = $result->fetch_assoc();
    $stmt->close();
    
    // Upload new image
    $file = $_FILES['image'];
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $upload_path = '../uploads/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Delete old file
        if ($old_file && file_exists('../uploads/' . $old_file['filename'])) {
            unlink('../uploads/' . $old_file['filename']);
        }
        
        // Update with new filename
        $stmt = $conn->prepare("UPDATE pictures SET filename = ?, creator = ?, date = ?, project = ?, color = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $filename, $creator, $date, $project, $color, $id);
    }
} else {
    // Update without changing image
    $stmt = $conn->prepare("UPDATE pictures SET creator = ?, date = ?, project = ?, color = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $creator, $date, $project, $color, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>