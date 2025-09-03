<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Unknown error occurred.'];

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, mime_type, size, modified_at, content FROM file_system WHERE id = ? AND type = 'file' AND is_deleted = 0");
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if (!$file) {
            $response = ['success' => false, 'message' => 'File not found or cannot be accessed.'];
            echo json_encode($response);
            exit();
        }

        $mimeType = $file['mime_type'] ?: 'application/octet-stream';
        $fileUrl = BASE_URL . '/download.php?id=' . $file['id'] . '&inline=true';

        if (isImage($mimeType)) {
            $response = ['success' => true, 'type' => 'image', 'data' => $fileUrl, 'mime_type' => $mimeType];
        } elseif (isVideo($mimeType)) {
            $response = ['success' => true, 'type' => 'video', 'data' => $fileUrl, 'mime_type' => $mimeType];
        } elseif (isAudio($mimeType)) {
            $response = ['success' => true, 'type' => 'audio', 'data' => $fileUrl, 'mime_type' => $mimeType];
        } elseif (isPdf($mimeType)) {
            $response = ['success' => true, 'type' => 'pdf', 'data' => $fileUrl, 'mime_type' => $mimeType];
        } elseif (isTextOrCode($mimeType) && $file['size'] < 2 * 1024 * 1024) {
            $response = [
                'success' => true, 'type' => 'code', 'data' => $file['content'],
                'mime_type' => $mimeType, 'language' => guessCodeLanguage($file['name'])
            ];
        } else {
            $response = [
                'success' => true, 'type' => 'details',
                'data' => [
                    'name' => $file['name'],
                    'kind' => strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)) . ' File',
                    'size' => formatBytes($file['size']),
                    'modified' => date('d/m/Y H:i', strtotime($file['modified_at'])),
                    'path' => getPathByItemId($pdo, $file['id']),
                    'mime_type' => $mimeType
                ],
                'message' => 'Preview is not available for this file type.'
            ];
        }

    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Server error while fetching preview data.'];
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid preview request.'];
}

echo json_encode($response);
exit();
?>