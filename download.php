<?php
require_once 'config.php';
require_once 'auth_check.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT name, mime_type, size, content FROM file_system WHERE id = ? AND is_deleted = 0 AND type = 'file'");
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if ($file) {
            $mimeType = $file['mime_type'] ?: 'application/octet-stream';
            
            header('Content-Description: File Transfer');
            if (isset($_GET['inline']) && $_GET['inline'] === 'true') {
                header('Content-Disposition: inline; filename="' . basename($file['name']) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
            }
            header('Content-Type: ' . $mimeType);
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
        error_log("Download failed: " . $e->getMessage());
        http_response_code(500);
        echo "Server error while retrieving the file.";
        exit();
    }
}
http_response_code(404);
echo "File not found or inaccessible.";
exit();
?>