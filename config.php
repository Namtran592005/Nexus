<?php
session_start();

// --- Cấu hình chính ---
define('DB_FILE', __DIR__ . '/database.sqlite'); 
define('ROOT_FOLDER_ID', 1);
define('TOTAL_STORAGE_GB', 5);
define('MAX_FILE_SIZE', 4000 * 1024 * 1024);
define('APP_NAME', 'Nexus Drive');

// --- Cấu hình Xác thực ---
define('AUTH_ENABLED', false); // <<< THÊM CÔNG TẮC NÀY VÀO
// Đặt thành 'true' để BẬT đăng nhập (khi đưa lên host)
// Đặt thành 'false' để TẮT đăng nhập (khi làm ở nhà/localhost)

// --- Cấu hình Xác thực ---
define('ALLOW_REGISTRATION', false); // Đặt thành 'true' để cho phép đăng ký, 'false' để tắt
define('USERS_FILE', __DIR__ . '/users.php'); // File để lưu trữ thông tin người dùng

// Tự động xác định BASE_URL
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $protocol = 'https://';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";
}
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
define('BASE_URL', $protocol . $host . $path);


// --- Hàm tiện ích ---
function redirect_with_message($url, $message, $type = 'info') {
    $_SESSION['message'] = [ 'text' => $message, 'type' => $type ];
    header("Location: " . $url);
    exit();
}

// --- Logic Tải Người dùng ---
// Kiểm tra và tạo file users.php nếu chưa có
if (!file_exists(USERS_FILE)) {
    $initial_users = [
        'admin' => password_hash('admin', PASSWORD_DEFAULT) // Mật khẩu mặc định là 'admin'
    ];
    $content = "<?php\n\nreturn " . var_export($initial_users, true) . ";\n";
    if (file_put_contents(USERS_FILE, $content) === false) {
        die('Lỗi: Không thể tạo file người dùng. Vui lòng kiểm tra quyền ghi của thư mục.');
    }
}
// Tải danh sách người dùng từ file
$auth_users = require USERS_FILE;
define('AUTH_USERS', $auth_users);


// --- Logic Kết nối và Tự Khởi tạo Cơ sở dữ liệu SQLite ---
try {
    $db_exists = file_exists(DB_FILE) && filesize(DB_FILE) > 0;
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    if (!$db_exists) {
        $pdo->beginTransaction();
        $pdo->exec("CREATE TABLE `file_system` (`id` INTEGER PRIMARY KEY, `parent_id` INTEGER, `name` TEXT NOT NULL, `type` TEXT NOT NULL, `mime_type` TEXT, `size` INTEGER DEFAULT 0, `content` BLOB, `is_deleted` INTEGER NOT NULL DEFAULT 0, `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, `modified_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, `deleted_at` TEXT, FOREIGN KEY (`parent_id`) REFERENCES `file_system` (`id`) ON DELETE CASCADE);");
        $pdo->exec("CREATE TRIGGER update_file_system_modified_at AFTER UPDATE ON file_system FOR EACH ROW BEGIN UPDATE file_system SET modified_at = CURRENT_TIMESTAMP WHERE id = OLD.id; END;");
        $pdo->exec("CREATE TABLE `share_links` (`id` TEXT PRIMARY KEY, `file_id` INTEGER NOT NULL, `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`file_id`) REFERENCES `file_system` (`id`) ON DELETE CASCADE);");
        $pdo->exec("CREATE INDEX idx_parent_deleted ON file_system (parent_id, is_deleted);");
        $pdo->exec("INSERT INTO `file_system` (`id`, `name`, `type`, `parent_id`) VALUES (1, 'ROOT', 'folder', NULL)");
        $pdo->commit();
    }
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'unable to open database file') !== false) {
         die('<strong>Lỗi Cấu hình:</strong> Không thể tạo hoặc mở file cơ sở dữ liệu. Vui lòng đảm bảo thư mục của ứng dụng có quyền ghi.');
    } else {
        die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
    }
}
?>