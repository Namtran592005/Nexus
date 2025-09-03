<?php
require_once 'config.php';
require_once 'auth_check.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $redirect_url = BASE_URL . '/index.php?view=trash';

    try {
        $stmt = $pdo->prepare("SELECT name, parent_id FROM file_system WHERE id = ? AND is_deleted = 1");
        $stmt->execute([$id]);
        $itemToRestore = $stmt->fetch();

        if (!$itemToRestore) {
            redirect_with_message($redirect_url, 'Item not found in trash.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0");
        $stmt->execute([$itemToRestore['name'], $itemToRestore['parent_id']]);
        $newName = $itemToRestore['name'];
        if ($stmt->fetch()) {
            $info = pathinfo($itemToRestore['name']);
            $newName = $info['filename'] . '_' . time() . (isset($info['extension']) ? '.' . $info['extension'] : '');
        }

        $stmt = $pdo->prepare("UPDATE file_system SET is_deleted = 0, deleted_at = NULL, name = ? WHERE id = ?");
        $stmt->execute([$newName, $id]);
        
        redirect_with_message(BASE_URL . '/index.php?view=browse', 'Item restored successfully.', 'success');

    } catch (PDOException $e) {
        error_log("Restore failed: " . $e->getMessage());
        redirect_with_message($redirect_url, 'Error during restore.', 'danger');
    }
}
redirect_with_message(BASE_URL . '/index.php?view=trash', 'Invalid request.', 'danger');
?>