<?php
// Tệp này được gọi sau config.php

// 1. Kiểm tra công tắc tổng trước tiên
if (AUTH_ENABLED === false) {
    // Nếu xác thực bị TẮT, hãy tạo một session giả để ứng dụng hoạt động bình thường
    if (session_status() === PHP_SESSION_NONE) {
        // Đảm bảo session đã được khởi tạo
        session_start();
    }
    $_SESSION['is_logged_in'] = true;
    $_SESSION['username'] = 'Local User'; // Tên người dùng mặc định cho chế độ local
    
    // Bỏ qua tất cả các bước kiểm tra khác và cho phép truy cập
    return;
}

// 2. Nếu xác thực được BẬT, chạy logic kiểm tra như cũ
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>