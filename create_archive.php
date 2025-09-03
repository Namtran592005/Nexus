<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

function addFolderToZip($pdo, $folderId, $zip, $parentPath) {
    $stmt = $pdo->prepare("SELECT id, name, type FROM file_system WHERE parent_id = ? AND is_deleted = 0");
    $stmt->execute([$folderId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $localPath = $parentPath . $item['name'];
        if ($item['type'] === 'folder') {
            $zip->addEmptyDir($localPath);
            addFolderToZip($pdo, $item['id'], $zip, $localPath . '/');
        } else {
            $fileStmt = $pdo->prepare("SELECT content FROM file_system WHERE id = ?");
            $fileStmt->execute([$item['id']]);
            $fileContent = $fileStmt->fetchColumn();
            if ($fileContent !== false) {
                $zip->addFromString($localPath, $fileContent);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids'])) {
    $itemIds = array_map('intval', $_POST['ids']);
    
    $zip = new ZipArchive();
    $zipFileName = 'archive_' . time() . '.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die("Could not open archive");
    }

    try {
        $placeholders = rtrim(str_repeat('?,', count($itemIds)), ',');
        $stmt = $pdo->prepare("SELECT id, name, type FROM file_system WHERE id IN ($placeholders) AND is_deleted = 0");
        $stmt->execute($itemIds);
        $itemsToArchive = $stmt->fetchAll();

        foreach ($itemsToArchive as $item) {
            if ($item['type'] === 'folder') {
                $zip->addEmptyDir($item['name']);
                addFolderToZip($pdo, $item['id'], $zip, $item['name'] . '/');
            } else {
                $fileStmt = $pdo->prepare("SELECT content FROM file_system WHERE id = ?");
                $fileStmt->execute([$item['id']]);
                $fileContent = $fileStmt->fetchColumn();
                 if ($fileContent !== false) {
                    $zip->addFromString($item['name'], $fileContent);
                }
            }
        }
        
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFilePath));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        ob_clean();
        flush();
        readfile($zipFilePath);
        
        // Clean up the temporary file
        unlink($zipFilePath);
        exit();

    } catch (PDOException $e) {
        error_log("Archive creation failed: " . $e->getMessage());
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
        die("Database error during archive creation.");
    }
} else {
    redirect_with_message(BASE_URL . '/index.php', 'No items selected for download.', 'danger');
}
?>