<?php
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

// Get filename before deleting
$stmt = $conn->prepare("SELECT filename FROM pictures WHERE id = ?");
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

// Delete from database
$stmt = $conn->prepare("DELETE FROM pictures WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Delete file
    $file_path = '../uploads/' . $picture['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>