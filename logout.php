<?php
require_once 'config.php'; // Để dùng hàm redirect_with_message

if (AUTH_ENABLED === false) {
    header('Location: index.php');
    exit;
}

session_start();
$_SESSION = []; // Xóa tất cả biến session
session_destroy(); // Hủy session

redirect_with_message('login.php', 'Bạn đã đăng xuất thành công.', 'success');
?>