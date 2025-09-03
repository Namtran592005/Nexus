
<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

// --- Khởi tạo biến ---
$items = [];
$totalSize = 0;
$numItems = 0;

// --- Lấy thông báo từ session ---
$message = '';
if (isset($_SESSION['message'])) {
    $msg_type = $_SESSION['message']['type'];
    $msg_text = $_SESSION['message']['text'];
    $message = '<div class="alert alert-' . htmlspecialchars($msg_type) . ' animate__animated animate__fadeInDown">' . htmlspecialchars($msg_text) . '</div>';
    unset($_SESSION['message']);
}

// --- Xử lý đường dẫn và trang hiện tại ---
$currentPage = isset($_GET['view']) ? $_GET['view'] : 'browse';
$currentRelativePath = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$currentFolderId = ROOT_FOLDER_ID; // Mặc định là thư mục gốc

// --- Lấy dữ liệu từ DB ---
try {
    if ($currentPage === 'browse') {
        if (!empty($currentRelativePath)) {
            $currentFolderId = getItemIdByPath($pdo, $currentRelativePath);
            if ($currentFolderId === null) {
                redirect_with_message(BASE_URL . '/index.php', 'Đường dẫn không hợp lệ.', 'danger');
            }
        }
        $stmt = $pdo->prepare("SELECT id, name, type, size, modified_at AS modified FROM file_system WHERE parent_id = ? AND is_deleted = 0");
        $stmt->execute([$currentFolderId]);
        $items = $stmt->fetchAll();

    } elseif ($currentPage === 'recents') {
        $stmt = $pdo->query("SELECT id, name, type, size, modified_at AS modified FROM file_system WHERE type = 'file' AND is_deleted = 0 ORDER BY modified_at DESC LIMIT 50");
        $items = $stmt->fetchAll();

    } elseif ($currentPage === 'shared') {
        $stmt = $pdo->query("SELECT fs.id, fs.name, fs.type, fs.size, fs.modified_at AS modified, sl.id AS share_id FROM file_system fs JOIN share_links sl ON fs.id = sl.file_id WHERE fs.is_deleted = 0 ORDER BY fs.name ASC");
        $items = $stmt->fetchAll();

    } elseif ($currentPage === 'trash') {
        $stmt = $pdo->query("SELECT id, name, type, size, deleted_at AS modified FROM file_system WHERE is_deleted = 1 ORDER BY deleted_at DESC");
        $items = $stmt->fetchAll();
    }

    // Gán đường dẫn tương đối và chuyển đổi timestamp
    foreach ($items as &$item) {
        if ($currentPage === 'browse' && $item['type'] === 'folder') {
            $item['relative_path'] = !empty($currentRelativePath) ? $currentRelativePath . '/' . $item['name'] : $item['name'];
        }
        $item['modified'] = strtotime($item['modified']);
    }
    unset($item);

    // Tính tổng dung lượng và số lượng item
    $totalSize = $pdo->query("SELECT SUM(size) FROM file_system WHERE is_deleted = 0 AND type = 'file'")->fetchColumn() ?: 0;
    $numItems = $pdo->query("SELECT COUNT(*) FROM file_system WHERE is_deleted = 0")->fetchColumn() ?: 0;


    // --- Dữ liệu cho Modal Info ---
    $totalStorageBytes = TOTAL_STORAGE_GB * 1024 * 1024 * 1024;
    $usagePercentage = ($totalStorageBytes > 0) ? ($totalSize / $totalStorageBytes) * 100 : 0;
    
    // Lấy dữ liệu phân loại dung lượng
    $stmt = $pdo->query("SELECT mime_type, SUM(size) as total_size FROM file_system WHERE type = 'file' AND is_deleted = 0 GROUP BY mime_type");
    $storageBreakdownRaw = $stmt->fetchAll();
    $storageBreakdown = [];
    foreach($storageBreakdownRaw as $row) {
        $category = getFileTypeCategory($row['mime_type']);
        if (!isset($storageBreakdown[$category])) {
            $storageBreakdown[$category] = 0;
        }
        $storageBreakdown[$category] += (int)$row['total_size'];
    }
    arsort($storageBreakdown); // Sắp xếp cho biểu đồ đẹp hơn
    $storageBreakdownForJs = [
        'labels' => array_keys($storageBreakdown),
        'data' => array_values($storageBreakdown),
    ];


} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Lỗi cơ sở dữ liệu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// --- Sắp xếp ---
$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = ($_GET['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

usort($items, function ($a, $b) use ($sort_by, $sort_order) {
    if ($a['type'] !== $b['type']) {
        return ($a['type'] === 'folder') ? -1 : 1;
    }
    $valA = $a[$sort_by] ?? null;
    $valB = $b[$sort_by] ?? null;
    $cmp = ($sort_by === 'name') ? strcasecmp($valA, $valB) : ($valA <=> $valB);
    return ($sort_order === 'asc') ? $cmp : -$cmp;
});

// --- Breadcrumbs ---
$breadcrumbs = [];
if ($currentPage === 'browse') {
    $breadcrumbs[] = ['name' => 'iCloud Drive', 'path' => ''];
    if (!empty($currentRelativePath)) {
        $pathParts = explode('/', $currentRelativePath);
        $accumulatedPath = '';
        foreach ($pathParts as $part) {
            if (!empty($part)) {
                $accumulatedPath .= (empty($accumulatedPath) ? '' : '/') . $part;
                $breadcrumbs[] = ['name' => $part, 'path' => $accumulatedPath];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iCloud Drive</title>
    <link rel="icon" type="image/x-icon" href="./src/image/favicon.ico">
    <link rel="stylesheet" href="./src/custom-fonts.css">
    <link rel="stylesheet" href="./src/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./src/css/animate.min.css" />
    <link rel="stylesheet" href="./src/css/atom-one-dark.min.css">
    <script src="./src/js/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-primary: #1c1c1e;
            --bg-secondary: #2c2c2e;
            --bg-tertiary: #3a3a3c;
            --text-color-primary: #f2f2f7;
            --text-color-secondary: #8a8a8e;
            --text-color-accent: #0a84ff;
            --border-color: #48484a;
            --highlight-color: #3f3f41;
            --selection-color: rgba(10, 132, 255, 0.3);
            --shadow-dark: 0 4px 15px rgba(0, 0, 0, 0.4);
            --radius-default: 8px;
            --transition-speed: 0.2s ease-out;
            --danger-color: #d9534f;
            --danger-color-hover: #c9302c;
        }

        body.light-mode {
            --bg-primary: #f2f2f7;
            --bg-secondary: #ffffff;
            --bg-tertiary: #ebebf0;
            --text-color-primary: #1c1c1e;
            --text-color-secondary: #636366;
            --border-color: #c6c6c8;
            --highlight-color: #e0e0e4;
            --selection-color: rgba(0, 122, 255, 0.2);
            --shadow-dark: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: var(--bg-primary);
            color: var(--text-color-primary);
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-user-select: none;
            user-select: none;
        }

        .main-content,
        .sidebar,
        .content-area {
            transition: background-color var(--transition-speed);
        }

        .header {
            background-color: var(--bg-tertiary);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-dark);
            z-index: 100;
            flex-shrink: 0;
        }

        .header .left-section {
            display: flex;
            align-items: center;
        }

        .header .logo {
            font-size: 1.4em;
            font-weight: 700;
            margin-right: 15px;
            display: flex;
            align-items: center;
        }

        .header .logo .fa-apple {
            margin-right: 8px;
        }

        .header .menu-toggle {
            display: none;
            font-size: 1.5em;
            margin-right: 15px;
            background: none;
            border: none;
            color: var(--text-color-secondary);
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
        }

        .header .right-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header .icon-btn {
            background: none;
            border: none;
            color: var(--text-color-secondary);
            font-size: 1.2em;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: color var(--transition-speed);
        }

        .header .icon-btn:hover {
            color: var(--text-color-primary);
        }

        .main-content {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .sidebar {
            width: 250px;
            background-color: var(--bg-secondary);
            padding: 20px 0;
            border-right: 1px solid var(--border-color);
            flex-shrink: 0;
            overflow-y: auto;
            transition: transform var(--transition-speed);
        }

        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color-primary);
            text-decoration: none;
            font-size: 1em;
            font-weight: 500;
            transition: background-color var(--transition-speed), color var(--transition-speed);
            border-left: 3px solid transparent;
        }

        .sidebar-menu li a:hover {
            background-color: var(--highlight-color);
        }

        .sidebar-menu li a.active {
            background-color: var(--highlight-color);
            color: var(--text-color-accent);
            border-left-color: var(--text-color-accent);
        }

        .sidebar-menu li a i {
            margin-right: 15px;
        }

        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .content-header h1 {
            font-size: 2em;
            font-weight: 700;
            margin: 0;
        }

        .content-header .fa-cloud {
            color: var(--text-color-accent);
        }

        .storage-info {
            font-size: 0.9em;
            color: var(--text-color-secondary);
        }

        .toolbar {
            background-color: var(--bg-tertiary);
            padding: 12px 20px;
            border-radius: var(--radius-default);
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            box-shadow: var(--shadow-dark);
            align-items: center;
        }

        .toolbar .icon-btn {
            background: none;
            border: none;
            color: var(--text-color-secondary);
            font-size: 1.1em;
            cursor: pointer;
            padding: 8px 10px;
            border-radius: 5px;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .toolbar .icon-btn:hover {
            color: var(--text-color-primary);
            background-color: var(--highlight-color);
        }

        .toolbar .icon-btn span {
            margin-left: 8px;
            font-weight: 500;
        }

        .toolbar .icon-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }

        .breadcrumbs {
            margin-bottom: 20px;
            font-size: 0.9em;
            color: var(--text-color-secondary);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .breadcrumbs a {
            color: var(--text-color-accent);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumbs span.separator {
            margin: 0 8px;
        }

        .breadcrumbs .current-folder {
            color: var(--text-color-primary);
            font-weight: 600;
        }

        .file-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--bg-secondary);
            border-radius: var(--radius-default);
            overflow: hidden;
        }

        .file-table thead th {
            background-color: var(--bg-tertiary);
            color: var(--text-color-secondary);
            padding: 12px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9em;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .file-table tbody tr.selectable {
            cursor: pointer;
        }

        .file-table tbody tr.dragged {
            opacity: 0.5;
            border: 2px dashed var(--text-color-accent);
        }

        .file-table tbody tr.drag-over {
            background-color: var(--selection-color);
        }

        .file-table tbody tr.selected {
            background-color: var(--selection-color) !important;
            color: var(--text-color-primary);
        }

        .file-table tbody tr:not(.selected):hover {
            background-color: var(--highlight-color);
        }

        .file-table tbody td {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .file-table tbody td.file-actions-cell {
            white-space: nowrap;
            width: 1%;
            text-align: right;
        }

        .file-table .file-name-cell a,
        .file-table .file-name-cell span.file-container {
            color: var(--text-color-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-table .file-name-cell i {
            font-size: 1.2em;
            width: 20px;
            text-align: center;
        }

        .file-table .file-text {
            word-break: break-all;
        }

        .file-actions {
            display: flex;
            gap: 5px;
        }

        .file-actions .action-btn {
            background-color: var(--bg-tertiary);
            color: var(--text-color-secondary);
            padding: 0;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1em;
            transition: all var(--transition-speed);
            text-decoration: none;
        }

        .file-actions .action-btn:hover {
            color: white;
            transform: scale(1.1);
        }

        .file-actions .more-actions-btn {
            display: none;
        }

        .no-files {
            text-align: center;
            padding: 50px 20px;
            background-color: var(--bg-secondary);
            border-radius: var(--radius-default);
            color: var(--text-color-secondary);
            border: 1px dashed var(--border-color);
            margin-top: 25px;
        }

        .no-files i {
            font-size: 2.5em;
            margin-bottom: 20px;
            display: block;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert.animate__fadeInDown {
            opacity: 1;
        }

        .alert-success {
            background-color: #2a6a3b;
            color: #d4edda;
        }

        .alert-danger {
            background-color: #8c2a30;
            color: #f8d7da;
        }

        .hidden-file-input {
            display: none;
        }

        .trash-actions {
            margin-bottom: 20px;
            text-align: right;
        }

        .trash-actions .btn-clean {
            background-color: var(--danger-color);
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .trash-actions .btn-clean:hover {
            background-color: var(--danger-color-hover);
        }

        .custom-checkbox-label {
            position: relative;
            display: inline-block;
            width: 18px;
            height: 18px;
            cursor: pointer;
            vertical-align: middle;
        }

        .custom-checkbox-label::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .file-table input[type="checkbox"]:checked+.custom-checkbox-label::before {
            background-color: var(--text-color-accent);
            border-color: var(--text-color-accent);
        }

        .custom-checkbox-label::after {
            content: '';
            position: absolute;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg) scale(0);
            transition: transform 0.2s ease;
        }

        .file-table input[type="checkbox"]:checked+.custom-checkbox-label::after {
            transform: rotate(45deg) scale(1);
        }

        .custom-checkbox-label:hover::before {
            border-color: var(--text-color-accent);
        }

        .file-table input[type="checkbox"]:focus+.custom-checkbox-label::before {
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.3);
        }

        .file-table thead th:first-child,
        .file-table tbody td:first-child {
            width: 40px;
            text-align: center;
            padding-right: 0;
        }

        #drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-default);
            padding: 30px;
            text-align: center;
            color: var(--text-color-secondary);
            transition: all var(--transition-speed);
            background-color: var(--bg-primary);
        }

        #drop-zone.dragover {
            border-color: var(--text-color-accent);
            background-color: var(--highlight-color);
            transform: scale(1.02);
        }

        #drop-zone i {
            font-size: 3.5em;
            margin-bottom: 15px;
            color: var(--text-color-accent);
        }

        #drop-zone .browse-btn {
            background: none;
            border: none;
            color: var(--text-color-accent);
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            font-size: 1em;
        }

        #upload-progress-list {
            margin-top: 20px;
            max-height: 280px;
            overflow-y: auto;
            padding-right: 10px;
        }

        #upload-progress-list::-webkit-scrollbar {
            width: 8px;
        }

        #upload-progress-list::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
            border-radius: 4px;
        }

        #upload-progress-list::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        #upload-progress-list::-webkit-scrollbar-thumb:hover {
            background: var(--highlight-color);
        }

        .progress-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: var(--bg-tertiary);
            border-radius: var(--radius-default);
            font-size: 0.9em;
        }

        .progress-item .file-icon {
            font-size: 2em;
            flex-shrink: 0;
        }

        .progress-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .progress-info .file-name {
            font-weight: 500;
            color: var(--text-color-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .progress-bar-container {
            width: 100%;
            height: 6px;
            background-color: var(--highlight-color);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-bar {
            width: 0;
            height: 100%;
            background-color: var(--text-color-accent);
            transition: width 0.1s linear;
        }

        .progress-status {
            font-size: 0.85em;
            color: var(--text-color-secondary);
            margin-top: 3px;
        }

        .progress-item .status-icon {
            font-size: 1.5em;
            margin-left: 10px;
        }

        .progress-item .status-icon.success {
            color: #28a745;
        }

        .progress-item .status-icon.error {
            color: #dc3545;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            transform: scale(0.95) translateY(-10px);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), opacity 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25em;
            font-weight: 600;
        }

        .modal-header .close-button {
            margin-left: 14px;
            font-size: 1.5em;
            cursor: pointer;
            background: red;
            border: none;
            /* height: 5px; */
            /* width: 5px; */
            border-radius: 5px;
            padding: 5px;
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .modal-body p {
            margin-top: 0;
            margin-bottom: 10px;
            line-height: 1.5;
            color: var(--text-color-secondary);
        }

        .modal-body label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9em;
        }

        .modal-body input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-tertiary);
            color: var(--text-color-primary);
            font-size: 1em;
            transition: border-color var(--transition-speed), box-shadow var(--transition-speed);
        }

        .modal-body input[type="text"]:focus {
            outline: none;
            border-color: var(--text-color-accent);
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.3);
        }

        .modal-body .copy-button {
            background-color: var(--text-color-accent);
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            margin-left: -1px;
        }

        .modal-body .form-group-share {
            display: flex;
            align-items: stretch;
        }

        .modal-body .form-group-share input {
            border-radius: 8px 0 0 8px;
            flex-grow: 1;
        }

        .modal-footer {
            padding: 15px 25px;
            background-color: var(--bg-tertiary);
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        .modal-footer .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .modal-footer .btn-cancel {
            background-color: var(--highlight-color);
            color: var(--text-color-primary);
        }
        
        .modal-footer .btn-primary {
            background-color: var(--text-color-accent);
            color: white;
        }

        .modal-footer .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        .modal-footer .btn-danger:hover {
            background-color: var(--danger-color-hover);
        }

        #uploadModal .modal-content {
            max-width: 600px;
        }

        #previewModal .modal-content {
            max-width: 90vw;
            width: auto;
            min-width: 320px;
            padding: 0;
            overflow: hidden;
        }

        #previewModal .modal-body {
            background-color: var(--bg-primary);
        }

        #previewModal .modal-body img,
        #previewModal .modal-body video,
        #previewModal .modal-body audio,
        #previewModal .modal-body iframe {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
        }

        #previewModal .modal-body pre {
            font-family: 'SF Mono', 'Menlo', monospace;
        }

        #previewModal .loading-spinner {
            border: 4px solid var(--highlight-color);
            border-top: 4px solid var(--text-color-accent);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .action-popover {
            display: none;
            position: fixed;
            z-index: 1000;
            background-color: var(--bg-secondary);
            border-radius: var(--radius-default);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            padding: 8px;
            min-width: 200px;
            opacity: 0;
            transform-origin: top right;
            transition: opacity 0.15s ease, transform 0.15s ease;
        }

        .action-popover.show {
            display: block;
            opacity: 1;
            transform: scale(1);
        }

        .action-popover .popover-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: var(--text-color-primary);
            text-decoration: none;
            border-radius: 6px;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .action-popover .popover-item:hover {
            background-color: var(--highlight-color);
        }

        .action-popover .popover-item i {
            margin-right: 12px;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 149;
        }

        .overlay.active {
            display: block;
        }
        
        /* --- NEW STYLES FOR INFO MODAL --- */
        #userInfoModal .modal-content {
            max-width: 650px;
        }
        #userInfoModal .modal-body {
            padding: 0;
        }
        .tab-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            padding: 0 25px;
        }
        .tab-nav-item {
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            font-weight: 500;
            color: var(--text-color-secondary);
        }
        .tab-nav-item.active {
            color: var(--text-color-accent);
            border-bottom-color: var(--text-color-accent);
        }
        .tab-content {
            padding: 25px;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .storage-progress-bar {
            width: 100%;
            height: 10px;
            background-color: var(--bg-tertiary);
            border-radius: 5px;
            overflow: hidden;
            margin: 8px 0;
        }
        .storage-progress-bar-inner {
            height: 100%;
            width: 0;
            background-color: var(--text-color-accent);
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        .storage-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            color: var(--text-color-secondary);
            margin-bottom: 25px;
        }
        #storageChartContainer {
            position: relative;
            height: 250px;
            margin: 0 auto;
        }
        .about-section {
            text-align: center;
        }
        .about-section .logo {
            font-size: 3em;
            color: var(--text-color-accent);
            margin-bottom: 10px;
        }
        .about-section h3 {
            margin-top: 0;
            font-size: 1.5em;
        }
        .about-section p {
            color: var(--text-color-secondary);
            max-width: 80%;
            margin: 10px auto;
        }
        /* --- END NEW STYLES --- */


        @media (max-width: 992px) {
            .toolbar .icon-btn span {
                display: none;
            }

            .file-table thead th:nth-child(3),
            .file-table tbody td:nth-child(3) {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 20px;
            }

            .header .logo span {
                display: none;
            }

            .header .menu-toggle {
                display: block;
            }

            .sidebar {
                position: fixed;
                top: 60px;
                left: 0;
                height: calc(100vh - 60px);
                transform: translateX(-100%);
                z-index: 150;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .file-table thead th:nth-child(4),
            .file-table thead th:nth-child(5),
            .file-table tbody td:nth-child(4),
            .file-table tbody td:nth-child(5) {
                display: none;
            }

            .file-actions .action-btn:not(.more-actions-btn) {
                display: none;
            }

            .file-actions .more-actions-btn {
                display: inline-flex;
            }
        }
    </style>
</head>

<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light' ? 'light-mode' : 'dark-mode'; ?>">
    <div class="header">
        <div class="left-section">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fa-regular fa-circle-right"></i></button>
            <div class="logo"><i class="fab fa-apple"></i> <span>iCloud Drive</span></div>
        </div>
        <div class="right-section">
            <button class="icon-btn" onclick="toggleTheme()" title="Chuyển đổi chế độ"><i
                    class="fas fa-adjust"></i></button>
            <button class="icon-btn" onclick="showUserInfoModal()" title="Thông tin"><i
                    class="fas fa-user-circle"></i></button>
        </div>
    </div>
    <div class="main-content">
        <div class="sidebar">
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="?view=recents" class="<?php if ($currentPage === 'recents')
                        echo 'active'; ?>"><i class="far fa-clock"></i> <span>Recents</span></a></li>
                    <li><a href="?view=browse" class="<?php if ($currentPage === 'browse')
                        echo 'active'; ?>"><i class="fas fa-folder-open"></i> <span>Browse</span></a></li>
                    <li><a href="?view=shared" class="<?php if ($currentPage === 'shared')
                        echo 'active'; ?>"><i class="fas fa-users"></i> <span>Shared</span></a></li>
                    <li><a href="?view=trash" class="<?php if ($currentPage === 'trash')
                        echo 'active'; ?>"><i class="fas fa-trash"></i> <span>Recently Deleted</span></a></li>
                </ul>
            </nav>
        </div>
        <div class="content-area">
            <?php echo $message; ?>
            <div class="content-header">
                <h1><i class="fas fa-cloud"></i>
                    <?php
                    if ($currentPage === 'browse')
                        echo 'iCloud Drive';
                    elseif ($currentPage === 'recents')
                        echo 'Recents';
                    elseif ($currentPage === 'shared')
                        echo 'Shared';
                    elseif ($currentPage === 'trash')
                        echo 'Recently Deleted';
                    ?>
                </h1>
                <div class="storage-info"><?php echo count($items); ?> items, <?php echo formatBytes($totalSize); ?> used
                </div>
            </div>
            <div class="toolbar">
                <?php if ($currentPage === 'browse'): ?>
                    <button type="button" class="icon-btn" onclick="openUploadModal()" title="Upload"><i
                            class="fas fa-cloud-upload-alt"></i> <span>Upload</span></button>
                    <button type="button" class="icon-btn" onclick="openNewFolderModal()" title="New Folder"><i
                            class="fas fa-folder-plus"></i> <span>New Folder</span></button>
                <?php endif; ?>
                <button id="batch-delete-btn" class="icon-btn disabled" title="Delete Selected"
                    onclick="batchDeleteSelected()"><i class="fas fa-trash"></i> <span>Delete</span></button>
            </div>
            <?php if ($currentPage === 'browse' && !empty($breadcrumbs)): ?>
                <div class="breadcrumbs">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <?php if ($index > 0): ?><span class="separator">/</span><?php endif; ?>
                        <?php if ($index === count($breadcrumbs) - 1 && !empty($crumb['path'])): ?>
                            <span class="current-folder"><?php echo htmlspecialchars($crumb['name']); ?></span>
                        <?php else: ?>
                            <a
                                href="<?php echo BASE_URL . '?view=browse&path=' . urlencode($crumb['path']); ?>"><?php echo htmlspecialchars($crumb['name']); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($currentPage === 'trash' && !empty($items)): ?>
                <div class="trash-actions"><button type="button" class="btn btn-clean" onclick="confirmEmptyTrash()"><i
                            class="fas fa-broom"></i> Clean Trash</button></div>
            <?php endif; ?>
            <?php if (empty($items) && !($currentPage === 'browse' && $currentFolderId != ROOT_FOLDER_ID)): ?>
                <div class="no-files"><i class="fas fa-box-open"></i>
                    <p>This space is empty.</p>
                </div>
            <?php else: ?>
                <table class="file-table">
                    <thead>
                        <tr>
                            <th>
                                <input style="display: none;" type="checkbox" id="select-all-checkbox"
                                    onchange="toggleSelectAll(this.checked)">
                                <label for="select-all-checkbox" class="custom-checkbox-label"></label>
                            </th>
                            <th>Name</th>
                            <th>Kind</th>
                            <th>Size</th>
                            <th>Date Modified</th>
                            <th class="file-actions-cell">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($currentPage === 'browse' && $currentFolderId != ROOT_FOLDER_ID):
                            $parentStmt = $pdo->prepare("SELECT parent_id FROM file_system WHERE id = ?");
                            $parentStmt->execute([$currentFolderId]);
                            $parentOfCurrent = $parentStmt->fetchColumn();
                            $parentPath = getPathByItemId($pdo, $parentOfCurrent);
                            ?>
                            <tr>
                                <td></td>
                                <td class="file-name-cell"><a href="?view=browse&path=<?php echo urlencode($parentPath); ?>"><i
                                            class="fas fa-level-up-alt"></i><span class="file-text">..</span></a></td>
                                <td>Folder</td>
                                <td>--</td>
                                <td>--</td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($items as $item):
                            $isFolder = $item['type'] === 'folder';
                            $fileInfo = getFileIcon($item['name'], $isFolder);
                            $kind = $isFolder ? 'Folder' : (strtoupper(pathinfo($item['name'], PATHINFO_EXTENSION)) ?: 'File');
                            ?>
                            <tr class="selectable" draggable="<?php echo $currentPage === 'browse' ? 'true' : 'false'; ?>"
                                data-id="<?php echo $item['id']; ?>" data-type="<?php echo $item['type']; ?>"
                                data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                <td></td>
                                <td class="file-name-cell">
                                    <?php if ($isFolder && $currentPage === 'browse'): ?>
                                        <a
                                            href="<?php echo BASE_URL . '?view=browse&path=' . urlencode($item['relative_path']); ?>"><i
                                                class="fas <?php echo $fileInfo['icon']; ?>"
                                                style="color: <?php echo $fileInfo['color']; ?>;"></i><span
                                                class="file-text"><?php echo htmlspecialchars($item['name']); ?></span></a>
                                    <?php else: ?>
                                        <span class="file-container"><i class="fas <?php echo $fileInfo['icon']; ?>"
                                                style="color: <?php echo $fileInfo['color']; ?>;"></i><span
                                                class="file-text"><?php echo htmlspecialchars($item['name']); ?></span></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($kind); ?></td>
                                <td><?php echo $isFolder ? '--' : formatBytes($item['size']); ?></td>
                                <td><?php echo date('d/m/Y H:i', $item['modified']); ?></td>
                                <td class="file-actions-cell">
                                    <div class="file-actions">
                                        <?php if ($currentPage === 'trash'): 
                                            $perm_delete_url = "delete.php?id=" . $item['id'] . "&force_delete=true";
                                            $perm_delete_msg = "This will permanently delete the item. This action cannot be undone.";
                                        ?>
                                            <a href="restore.php?id=<?php echo $item['id']; ?>" class="action-btn"
                                                title="Restore"><i class="fas fa-redo-alt"></i></a>
                                            <a href="#" class="action-btn" title="Delete Permanently"
                                                onclick="requestDeletion(event, '<?php echo $perm_delete_url; ?>', '<?php echo $perm_delete_msg; ?>', 'Confirm Permanent Deletion')"><i
                                                    class="fas fa-times"></i></a>
                                        <?php else: 
                                            $delete_url = "delete.php?id=" . $item['id'];
                                            $delete_msg = "Are you sure you want to move this item to the trash?";
                                        ?>
                                            <?php if (!$isFolder): ?>
                                                <a href="download.php?id=<?php echo $item['id']; ?>" class="action-btn" title="Download"
                                                    onclick="event.stopPropagation()"><i class="fas fa-download"></i></a>
                                                <button type="button" class="action-btn" title="Share"
                                                    onclick="handleAction(event, 'share', <?php echo $item['id']; ?>, '<?php echo isset($item['share_id']) ? $item['share_id'] : ''; ?>')"><i
                                                        class="fas fa-share-alt"></i></button>
                                                <button type="button" class="action-btn" title="View"
                                                    onclick="handleAction(event, 'preview', <?php echo $item['id']; ?>)"><i
                                                        class="fas fa-eye"></i></button>
                                            <?php endif; ?>
                                            <button type="button" class="action-btn" title="Rename"
                                                onclick="handleAction(event, 'rename', <?php echo $item['id']; ?>)"><i
                                                    class="fas fa-edit"></i></button>
                                            <a href="#" class="action-btn" title="Trash"
                                                onclick="requestDeletion(event, '<?php echo $delete_url; ?>', '<?php echo $delete_msg; ?>')"><i
                                                    class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                        <button type="button" class="action-btn more-actions-btn" title="More"
                                            onclick="showActionPopover(this, event)"><i class="fas fa-ellipsis-v"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <!-- Modals -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Files</h2><span class="close-button" onclick="closeModal('uploadModal')">×</span>
            </div>
            <div class="modal-body">
                <div id="drop-zone"><i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop files here, or <button type="button" class="browse-btn"
                            onclick="document.getElementById('file-input-chunk').click();">browse</button></p><input
                        type="file" id="file-input-chunk" class="hidden-file-input" multiple>
                </div>
                <div id="upload-progress-list"></div>
            </div>
        </div>
    </div>
    <div id="newFolderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Folder</h2><span class="close-button" onclick="closeModal('newFolderModal')">×</span>
            </div>
            <form action="new_folder.php" method="post">
                <div class="modal-body"><input type="hidden" name="parent_id"
                        value="<?php echo htmlspecialchars($currentFolderId); ?>">
                    <div class="form-group"><label for="folderName">Name:</label><input type="text" id="folderName"
                            name="folder_name" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-cancel"
                        onclick="closeModal('newFolderModal')">Cancel</button><button type="submit"
                        class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rename</h2><span class="close-button" onclick="closeModal('renameModal')">×</span>
            </div>
            <form action="rename.php" method="post">
                <div class="modal-body"><input type="hidden" id="renameItemId" name="id">
                    <div class="form-group"><label for="newName">New Name:</label><input type="text" id="newName"
                            name="new_name" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-cancel"
                        onclick="closeModal('renameModal')">Cancel</button><button type="submit"
                        class="btn btn-primary">Rename</button></div>
            </form>
        </div>
    </div>
    <div id="shareModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Share File</h2><span class="close-button" onclick="closeModal('shareModal')">×</span>
            </div>
            <div class="modal-body">
                <p>Copy the link to share:</p>
                <div class="form-group form-group-share"><input type="text" id="shareLinkInput" readonly><button
                        class="copy-button" onclick="copyShareLink()"><i class="fas fa-copy"></i></button></div>
                <p id="shareStatusMessage"></p>
            </div>
            <div class="modal-footer"><button class="btn btn-cancel" onclick="closeModal('shareModal')">Close</button>
            </div>
        </div>
    </div>
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="previewModalTitle"></h2><span class="close-button" onclick="closeModal('previewModal')">×</span>
            </div>
            <div class="modal-body">
                <div class="loading-spinner"></div>
                <div id="previewContent" style="display: none;"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-cancel" onclick="closeModal('previewModal')">Close</button>
            </div>
        </div>
    </div>
    <div id="userInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Information</h2><span class="close-button" onclick="closeModal('userInfoModal')">×</span>
            </div>
            <div class="modal-body">
                <div class="tab-nav">
                    <div class="tab-nav-item active" data-tab="overview">Overview</div>
                    <div class="tab-nav-item" data-tab="about">About</div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-overview">
                        <label>Storage Usage</label>
                        <div class="storage-progress-bar">
                            <div class="storage-progress-bar-inner" style="width: <?php echo $usagePercentage; ?>%;"></div>
                        </div>
                        <div class="storage-details">
                            <span><?php echo formatBytes($totalSize); ?> used</span>
                            <span><?php echo TOTAL_STORAGE_GB; ?> GB total</span>
                        </div>
                        <div id="storageChartContainer">
                            <canvas id="storageChart"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-about">
                        <div class="about-section">
                            <div class="logo"><i class="fab fa-apple"></i></div>
                            <h3>iCloud Drive</h3>
                            <p>Version 1.0</p>
                            <p>A simple, self-hosted file management solution inspired by iCloud</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" onclick="closeModal('userInfoModal')">OK</button>
            </div>
        </div>
    </div>
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="confirmModalTitle">Confirm Action</h2><span class="close-button"
                    onclick="closeModal('confirmModal')">×</span>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('confirmModal')">Cancel</button>
                <button type="button" id="confirmModalButton" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </div>
    <div id="actionPopover" class="action-popover"></div>
    <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let storageChartInstance = null;

        // --- Global Helpers & Toggles ---
        function openModal(id) { document.getElementById(id).classList.add('show'); }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        function toggleTheme() {
            const isLight = document.body.classList.toggle('light-mode');
            document.cookie = `theme=${isLight ? 'light' : 'dark'}; path=/; max-age=31536000`;
            if (storageChartInstance) { // Re-render chart with new theme colors
                storageChartInstance.destroy();
                renderStorageChart();
            }
        }
        const sidebar = document.querySelector('.sidebar'), overlay = document.getElementById('overlay');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        
        function openNewFolderModal() { openModal('newFolderModal'); document.getElementById('folderName').focus(); }

        // --- Custom Confirmation Modal ---
        function showConfirmModal(title, message, onConfirmCallback) {
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;
            const confirmBtn = document.getElementById('confirmModalButton');
            
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            newConfirmBtn.addEventListener('click', () => {
                closeModal('confirmModal');
                onConfirmCallback();
            });

            openModal('confirmModal');
        }
        
        // --- Info Modal & Chart ---
        function showUserInfoModal() {
            openModal('userInfoModal');
            // Delay rendering to ensure modal is visible and dimensions are correct
            setTimeout(() => {
                if (!storageChartInstance) {
                    renderStorageChart();
                }
            }, 100);
        }

        function renderStorageChart() {
            const storageData = <?php echo json_encode($storageBreakdownForJs); ?>;
            const ctx = document.getElementById('storageChart').getContext('2d');
            const isDarkMode = !document.body.classList.contains('light-mode');
            const textColor = isDarkMode ? 'rgba(242, 242, 247, 0.8)' : 'rgba(28, 28, 30, 0.8)';
            
            if (storageChartInstance) {
                storageChartInstance.destroy();
            }

            if (storageData.labels.length === 0) {
                 document.getElementById('storageChartContainer').innerHTML = '<p style="text-align:center; color: var(--text-color-secondary); padding-top: 50px;">No file data to display.</p>';
                 return;
            }

            storageChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: storageData.labels,
                    datasets: [{
                        label: 'Storage Used',
                        data: storageData.data,
                        backgroundColor: [
                            '#0a84ff', '#5ac8fa', '#ff9500', '#ff3b30',
                            '#34c759', '#ffcc00', '#af52de', '#5856d6'
                        ],
                        borderColor: isDarkMode ? '#2c2c2e' : '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 15,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${formatBytesJS(value)}`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
        
        // --- Core Action Modals & Handlers ---
        function openRenameModal(id, oldName, type) {
            openModal('renameModal');
            document.getElementById('renameItemId').value = id;
            const input = document.getElementById('newName');
            if (type === 'file') {
                const lastDot = oldName.lastIndexOf('.');
                if (lastDot > 0) {
                    input.value = oldName.substring(0, lastDot);
                } else {
                    input.value = oldName;
                }
            } else {
                input.value = oldName;
            }
            input.focus();
            input.select();
        }

        function openShareModal(id, shareId = '') { openModal('shareModal'); const linkInput = document.getElementById('shareLinkInput'), statusMsg = document.getElementById('shareStatusMessage'); linkInput.value = 'Generating...'; statusMsg.textContent = ''; if (shareId) { linkInput.value = `${BASE_URL}/share.php?id=${shareId}`; return; } const formData = new FormData(); formData.append('create_share_link', true); formData.append('file_id', id); fetch('share.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if (d.success) linkInput.value = `${BASE_URL}/share.php?id=${d.share_id}`; else { linkInput.value = 'Error'; statusMsg.textContent = d.message; } }); }
        function copyShareLink() { const input = document.getElementById('shareLinkInput'); input.select(); document.execCommand('copy'); document.getElementById('shareStatusMessage').textContent = 'Copied!'; }
        function openPreviewModal(name, id) { openModal('previewModal'); const title = document.getElementById('previewModalTitle'), content = document.getElementById('previewContent'), spinner = document.querySelector('#previewModal .loading-spinner'); title.textContent = name; content.style.display = 'none'; spinner.style.display = 'block'; content.innerHTML = ''; fetch(`preview.php?id=${id}`).then(r => r.json()).then(d => { spinner.style.display = 'none'; content.style.display = 'flex'; if (!d.success) { content.innerHTML = `<p>${d.message}</p>`; return; } let html = ''; switch (d.type) { case 'image': html = `<img src="${d.data}" alt="${name}">`; break; case 'video': html = `<video controls src="${d.data}" type="${d.mime_type}"></video>`; break; case 'audio': html = `<audio controls src="${d.data}" type="${d.mime_type}"></audio>`; break; case 'pdf': html = `<iframe src="${d.data}"></iframe>`; break; case 'code': html = `<pre><code class="language-${d.language}">${d.data.replace(/</g, "<")}</code></pre>`; break; default: html = `<div style="text-align:left;width:100%"><p><strong>Name:</strong> ${d.data.name}</p><p><strong>Size:</strong> ${d.data.size}</p><p><em>No preview available.</em></p></div>`; break; } content.innerHTML = html; if (d.type === 'code') hljs.highlightElement(content.querySelector('code')); }); }
        
        function confirmEmptyTrash() {
            showConfirmModal('Confirm Empty Trash', 'This will permanently delete all items in the trash. This action cannot be undone.', () => { window.location.href = 'empty_trash.php'; });
        }

        function handleAction(event, action, id, extra = '') {
            event.stopPropagation();
            const row = event.target.closest('tr');
            if (!row) return;
            const name = row.dataset.name;
            const type = row.dataset.type;
            switch (action) { case 'rename': openRenameModal(id, name, type); break; case 'share': openShareModal(id, extra); break; case 'preview': openPreviewModal(name, id); break; }
        }
        
        function requestDeletion(event, url, message, title = 'Confirm Deletion') {
            event.preventDefault(); event.stopPropagation();
            showConfirmModal(title, message, () => { window.location.href = url; });
        }

        // --- Click-to-Select & Batch Actions ---
        const tableBody = document.querySelector('.file-table tbody');
        if (tableBody) { tableBody.addEventListener('click', (e) => { const row = e.target.closest('tr.selectable'); if (!row || e.target.closest('a, button')) return; if (e.ctrlKey || e.metaKey) { row.classList.toggle('selected'); } else { const isSelected = row.classList.contains('selected'); tableBody.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected')); if (!isSelected) row.classList.add('selected'); } updateToolbarState(); }); }
        function toggleSelectAll(isChecked) { tableBody.querySelectorAll('tr.selectable').forEach(row => row.classList.toggle('selected', isChecked)); updateToolbarState(); }
        function updateToolbarState() { const selectedCount = document.querySelectorAll('tr.selected').length; document.getElementById('batch-delete-btn').classList.toggle('disabled', selectedCount === 0); const totalRows = document.querySelectorAll('tr.selectable').length; document.getElementById('select-all-checkbox').checked = (totalRows > 0 && selectedCount === totalRows); }
        
        function batchDeleteSelected() {
            const selectedIds = Array.from(document.querySelectorAll('tr.selected')).map(row => row.dataset.id);
            if (selectedIds.length === 0) return;
            showConfirmModal(`Confirm Deletion`, `Are you sure you want to move ${selectedIds.length} item(s) to the trash?`, () => {
                fetch('delete.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: selectedIds }) })
                .then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert('Error: ' + d.message); });
            });
        }

        // --- Drag & Drop ---
        let draggedItemId = null; document.querySelectorAll('tr[draggable="true"]').forEach(row => { row.addEventListener('dragstart', e => { draggedItemId = e.currentTarget.dataset.id; e.currentTarget.classList.add('dragged'); e.dataTransfer.setData('text/plain', draggedItemId); }); row.addEventListener('dragend', e => e.currentTarget.classList.remove('dragged')); if (row.dataset.type === 'folder') { row.addEventListener('dragover', e => { e.preventDefault(); if (draggedItemId && draggedItemId !== e.currentTarget.dataset.id) e.currentTarget.classList.add('drag-over'); }); row.addEventListener('dragleave', e => e.currentTarget.classList.remove('drag-over')); row.addEventListener('drop', e => { e.preventDefault(); e.stopPropagation(); e.currentTarget.classList.remove('drag-over'); const destId = e.currentTarget.dataset.id; if (draggedItemId && draggedItemId !== destId) moveItem(draggedItemId, destId); }); } });
        function moveItem(itemId, destId) { const formData = new FormData(); formData.append('item_id', itemId); formData.append('destination_id', destId); fetch('move.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert('Move failed: ' + d.message); }); }

        // --- Action Popover ---
        const actionPopover = document.getElementById('actionPopover');
        function showActionPopover(btn, e) {
            e.preventDefault(); e.stopPropagation();
            const row = btn.closest('tr'), id = row.dataset.id, name = row.dataset.name, type = row.dataset.type, shareId = row.dataset.shareId || '';
            const page = '<?php echo $currentPage; ?>';
            let content = '';
            if (page === 'trash') {
                const pUrl = `delete.php?id=${id}&force_delete=true`, pMsg = 'This will permanently delete the item. This action cannot be undone.';
                content = `<a class="popover-item" href="restore.php?id=${id}"><i class="fas fa-redo-alt"></i> Restore</a><a href="#" class="popover-item" onclick="requestDeletion(event, '${pUrl}', '${pMsg}', 'Confirm Permanent Deletion')"><i class="fas fa-times"></i> Delete Forever</a>`;
            } else {
                if (type === 'file') { content += `<a class="popover-item" href="download.php?id=${id}"><i class="fas fa-download"></i> Download</a><button type="button" class="popover-item" onclick="openShareModal(${id},'${shareId}')"><i class="fas fa-share-alt"></i> Share</button><button type="button" class="popover-item" onclick="openPreviewModal('${escapeJS(name)}', ${id})"><i class="fas fa-eye"></i> View</button>`; }
                const dUrl = `delete.php?id=${id}`, dMsg = 'Are you sure you want to move this item to the trash?';
                content += `<button type="button" class="popover-item" onclick="openRenameModal(${id},'${escapeJS(name)}', '${type}')"><i class="fas fa-edit"></i> Rename</button><a href="#" class="popover-item" onclick="requestDeletion(event, '${dUrl}', '${dMsg}')"><i class="fas fa-trash-alt"></i> Trash</a>`;
            }
            actionPopover.innerHTML = content; const rect = btn.getBoundingClientRect(); actionPopover.classList.add('show'); const popoverRect = actionPopover.getBoundingClientRect(); let top = rect.bottom + 5, left = rect.right - popoverRect.width; if (top + popoverRect.height > window.innerHeight) { top = rect.top - popoverRect.height - 5; } if (left < 5) { left = 5; } actionPopover.style.top = `${top}px`; actionPopover.style.left = `${left}px`;
        }
        function escapeJS(str) { return str.replace(/'/g, "\\'").replace(/"/g, '\\"'); }

        // --- Chunk Upload Logic (kept same) ---
        const CHUNK_SIZE = 2 * 1024 * 1024; const MAX_PARALLEL_UPLOADS = 4; const MAX_RETRIES = 3;
        let parentIdForUpload = <?php echo htmlspecialchars($currentFolderId); ?>;
        function openUploadModal() { parentIdForUpload = <?php echo htmlspecialchars($currentFolderId); ?>; openModal('uploadModal'); }
        const dropZone = document.getElementById('drop-zone'), fileInput = document.getElementById('file-input-chunk'), progressList = document.getElementById('upload-progress-list');
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('dragover'); if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files); });
        fileInput.addEventListener('change', () => { if (fileInput.files.length) handleFiles(fileInput.files); fileInput.value = ''; });
        function handleFiles(files) { for (const file of files) uploadFile(file); }
        function createProgressItem(file) { const fileInfo = getFileIconJS(file.name); const item = document.createElement('div'); item.className = 'progress-item'; item.innerHTML = `<i class="fas ${fileInfo.icon} file-icon" style="color: ${fileInfo.color};"></i><div class="progress-info"><div class="file-name">${escapeHtml(file.name)}</div><div class="progress-bar-container"><div class="progress-bar"></div></div><div class="progress-status">Initializing...</div></div><div class="status-icon"></div>`; progressList.appendChild(item); return item; }
        async function uploadFile(file) { const item = createProgressItem(file), bar = item.querySelector('.progress-bar'), status = item.querySelector('.progress-status'), icon = item.querySelector('.status-icon'); const totalChunks = Math.ceil(file.size / CHUNK_SIZE); status.textContent = 'Preparing...'; const startForm = new FormData(); startForm.append('action', 'start'); startForm.append('fileName', file.name); startForm.append('fileSize', file.size); startForm.append('mimeType', file.type); startForm.append('parentId', parentIdForUpload); let fileId; try { const res = await fetch('chunk_upload.php', { method: 'POST', body: startForm }); const data = await res.json(); if (!data.success) throw new Error(data.message); fileId = data.fileId; } catch (e) { status.textContent = 'Error: ' + e.message; icon.innerHTML = `<i class="fas fa-times-circle error"></i>`; return; } const chunkQueue = Array.from({ length: totalChunks }, (_, i) => i); let progress = 0; const uploadWorker = async () => { while (chunkQueue.length > 0) { const chunkIndex = chunkQueue.shift(); let retries = 0; while (retries < MAX_RETRIES) { try { const start = chunkIndex * CHUNK_SIZE; const chunk = file.slice(start, start + CHUNK_SIZE); const chunkForm = new FormData(); chunkForm.append('action', 'upload'); chunkForm.append('fileId', fileId); chunkForm.append('chunkIndex', chunkIndex); chunkForm.append('chunk', chunk); const res = await fetch('chunk_upload.php', { method: 'POST', body: chunkForm }); if (!res.ok) throw new Error(`HTTP ${res.status}`); const data = await res.json(); if (!data.success) throw new Error(data.message); progress++; updateProgress(progress, totalChunks, bar, status); break; } catch (e) { retries++; if (retries >= MAX_RETRIES) { throw new Error(`Chunk ${chunkIndex + 1} failed.`); } await new Promise(r => setTimeout(r, 1000 * retries)); } } } }; const workers = Array(MAX_PARALLEL_UPLOADS).fill(null).map(uploadWorker); try { await Promise.all(workers); } catch (e) { status.textContent = 'Upload failed: ' + e.message; icon.innerHTML = `<i class="fas fa-times-circle error"></i>`; return; } status.textContent = 'Assembling file...'; const completeForm = new FormData(); completeForm.append('action', 'complete'); completeForm.append('fileId', fileId); completeForm.append('totalChunks', totalChunks); try { const res = await fetch('chunk_upload.php', { method: 'POST', body: completeForm }); const data = await res.json(); if (!data.success) throw new Error(data.message); status.textContent = 'Complete!'; icon.innerHTML = `<i class="fas fa-check-circle success"></i>`; setTimeout(() => { if (document.getElementById('uploadModal').classList.contains('show')) window.location.reload(); }, 1200); } catch (e) { status.textContent = 'Finalization failed: ' + e.message; icon.innerHTML = `<i class="fas fa-exclamation-circle error"></i>`; } }
        function updateProgress(chunkNum, totalChunks, bar, status) { const percent = totalChunks > 0 ? Math.round((chunkNum / totalChunks) * 100) : 100; bar.style.width = `${percent}%`; status.textContent = `Uploading... ${percent}%`; }
        function getFileIconJS(name) { const ext = name.split('.').pop().toLowerCase(); switch (ext) { case 'pdf': return { icon: 'fa-file-pdf', color: '#e62e2e' }; case 'doc': case 'docx': return { icon: 'fa-file-word', color: '#2a5699' }; case 'zip': return { icon: 'fa-file-archive', color: '#f0ad4e' }; default: return { icon: 'fa-file', color: '#8a8a8e' }; } }
        function escapeHtml(text) { const map = { '&': '&', '<': '<', '>': '>', '"': '"', "'": "'" }; return text.replace(/[&<>"']/g, m => map[m]); }
        function formatBytesJS(bytes, decimals = 2) { if (bytes === 0) return '0 B'; const k = 1024; const dm = decimals < 0 ? 0 : decimals; const sizes = ['B', 'KB', 'MB', 'GB', 'TB']; const i = Math.floor(Math.log(bytes) / Math.log(k)); return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]; }

        // --- Global Event Listeners ---
        window.addEventListener('click', e => {
            if (e.target.classList.contains('modal')) closeModal(e.target.id);
            if (actionPopover.classList.contains('show') && !e.target.closest('.action-popover')) actionPopover.classList.remove('show');
        });

        // Tab functionality for Info Modal
        document.querySelectorAll('.tab-nav-item').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabContainer = tab.closest('.modal-body');
                tabContainer.querySelectorAll('.tab-nav-item').forEach(t => t.classList.remove('active'));
                tabContainer.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
            });
        });
    </script>

</body>

</html>