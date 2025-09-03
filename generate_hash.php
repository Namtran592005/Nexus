<?php
// Mật khẩu bạn muốn mã hóa
$passwordToHash = 'your_strong_password_here'; // <-- THAY MẬT KHẨU CỦA BẠN VÀO ĐÂY

// Mã hóa mật khẩu bằng thuật toán an toàn của PHP
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);

// In ra chuỗi đã mã hóa để bạn sao chép
echo "Mật khẩu của bạn là: " . htmlspecialchars($passwordToHash) . "<br>";
echo "Chuỗi mã hóa (sao chép và dán vào config.php hoặc users.php):<br>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hashedPassword) . "</textarea>";
?>