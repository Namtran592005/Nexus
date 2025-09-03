<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['new_name'])) {
    $id = (int)$_POST['id'];
    $newNameFromForm = trim($_POST['new_name']);

    $stmt = $pdo->prepare("SELECT name, type, parent_id, is_deleted FROM file_system WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        redirect_with_message(BASE_URL . '/index.php', 'Item not found.', 'danger');
    }

    $redirectView = $item['is_deleted'] ? 'trash' : 'browse';
    $parentPath = $item['is_deleted'] ? '' : getPathByItemId($pdo, $item['parent_id']);
    $redirect_url = BASE_URL . '/index.php?view=' . $redirectView . '&path=' . urlencode($parentPath);
    
    $sanitizedFilename = preg_replace('/[^a-zA-Z0-9 _.-]/', '', $newNameFromForm);
    if (empty($sanitizedFilename)) {
        redirect_with_message($redirect_url, 'The new name is invalid.', 'danger');
    }

    $finalNewName = $sanitizedFilename;
    if ($item['type'] === 'file') {
        $originalExtension = pathinfo($item['name'], PATHINFO_EXTENSION);
        if (!empty($originalExtension)) {
            $finalNewName = $sanitizedFilename . '.' . $originalExtension;
        }
    }

    $stmt = $pdo->prepare("SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND id != ? AND is_deleted = ?");
    $stmt->execute([$finalNewName, $item['parent_id'], $id, $item['is_deleted']]);
    if ($stmt->fetch()) {
        redirect_with_message($redirect_url, 'An item named "' . htmlspecialchars($finalNewName) . '" already exists.', 'warning');
    }

    try {
        $stmt = $pdo->prepare("UPDATE file_system SET name = ? WHERE id = ?");
        $stmt->execute([$finalNewName, $id]);
        redirect_with_message($redirect_url, 'Rename successful.', 'success');
    } catch(PDOException $e) {
        error_log("Rename failed: " . $e->getMessage());
        redirect_with_message($redirect_url, 'Could not rename the item.', 'danger');
    }
}
redirect_with_message(BASE_URL . '/index.php', 'Invalid request.', 'danger');
?>