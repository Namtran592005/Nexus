<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_name'])) {
    $folderName = trim($_POST['folder_name']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : ROOT_FOLDER_ID;
    $currentRelativePath = getPathByItemId($pdo, $parent_id);
    $redirect_url = BASE_URL . '/index.php?view=browse&path=' . urlencode($currentRelativePath);

    $folderName = preg_replace('/[^a-zA-Z0-9 _.-]/', '', $folderName);
    if (empty($folderName)) {
        redirect_with_message($redirect_url, 'Invalid or empty folder name.', 'danger');
    }

    $stmt = $pdo->prepare("SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0");
    $stmt->execute([$folderName, $parent_id]);
    if ($stmt->fetch()) {
        redirect_with_message($redirect_url, 'Folder "' . htmlspecialchars($folderName) . '" already exists.', 'warning');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO file_system (parent_id, name, type, size) VALUES (?, ?, 'folder', 0)");
        if ($stmt->execute([$parent_id, $folderName])) {
            redirect_with_message($redirect_url, 'Folder "' . htmlspecialchars($folderName) . '" was created.', 'success');
        }
    } catch (PDOException $e) {
        error_log("New folder creation failed: " . $e->getMessage());
        redirect_with_message($redirect_url, 'Could not create the folder.', 'danger');
    }
}
redirect_with_message(BASE_URL . '/index.php', 'Invalid request.', 'danger');
?>