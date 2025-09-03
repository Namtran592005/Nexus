<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_share_link'])) {
    header('Content-Type: application/json');
    $file_id = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;

    if ($file_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid file ID.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM share_links WHERE file_id = ?");
        $stmt->execute([$file_id]);
        $existing = $stmt->fetch();
        if ($existing) {
            echo json_encode(['success' => true, 'share_id' => $existing['id']]);
            exit();
        }

        $shareId = bin2hex(random_bytes(8));
        
        $stmt = $pdo->prepare("INSERT INTO share_links (id, file_id) VALUES (?, ?)");
        $stmt->execute([$shareId, $file_id]);

        echo json_encode(['success' => true, 'share_id' => $shareId]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

if (isset($_GET['id'])) {
    $shareId = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT fs.name, fs.mime_type, fs.size, fs.content 
            FROM file_system fs
            JOIN share_links sl ON fs.id = sl.file_id
            WHERE sl.id = ? AND fs.is_deleted = 0
        ");
        $stmt->execute([$shareId]);
        $file = $stmt->fetch();

        if ($file) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $file['size']);
            ob_clean();
            flush();
            echo $file['content'];
            exit();
        }
    } catch (PDOException $e) {
        // Fallthrough to 404
    }
}
http_response_code(404);
echo '<h1>404 Not Found</h1><p>The share link is invalid or has expired.</p>';
exit();
?>