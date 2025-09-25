<?php

echo "<h1>Kiểm tra cài đặt SQLite cho PHP</h1>";

if (class_exists("PDO")) {
    echo "<p style='color: green;'>✔ PDO đã được cài đặt.</p>";
    $drivers = PDO::getAvailableDrivers();
    if (in_array("sqlite", $drivers)) {
        echo "<p style='color: green;'>✔ Trình điều khiển PDO cho SQLite (pdo_sqlite) đã được kích hoạt!</p>";
        echo "<p><strong>Bạn đã sẵn sàng để sử dụng phiên bản SQLite của ứng dụng.</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Lỗi: Trình điều khiển PDO cho SQLite (pdo_sqlite) CHƯA được kích hoạt.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Lỗi: PDO CHƯA được cài đặt. Đây là yêu cầu cơ bản.</p>";
}

echo "<hr>";
phpinfo();

?>
