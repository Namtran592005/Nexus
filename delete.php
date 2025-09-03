<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $itemsToDelete = $input['ids'] ?? [];

    if (empty($itemsToDelete)) {
        echo json_encode(['success' => false, 'message' => 'No items selected.']);
        exit();
    }
    
    $ids = array_map('intval', $itemsToDelete);
    $placeholders = rtrim(str_repeat('?,', count($ids)), ',');

    try {
        $stmt = $pdo->prepare("UPDATE file_system SET is_deleted = 1, deleted_at = datetime('now') WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $successCount = $stmt->rowCount();
        echo json_encode(['success' => true, 'message' => "Moved $successCount item(s) to trash."]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error during deletion: ' . $e->getMessage()]);
    }
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $forceDelete = isset($_GET['force_delete']) && $_GET['force_delete'] === 'true';

    $stmt = $pdo->prepare("SELECT type, is_deleted, parent_id FROM file_system WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        redirect_with_message(BASE_URL . '/index.php', 'Item not found.', 'danger');
    }
    
    $redirectView = $item['is_deleted'] ? 'trash' : 'browse';
    $parentPath = $item['is_deleted'] ? '' : getPathByItemId($pdo, $item['parent_id']);
    $redirect_url = BASE_URL . '/index.php?view=' . $redirectView . '&path=' . urlencode($parentPath);

    try {
        if ($forceDelete) {
            if ($item['type'] === 'folder') {
                deleteFolderRecursiveDb($pdo, $id);
            } else {
                $deleteStmt = $pdo->prepare("DELETE FROM file_system WHERE id = ?");
                $deleteStmt->execute([$id]);
            }
            redirect_with_message($redirect_url, "Item permanently deleted.", 'success');
        } else {
            $updateStmt = $pdo->prepare("UPDATE file_system SET is_deleted = 1, deleted_at = datetime('now') WHERE id = ?");
            $updateStmt->execute([$id]);
            redirect_with_message($redirect_url, "Item moved to trash.", 'success');
        }
    } catch (PDOException $e) {
        error_log("Delete failed: " . $e->getMessage());
        redirect_with_message($redirect_url, 'Could not perform delete operation.', 'danger');
    }
}
redirect_with_message(BASE_URL . '/index.php', 'Invalid delete request.', 'danger');
?>