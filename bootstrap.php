<?php
// =================================================================
// BOOTSTRAP FILE - CORE OF THE APPLICATION
// This file combines config.php, helpers.php, and auth_check.php
// =================================================================

// Tự động nén output (HTML, JSON, etc.) nếu trình duyệt hỗ trợ
if (substr_count($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip")) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

// --- 1. CONFIGURATION ---

session_start();

// --- Main Configuration ---
define("DB_FILE", __DIR__ . "/database/database.sqlite");
define("ROOT_FOLDER_ID", 1);
define("TOTAL_STORAGE_GB", 5);
define("MAX_FILE_SIZE", 4000 * 1024 * 1024);
define("APP_NAME", "Nexus Drive");

// --- Authentication Configuration ---
define("AUTH_ENABLED", true);
define("ALLOW_REGISTRATION", false);
define("USERS_FILE", __DIR__ . "/users.php");

// --- Auto-detect BASE_URL ---
if (
    isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
    $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https"
) {
    $protocol = "https://";
} else {
    $protocol =
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        $_SERVER["SERVER_PORT"] == 443
            ? "https://"
            : "http://";
}
$host = $_SERVER["HTTP_HOST"];
$path = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") . "/";
define("BASE_URL", $protocol . $host . $path);

// --- User Loading Logic ---
if (!file_exists(USERS_FILE)) {
    // Cấu trúc user mới hỗ trợ 2FA
    $initial_users = [
        'admin' => [
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'tfa_secret' => null,
            'tfa_enabled' => false,
        ]
    ];
    $content = "<?php\n\nreturn " . var_export($initial_users, true) . ";\n";
    if (file_put_contents(USERS_FILE, $content) === false) {
        die(
            "Error: Could not create user file. Please check folder write permissions."
        );
    }
}
$auth_users = require USERS_FILE;
define("AUTH_USERS", $auth_users);

// --- Database Connection and Initialization ---
if (!is_dir(__DIR__ . "/database")) {
    mkdir(__DIR__ . "/database", 0777, true);
}
try {
    $db_exists = file_exists(DB_FILE) && filesize(DB_FILE) > 0;
    $pdo = new PDO("sqlite:" . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
    $pdo->exec("PRAGMA auto_vacuum = FULL;");

    if (!$db_exists) {
        $pdo->beginTransaction();
        $pdo->exec(
            "CREATE TABLE `file_system` (`id` INTEGER PRIMARY KEY, `parent_id` INTEGER, `name` TEXT NOT NULL, `type` TEXT NOT NULL, `mime_type` TEXT, `size` INTEGER DEFAULT 0, `content` BLOB, `is_deleted` INTEGER NOT NULL DEFAULT 0, `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, `modified_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, `deleted_at` TEXT, FOREIGN KEY (`parent_id`) REFERENCES `file_system` (`id`) ON DELETE CASCADE);"
        );
        $pdo->exec(
            "CREATE TRIGGER update_file_system_modified_at AFTER UPDATE ON file_system FOR EACH ROW BEGIN UPDATE file_system SET modified_at = CURRENT_TIMESTAMP WHERE id = OLD.id; END;"
        );
        $pdo->exec(
            "CREATE TABLE `share_links` (`id` TEXT PRIMARY KEY, `file_id` INTEGER NOT NULL, `password` TEXT NULL, `expires_at` TEXT NULL, `allow_download` INTEGER NOT NULL DEFAULT 1, `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`file_id`) REFERENCES `file_system` (`id`) ON DELETE CASCADE);"
        );
        $pdo->exec(
            "CREATE INDEX idx_parent_deleted ON file_system (parent_id, is_deleted);"
        );
        $pdo->exec(
            "INSERT INTO `file_system` (`id`, `name`, `type`, `parent_id`) VALUES (1, 'ROOT', 'folder', NULL)"
        );
        $pdo->commit();
    } else {
        $stmt = $pdo->query("PRAGMA table_info(share_links)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array("password", $columns)) {
            $pdo->exec("ALTER TABLE share_links ADD COLUMN password TEXT NULL");
        }
        if (!in_array("expires_at", $columns)) {
            $pdo->exec(
                "ALTER TABLE share_links ADD COLUMN expires_at TEXT NULL"
            );
        }
        if (!in_array("allow_download", $columns)) {
            $pdo->exec(
                "ALTER TABLE share_links ADD COLUMN allow_download INTEGER NOT NULL DEFAULT 1"
            );
        }
    }

    // Luôn chạy lệnh này để đảm bảo bảng login_attempts tồn tại.
    // CREATE TABLE IF NOT EXISTS sẽ không làm gì nếu bảng đã có.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `login_attempts` (
            `ip_address` TEXT NOT NULL,
            `last_attempt_at` INTEGER NOT NULL,
            `failed_attempts` INTEGER NOT NULL DEFAULT 1,
            PRIMARY KEY (`ip_address`)
        );"
    );

} catch (\PDOException $e) {
    if (strpos($e->getMessage(), "unable to open database file") !== false) {
        die(
            "<strong>Configuration Error:</strong> Cannot create or open the database file. Please ensure the application directory is writable."
        );
    } else {
        die("Database connection error: " . $e->getMessage());
    }
}

// --- 2. HELPER FUNCTIONS ---

function redirect_with_message($url, $message, $type = "info")
{
    $_SESSION["message"] = ["text" => $message, "type" => $type];
    header("Location: " . $url);
    exit();
}

function formatBytes($bytes, $precision = 2)
{
    if ($bytes === null || $bytes == 0) {
        return "0 B";
    }
    $units = ["B", "KB", "MB", "GB", "TB"];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= 1 << 10 * $pow;
    return round($bytes, $precision) . " " . $units[$pow];
}

function getFileIcon($fileName, $isFolder = false)
{
    if ($isFolder) {
        return ["icon" => "fa-folder", "color" => "#5ac8fa"];
    }
    
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $icon = "fa-file";
    $color = "#8a8a8e";

    switch ($extension) {
        case 'pdf': $icon = 'fa-file-pdf'; $color = '#e62e2e'; break;
        case 'doc': case 'docx': $icon = 'fa-file-word'; $color = '#2a5699'; break;
        case 'xls': case 'xlsx': case 'csv': $icon = 'fa-file-excel'; $color = '#217346'; break;
        case 'ppt': case 'pptx': $icon = 'fa-file-powerpoint'; $color = '#d24726'; break;
        case 'txt': case 'rtf': $icon = 'fa-file-alt'; $color = '#a0a0a5'; break;
        case 'zip': case 'rar': case '7z': case 'tar': case 'gz': $icon = 'fa-file-archive'; $color = '#f0ad4e'; break;
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': case 'webp': case 'heic': case 'tiff': $icon = 'fa-file-image'; $color = '#5cb85c'; break;
        case 'svg': $icon = 'fa-file-image'; $color = '#ffb13b'; break;
        case 'psd': $icon = 'fa-file-image'; $color = '#3498db'; break;
        case 'ai': $icon = 'fa-file-image'; $color = '#f39c12'; break;
        case 'fig': $icon = 'fa-file-image'; $color = '#a259ff'; break;
        case 'mp3': case 'wav': case 'aac': case 'flac': case 'm4a': $icon = 'fa-file-audio'; $color = '#9b59b6'; break;
        case 'mp4': case 'mov': case 'avi': case 'mkv': case 'webm': $icon = 'fa-file-video'; $color = '#e74c3c'; break;
        case 'html': case 'htm': $icon = 'fa-file-code'; $color = '#e44d26'; break;
        case 'css': case 'scss': case 'sass': $icon = 'fa-file-code'; $color = '#264de4'; break;
        case 'js': case 'ts': case 'jsx': case 'tsx': $icon = 'fa-file-code'; $color = '#f0db4f'; break;
        case 'json': $icon = 'fa-file-code'; $color = '#8a8a8e'; break;
        case 'xml': $icon = 'fa-file-code'; $color = '#ff6600'; break;
        case 'md': $icon = 'fa-file-alt'; $color = '#34495e'; break;
        case 'php': $icon = 'fa-file-code'; $color = '#8892be'; break;
        case 'py': $icon = 'fa-file-code'; $color = '#3572A5'; break;
        case 'java': case 'jar': $icon = 'fa-file-code'; $color = '#b07219'; break;
        case 'c': case 'cpp': case 'h': $icon = 'fa-file-code'; $color = '#00599c'; break;
        case 'cs': $icon = 'fa-file-code'; $color = '#68217a'; break;
        case 'sql': $icon = 'fa-database'; $color = '#f29111'; break;
        case 'sh': case 'bash': $icon = 'fa-terminal'; $color = '#4EAA25'; break;
        case 'yml': case 'yaml': $icon = 'fa-file-code'; $color = '#cb171e'; break;
        case 'rb': $icon = 'fa-file-code'; $color = '#CC342D'; break;
        case 'go': $icon = 'fa-file-code'; $color = '#00ADD8'; break;
        case 'swift': $icon = 'fa-file-code'; $color = '#F05138'; break;
        case 'kt': $icon = 'fa-file-code'; $color = '#7F52FF'; break;
        case 'rs': $icon = 'fa-file-code'; $color = '#000000'; break;
        case 'dockerfile': $icon = 'fa-file-code'; $color = '#384d54'; break;
        case 'ttf': case 'otf': case 'woff': case 'woff2': $icon = 'fa-font'; $color = '#94a2b0'; break;
        case 'exe': case 'app': case 'dmg': $icon = 'fa-cog'; $color = '#34495e'; break;
        case 'iso': $icon = 'fa-compact-disc'; $color = '#7f8c8d'; break;
        case 'apk': $icon = 'fa-robot'; $color = '#a4c639'; break;
        default: break;
    }
    
    $name = strtolower(basename($fileName));
    if (strpos($name, '.') === false) {
        if ($name === 'dockerfile') {
            $icon = 'fa-file-code'; $color = '#384d54';
        }
    }
    
    return ["icon" => $icon, "color" => $color];
}

function isImage($mimeType) { return str_starts_with($mimeType, "image/"); }
function isVideo($mimeType) { return str_starts_with($mimeType, "video/"); }
function isAudio($mimeType) { return str_starts_with($mimeType, "audio/"); }

function isEditableAsCode($fileName, $mimeType) {
    if (str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript', 'application/x-php'])) {
        return true;
    }
    $editableExtensions = ['js', 'ts', 'css', 'scss', 'sass', 'html', 'htm', 'xml', 'json', 'md', 'php', 'py', 'rb', 'go', 'java', 'cs', 'rs', 'swift', 'kt', 'kts', 'sh', 'bash', 'ps1', 'sql', 'yml', 'yaml', 'ini', 'cfg', 'conf', 'env', 'c', 'h', 'cpp', 'hpp', 'cxx', 'txt', 'log'];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $name = strtolower(basename($fileName));
    if (in_array($name, ['dockerfile', '.gitignore'])) {
        return true;
    }
    if (str_starts_with($mimeType, 'image/') || str_starts_with($mimeType, 'video/') || str_starts_with($mimeType, 'audio/') || $mimeType === 'application/pdf') {
        return false;
    }
    return in_array($extension, $editableExtensions);
}

function guessCodeLanguage($fileName) {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $name = strtolower(basename($fileName));
    if ($name === 'dockerfile') return 'dockerfile';
    if (empty($extension) && str_starts_with($name, '.')) {
        if ($name === '.gitignore') return 'gitignore';
        if (str_contains($name, 'env')) return 'ini';
        return 'text';
    }
    switch ($extension) {
        case "js": return "javascript";
        case "ts": return "typescript";
        case "css": return "css";
        case "scss": case "sass": return "scss";
        case "html": case "htm": return "html";
        case "xml": return "xml";
        case "json": return "json";
        case "md": return "markdown";
        case "php": return "php";
        case "py": return "python";
        case "rb": return "ruby";
        case "go": return "golang";
        case "java": return "java";
        case "cs": return "csharp";
        case "rs": return "rust";
        case "swift": return "swift";
        case "kt": case "kts": return "kotlin";
        case "sh": case "bash": return "sh";
        case "ps1": return "powershell";
        case "sql": return "sql";
        case "yml": case "yaml": return "yaml";
        case "ini": case "cfg": case "conf": case "env": return "ini";
        case "c": case "h": case "cpp": case "hpp": case "cxx": return "c_cpp";
        case "txt": case "log": return "text";
        default: return "text";
    }
}

function getFileTypeCategory($mimeType)
{
    if (str_starts_with($mimeType, "image/")) return "Images";
    if (str_starts_with($mimeType, "video/")) return "Videos";
    if (str_starts_with($mimeType, "audio/")) return "Audio";
    if (in_array($mimeType, ["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation"])) return "Documents";
    if (in_array($mimeType, ["application/zip", "application/x-rar-compressed", "application/x-7z-compressed", "application/gzip"])) return "Archives";
    if (str_starts_with($mimeType, "text/") || in_array($mimeType, ["application/json", "application/xml", "application/javascript"])) return "Code & Text";
    return "Other";
}

function getItemIdByPath($pdo, $path)
{
    $path = trim($path, "/");
    if (empty($path)) {
        return ROOT_FOLDER_ID;
    }
    $parts = explode("/", $path);
    $currentParentId = ROOT_FOLDER_ID;
    $itemId = null;
    foreach ($parts as $part) {
        $stmt = $pdo->prepare(
            "SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0"
        );
        $stmt->execute([$part, $currentParentId]);
        $result = $stmt->fetch();
        if ($result) {
            $itemId = $result["id"];
            $currentParentId = $itemId;
        } else {
            return null;
        }
    }
    return $itemId;
}

function getPathByItemId($pdo, $id)
{
    if ($id == ROOT_FOLDER_ID || $id == null) {
        return "";
    }
    $pathParts = [];
    $currentId = $id;
    while ($currentId != null && $currentId != ROOT_FOLDER_ID) {
        $stmt = $pdo->prepare(
            "SELECT name, parent_id FROM file_system WHERE id = ?"
        );
        $stmt->execute([$currentId]);
        $item = $stmt->fetch();
        if ($item) {
            array_unshift($pathParts, $item["name"]);
            $currentId = $item["parent_id"];
        } else {
            break;
        }
    }
    return implode("/", $pathParts);
}

function deleteFolderRecursiveDb($pdo, $folderId)
{
    $stmt = $pdo->prepare(
        "SELECT id, type FROM file_system WHERE parent_id = ?"
    );
    $stmt->execute([$folderId]);
    $children = $stmt->fetchAll();
    foreach ($children as $child) {
        if ($child["type"] === "folder") {
            deleteFolderRecursiveDb($pdo, $child["id"]);
        }
    }
    $deleteStmt = $pdo->prepare("DELETE FROM file_system WHERE id = ?");
    $deleteStmt->execute([$folderId]);
}

// --- 3. AUTHENTICATION CHECK ---

if (!defined("IS_PUBLIC_PAGE") || IS_PUBLIC_PAGE !== true) {
    if (AUTH_ENABLED === false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION["is_logged_in"] = true;
        $_SESSION["username"] = "Local User";
        return;
    }

    if (
        !isset($_SESSION["is_logged_in"]) ||
        $_SESSION["is_logged_in"] !== true
    ) {
        header("Location: login.php");
        exit();
    }
}