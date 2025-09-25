<?php
// =================================================================
// BOOTSTRAP FILE - CORE OF THE APPLICATION
// This file combines config.php, helpers.php, and auth_check.php
// =================================================================

// --- 1. CONFIGURATION (from config.php) ---

session_start();

// --- Main Configuration ---
define("DB_FILE", __DIR__ . "/database/database.sqlite");
define("ROOT_FOLDER_ID", 1);
define("TOTAL_STORAGE_GB", 5);
define("MAX_FILE_SIZE", 4000 * 1024 * 1024);
define("APP_NAME", "Nexus Drive");

// --- Authentication Configuration ---
define("AUTH_ENABLED", true); // Set to 'true' to enable login, 'false' for local development
define("ALLOW_REGISTRATION", false); // Set to 'true' to allow registration, 'false' to disable
define("USERS_FILE", __DIR__ . "/users.php"); // File to store user information

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
    $initial_users = ["admin" => password_hash("admin", PASSWORD_DEFAULT)];
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
            "CREATE TABLE `share_links` (`id` TEXT PRIMARY KEY, `file_id` INTEGER NOT NULL, `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`file_id`) REFERENCES `file_system` (`id`) ON DELETE CASCADE);"
        );
        $pdo->exec(
            "CREATE INDEX idx_parent_deleted ON file_system (parent_id, is_deleted);"
        );
        $pdo->exec(
            "INSERT INTO `file_system` (`id`, `name`, `type`, `parent_id`) VALUES (1, 'ROOT', 'folder', NULL)"
        );
        $pdo->commit();
    }
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), "unable to open database file") !== false) {
        die(
            "<strong>Configuration Error:</strong> Cannot create or open the database file. Please ensure the application directory is writable."
        );
    } else {
        die("Database connection error: " . $e->getMessage());
    }
}

// --- 2. HELPER FUNCTIONS (from helpers.php) ---

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
        return ["icon" => "fa-folder", "color" => "#5aa4f0"];
    }
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $color = "#8a8a8e";
    $icon = "fa-file";
    switch ($extension) {
        case "pdf":
            $icon = "fa-file-pdf";
            $color = "#e62e2e";
            break;
        case "doc":
        case "docx":
            $icon = "fa-file-word";
            $color = "#2a5699";
            break;
        case "xls":
        case "xlsx":
            $icon = "fa-file-excel";
            $color = "#217346";
            break;
        case "ppt":
        case "pptx":
            $icon = "fa-file-powerpoint";
            $color = "#d24726";
            break;
        case "jpg":
        case "jpeg":
        case "png":
        case "gif":
        case "bmp":
        case "heic":
            $icon = "fa-file-image";
            $color = "#5cb85c";
            break;
        case "zip":
        case "rar":
        case "7z":
            $icon = "fa-file-archive";
            $color = "#f0ad4e";
            break;
        case "txt":
        case "log":
        case "md":
            $icon = "fa-file-alt";
            $color = "#a0a0a5";
            break;
        case "mp3":
        case "wav":
        case "aac":
            $icon = "fa-file-audio";
            $color = "#c06c84";
            break;
        case "mp4":
        case "mov":
        case "avi":
            $icon = "fa-file-video";
            $color = "#6c5b7b";
            break;
        case "html":
        case "css":
        case "js":
        case "php":
        case "py":
        case "java":
        case "c":
        case "cpp":
        case "json":
        case "xml":
            $icon = "fa-file-code";
            $color = "#8d6e63";
            break;
        default:
            $icon = "fa-file";
            $color = "#8a8a8e";
            break;
    }
    return ["icon" => $icon, "color" => $color];
}

function isImage($mimeType)
{
    return str_starts_with($mimeType, "image/");
}
function isVideo($mimeType)
{
    return str_starts_with($mimeType, "video/");
}
function isAudio($mimeType)
{
    return str_starts_with($mimeType, "audio/");
}
function isPdf($mimeType)
{
    return $mimeType === "application/pdf";
}
function isTextOrCode($mimeType)
{
    return str_starts_with($mimeType, "text/") ||
        in_array($mimeType, [
            "application/json",
            "application/xml",
            "application/javascript",
        ]);
}

function guessCodeLanguage($fileName)
{
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    switch (strtolower($extension)) {
        case "php":
            return "php";
        case "js":
            return "javascript";
        case "css":
            return "css";
        case "html":
        case "htm":
            return "xml";
        case "json":
            return "json";
        case "xml":
            return "xml";
        case "py":
            return "python";
        case "java":
            return "java";
        case "c":
            return "c";
        case "cpp":
            return "cpp";
        case "md":
            return "markdown";
        default:
            return "plaintext";
    }
}

function getFileTypeCategory($mimeType)
{
    if (str_starts_with($mimeType, "image/")) {
        return "Images";
    }
    if (str_starts_with($mimeType, "video/")) {
        return "Videos";
    }
    if (str_starts_with($mimeType, "audio/")) {
        return "Audio";
    }
    if (
        in_array($mimeType, [
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        ])
    ) {
        return "Documents";
    }
    if (
        in_array($mimeType, [
            "application/zip",
            "application/x-rar-compressed",
            "application/x-7z-compressed",
            "application/gzip",
        ])
    ) {
        return "Archives";
    }
    if (
        str_starts_with($mimeType, "text/") ||
        in_array($mimeType, [
            "application/json",
            "application/xml",
            "application/javascript",
        ])
    ) {
        return "Code & Text";
    }
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

// --- 3. AUTHENTICATION CHECK (from auth_check.php) ---

// This check runs on every page that includes bootstrap.php, unless IS_PUBLIC_PAGE is defined.
// Pages like login.php, register.php, share.php will define this constant before including this file.
if (!defined("IS_PUBLIC_PAGE") || IS_PUBLIC_PAGE !== true) {
    // 1. Check the main toggle first
    if (AUTH_ENABLED === false) {
        // If auth is OFF, create a mock session to let the app function
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION["is_logged_in"] = true;
        $_SESSION["username"] = "Local User"; // Default username for local mode

        // Skip all other checks and allow access
        return;
    }

    // 2. If auth is ON, run the normal login check
    if (
        !isset($_SESSION["is_logged_in"]) ||
        $_SESSION["is_logged_in"] !== true
    ) {
        header("Location: login.php");
        exit();
    }
}
