<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id']) && isset($_POST['destination_id'])) {
    $itemId = (int)$_POST['item_id'];
    $destinationId = (int)$_POST['destination_id'];

    $stmt = $pdo->prepare("SELECT name, type, parent_id FROM file_system WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Source item not found.']);
        exit();
    }
    if ($itemId === $destinationId) {
        echo json_encode(['success' => false, 'message' => 'Cannot move an item into itself.']);
        exit();
    }
    if ($item['parent_id'] === $destinationId) {
        echo json_encode(['success' => false, 'message' => 'Item is already in this folder.']);
        exit();
    }

    if ($item['type'] === 'folder') {
        $currentParent = $destinationId;
        while ($currentParent != ROOT_FOLDER_ID && $currentParent != null) {
            if ($currentParent == $itemId) {
                echo json_encode(['success' => false, 'message' => 'Cannot move a folder into its own subfolder.']);
                exit();
            }
            $stmt = $pdo->prepare("SELECT parent_id FROM file_system WHERE id = ?");
            $stmt->execute([$currentParent]);
            $currentParent = $stmt->fetchColumn();
        }
    }
    
    $stmt = $pdo->prepare("SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0");
    $stmt->execute([$item['name'], $destinationId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An item named "' . htmlspecialchars($item['name']) . '" already exists in the destination.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE file_system SET parent_id = ? WHERE id = ?");
        $stmt->execute([$destinationId, $itemId]);
        echo json_encode(['success' => true, 'message' => 'Item moved successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Server error during move operation.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>