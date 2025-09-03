<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

$redirect_url = BASE_URL . '/index.php?view=trash';

try {
    $stmt = $pdo->query("DELETE FROM file_system WHERE is_deleted = 1");
    redirect_with_message($redirect_url, "Trash has been emptied successfully.", 'success');

} catch (PDOException $e) {
    error_log("Empty trash failed: " . $e->getMessage());
    redirect_with_message($redirect_url, "Error emptying trash.", 'danger');
}
?>