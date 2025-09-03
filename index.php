<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'helpers.php';

$items = [];
$totalSize = 0;
$numItems = 0;
$message = '';

if (isset($_SESSION['message'])) {
    $msg_type = $_SESSION['message']['type'];
    $msg_text = $_SESSION['message']['text'];
    $message = '<div class="alert alert-' . htmlspecialchars($msg_type) . ' animate__animated animate__fadeInDown">' . htmlspecialchars($msg_text) . '</div>';
    unset($_SESSION['message']);
}

$currentPage = isset($_GET['view']) ? $_GET['view'] : 'browse';
$currentRelativePath = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$currentFolderId = ROOT_FOLDER_ID;

try {
    if ($currentPage === 'browse') {
        if (!empty($currentRelativePath)) {
            $currentFolderId = getItemIdByPath($pdo, $currentRelativePath);
            if ($currentFolderId === null) {
                redirect_with_message(BASE_URL . '/index.php', 'Invalid path specified.', 'danger');
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
    } elseif ($currentPage === 'search') {
        $searchTerm = $_GET['q'] ?? '';
        if (!empty($searchTerm)) {
            $stmt = $pdo->prepare("SELECT id, name, type, size, modified_at AS modified, parent_id FROM file_system WHERE name LIKE ? AND is_deleted = 0 ORDER BY type, name");
            $stmt->execute(['%' . $searchTerm . '%']);
            $items = $stmt->fetchAll();
        }
    }


    foreach ($items as &$item) {
        if ($currentPage === 'browse' && $item['type'] === 'folder') {
            $item['relative_path'] = !empty($currentRelativePath) ? $currentRelativePath . '/' . $item['name'] : $item['name'];
        }
        if ($currentPage === 'search') {
            $item['full_path'] = getPathByItemId($pdo, $item['parent_id']);
        }
        $item['modified'] = strtotime($item['modified']);
    }
    unset($item);

    $totalSize = $pdo->query("SELECT SUM(size) FROM file_system WHERE is_deleted = 0 AND type = 'file'")->fetchColumn() ?: 0;
    $numItems = $pdo->query("SELECT COUNT(*) FROM file_system WHERE is_deleted = 0")->fetchColumn() ?: 0;

    $totalStorageBytes = TOTAL_STORAGE_GB * 1024 * 1024 * 1024;
    $usagePercentage = ($totalStorageBytes > 0) ? ($totalSize / $totalStorageBytes) * 100 : 0;

    $stmt = $pdo->query("SELECT mime_type, SUM(size) as total_size FROM file_system WHERE type = 'file' AND is_deleted = 0 GROUP BY mime_type");
    $storageBreakdownRaw = $stmt->fetchAll();
    $storageBreakdown = [];
    foreach ($storageBreakdownRaw as $row) {
        $category = getFileTypeCategory($row['mime_type']);
        if (!isset($storageBreakdown[$category])) $storageBreakdown[$category] = 0;
        $storageBreakdown[$category] += (int)$row['total_size'];
    }
    arsort($storageBreakdown);
    $storageBreakdownForJs = ['labels' => array_keys($storageBreakdown), 'data' => array_values($storageBreakdown)];
} catch (PDOException $e) {
    if (file_exists('config.php')) unlink('config.php');
    header('Location: setup.php?error=tables_missing');
    exit;
}

$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = ($_GET['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
usort($items, function ($a, $b) use ($sort_by, $sort_order) {
    if ($a['type'] !== $b['type']) return ($a['type'] === 'folder') ? -1 : 1;
    $valA = $a[$sort_by] ?? null;
    $valB = $b[$sort_by] ?? null;
    $cmp = ($sort_by === 'name') ? strcasecmp($valA, $valB) : ($valA <=> $valB);
    return ($sort_order === 'asc') ? $cmp : -$cmp;
});

$breadcrumbs = [];
if ($currentPage === 'browse') {
    $breadcrumbs[] = ['name' => 'Drive', 'path' => ''];
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ĐỔI TIÊU ĐỀ -->
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="./src/image/favicon.ico">
    <link rel="stylesheet" href="./src/custom-fonts.css">
    <link rel="stylesheet" href="./src/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- ... (Các link CSS và JS khác giữ nguyên) ... -->
    <link rel="stylesheet" href="./src/css/plyr.css" />
    <link rel="stylesheet" href="./src/css/prism-tomorrow.min.css" />
    <link rel="stylesheet" href="./src/css/prism-line-numbers.min.css" />
    <script src="./src/js/plyr.js"></script>
    <script src="./src/js/prism.min.js"></script>
    <script src="./src/js/prism-line-numbers.min.js"></script>
    <script src="./src/js/prism-core.min.js"></script>
    <script src="./src/js/prism-autoloader.min.js"></script>
    <script src="./src/js/pdf.mjs" type="module"></script>
    <script src="./src/js/chart.js"></script>

    <style>
        :root {
            --font-family-sans: 'Roboto', sans-serif;
            --radius-default: 10px;
            --transition-speed-fast: 0.2s;
            --transition-speed-normal: 0.3s;
            --transition-timing-function: cubic-bezier(0.25, 0.8, 0.25, 1);
            --header-height: 60px;
        }

        :root,
        body.dark-mode {
            --bg-primary: #161618;
            --bg-secondary: #1d1d20;
            --bg-tertiary: #2e2e32;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --text-accent: #0a84ff;
            --border-color: #3a3a3c;
            --highlight-color: #2a2a2d;
            --selection-color: rgba(10, 132, 255, 0.25);
            --shadow-color: rgba(0, 0, 0, 0.2);
            --danger-color: #ff453a;
            --danger-color-hover: #ff5e55;
            --plyr-color-main: var(--text-accent);
        }

        body.light-mode {
            --bg-primary: #f2f2f7;
            --bg-secondary: #ffffff;
            --bg-tertiary: #e5e5ea;
            --text-primary: #1c1c1e;
            --text-secondary: #636366;
            --border-color: #d1d1d6;
            --highlight-color: #e8e8ed;
            --selection-color: rgba(0, 122, 255, 0.15);
            --shadow-color: rgba(0, 0, 0, 0.08);
            --danger-color: #d9534f;
            --danger-color-hover: #c9302c;
            --plyr-color-main: var(--text-accent);
        }

        body {
            font-family: var(--font-family-sans);
            margin: 0;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
            user-select: none;
        }

        .main-content,
        .sidebar,
        .content-area {
            transition: background-color var(--transition-speed-fast) ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background-color: var(--bg-secondary);
            height: var(--header-height);
            box-sizing: border-box;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            z-index: 100;
            flex-shrink: 0;
        }

        .header .left-section,
        .header .right-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header .logo {
            font-size: 1.3em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header .icon-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25em;
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            line-height: 1;
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
        }

        .header .icon-btn:hover {
            color: var(--text-primary);
            background-color: var(--highlight-color);
            transform: scale(1.1);
        }

        .header .menu-toggle {
            display: none;
        }

        .main-content {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .sidebar {
            width: 260px;
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-sizing: border-box;
            transition: transform var(--transition-speed-normal) var(--transition-timing-function);
        }

        .sidebar-header .btn-upload {
            width: 100%;
            padding: 12px;
            background-color: var(--text-accent);
            color: white;
            border: none;
            border-radius: var(--radius-default);
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .sidebar-header .btn-upload:hover {
            background-color: #007aff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 122, 255, 0.3);
        }

        .sidebar-nav {
            margin-top: 24px;
            flex-grow: 1;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            margin: 4px 0;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 1em;
            font-weight: 500;
            border-radius: var(--radius-default);
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
        }

        .sidebar-nav-item a:hover {
            background-color: var(--highlight-color);
            color: var(--text-primary);
            transform: translateX(5px);
        }

        .sidebar-nav-item a.active {
            background-color: var(--selection-color);
            color: var(--text-accent);
        }

        .sidebar-nav-item a i {
            font-size: 1.1em;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
        }

        .sidebar-storage-info {
            font-size: 0.85em;
        }

        .sidebar-storage-info .details {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .sidebar-storage-info .progress-bar {
            width: 100%;
            height: 6px;
            background-color: var(--bg-tertiary);
            border-radius: 3px;
            overflow: hidden;
        }

        .sidebar-storage-info .progress-bar-inner {
            height: 100%;
            background-color: var(--text-accent);
            border-radius: 3px;
            transition: width var(--transition-speed-normal) var(--transition-timing-function);
        }

        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .content-header {
            animation: fadeInUp 0.5s var(--transition-timing-function) both;
        }

        .toolbar {
            animation: fadeInUp 0.5s var(--transition-timing-function) 0.1s both;
        }

        .breadcrumbs {
            animation: fadeInUp 0.5s var(--transition-timing-function) 0.15s both;
        }

        .file-table {
            animation: fadeInUp 0.5s var(--transition-timing-function) 0.2s both;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .content-header h1 {
            font-size: 2.2em;
            font-weight: 700;
            margin: 0;
        }

        .content-header .stats {
            font-size: 0.9em;
            color: var(--text-secondary);
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .toolbar .icon-btn {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.9em;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: var(--radius-default);
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .toolbar .icon-btn:hover {
            color: var(--text-primary);
            background-color: var(--highlight-color);
            border-color: var(--highlight-color);
            transform: translateY(-2px);
        }

        .toolbar .icon-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .breadcrumbs a {
            color: var(--text-accent);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumbs span.separator {
            margin: 0 8px;
        }

        .breadcrumbs .current-folder {
            color: var(--text-primary);
            font-weight: 600;
        }

        .file-table {
            width: 100%;
            border-collapse: collapse;
        }

        .file-table thead th {
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            padding: 12px 20px;
            text-align: left;
            font-weight: 500;
            font-size: 0.9em;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .file-table tbody tr {
            cursor: pointer;
            animation: fadeInUp 0.5s var(--transition-timing-function) both;
        }

        .file-table tbody tr.selected {
            background-color: var(--selection-color) !important;
            color: var(--text-primary);
        }

        .file-table tbody tr:not(.selected):hover {
            background-color: var(--highlight-color);
        }

        .file-table tbody td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            transition: background-color var(--transition-speed-fast) ease;
        }

        .file-table .file-name-cell a,
        .file-table .file-name-cell span {
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-table .file-name-cell i {
            font-size: 1.3em;
            width: 20px;
            text-align: center;
        }

        .file-table .file-text {
            word-break: break-all;
            font-weight: 500;
        }

        .file-table .file-actions {
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity var(--transition-speed-fast) ease;
        }

        .file-table tr:hover .file-actions,
        .file-table tr.selected .file-actions {
            opacity: 1;
        }

        .file-actions .action-btn {
            background-color: var(--bg-tertiary);
            color: var(--text-secondary);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9em;
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
        }

        .file-actions .action-btn:hover {
            color: var(--text-primary);
            background-color: var(--highlight-color);
            transform: scale(1.15);
        }

        .file-actions .more-actions-btn {
            display: none;
        }

        .no-files {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-files i {
            font-size: 3em;
            margin-bottom: 20px;
            display: block;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--radius-default);
            font-weight: 500;
            animation: fadeInUp 0.5s var(--transition-timing-function) both;
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
            border-radius: var(--radius-default);
            cursor: pointer;
            font-weight: 500;
            transition: background-color var(--transition-speed-fast);
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
            background-color: var(--text-accent);
            border-color: var(--text-accent);
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
            border-color: var(--text-accent);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity var(--transition-speed-normal) ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-color);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            opacity: 0;
            transition: all var(--transition-speed-normal) var(--transition-timing-function);
            will-change: transform, opacity;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.2em;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .modal-header .close-button {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5em;
            cursor: pointer;
            line-height: 1;
            transition: transform var(--transition-speed-fast) ease;
        }

        .modal-header .close-button:hover {
            transform: rotate(90deg);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .modal-body p {
            margin-top: 0;
            margin-bottom: 15px;
            line-height: 1.5;
            color: var(--text-secondary);
        }

        .modal-body label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9em;
        }

        .modal-body input[type="text"] {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-default);
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            font-size: 1em;
            transition: border-color var(--transition-speed-fast);
        }

        .modal-body input[type="text"]:focus {
            outline: none;
            border-color: var(--text-accent);
        }

        .modal-footer {
            padding: 16px 24px;
            background-color: var(--bg-primary);
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
            border-radius: var(--radius-default);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
        }

        .modal-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .modal-footer .btn-cancel {
            background-color: var(--highlight-color);
            color: var(--text-primary);
        }

        .modal-footer .btn-primary {
            background-color: var(--text-accent);
            color: white;
        }

        .modal-footer .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        #uploadModal .modal-content {
            max-width: 600px;
        }

        #drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-default);
            padding: 40px;
            text-align: center;
            color: var(--text-secondary);
            transition: all var(--transition-speed-fast);
            background-color: var(--bg-primary);
        }

        #drop-zone.dragover {
            border-color: var(--text-accent);
            background-color: var(--highlight-color);
        }

        #drop-zone i {
            font-size: 3em;
            margin-bottom: 15px;
            color: var(--text-accent);
        }

        #drop-zone .browse-btn {
            background: none;
            border: none;
            color: var(--text-accent);
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

        .progress-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .progress-info .file-name {
            font-weight: 500;
            color: var(--text-primary);
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
            background-color: var(--text-accent);
            transition: width 0.1s linear;
        }

        #userInfoModal .modal-content {
            max-width: 650px;
        }

        #userInfoModal .modal-body {
            padding: 0;
        }

        .tab-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            padding: 0 24px;
        }

        .tab-nav-item {
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .tab-nav-item.active {
            color: var(--text-accent);
            border-bottom-color: var(--text-accent);
        }

        .tab-content {
            padding: 24px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
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
            color: var(--text-accent);
            margin-bottom: 10px;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 149;
            opacity: 0;
            transition: opacity var(--transition-speed-normal) ease;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        .action-popover {
            display: none;
            position: fixed;
            z-index: 1000;
            background-color: var(--bg-tertiary);
            border-radius: var(--radius-default);
            box-shadow: 0 5px 15px var(--shadow-color);
            padding: 8px;
            min-width: 200px;
            opacity: 0;
            transform: scale(0.95);
            transform-origin: top right;
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
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
            color: var(--text-primary);
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

        #previewModal .modal-content {
            background: var(--bg-secondary);
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            width: auto;
            max-width: 90vw;
            height: auto;
            max-height: 90vh;
            overflow: hidden;
        }

        #previewModal .modal-header {
            background-color: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 12px 20px;
        }

        #previewModal .modal-body {
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: black;
            height: 100%;
            overflow: hidden;
        }

        #previewModal .modal-body img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }

        #previewContent .plyr {
            width: 100%;
            height: 100%;
        }

        #previewContent {
            overflow: auto;
        }

        #previewModal .plyr--video,
        #previewModal .plyr--audio {
            max-height: calc(90vh - 55px);
        }

        body.light-mode .plyr--audio .plyr__controls {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-top: 1px solid var(--border-color);
        }

        #pdf-viewer-container {
            width: 100%;
            height: calc(90vh - 55px);
            overflow: auto;
            background-color: #525659;
        }

        #pdf-canvas {
            display: block;
            margin: 0 auto;
        }

        #pdf-controls {
            background: var(--bg-secondary);
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        #pdf-controls button,
        #pdf-controls input {
            margin: 0 5px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 4px 8px;
        }

        #previewContent pre[class*="language-"] {
            max-height: calc(90vh - 55px);
            margin: 0;
            background: #282c34;
        }
        
        /* --- Search Form Styles --- */
        .search-form {
            display: flex;
            align-items: center;
            background-color: var(--bg-tertiary);
            border-radius: var(--radius-default);
            border: 1px solid var(--border-color);
            transition: all var(--transition-speed-fast) ease;
        }
        .search-form:focus-within {
            border-color: var(--text-accent);
            box-shadow: 0 0 0 3px var(--selection-color);
        }
        .search-input {
            background: none;
            border: none;
            color: var(--text-primary);
            padding: 8px 12px;
            font-size: 0.9em;
            outline: none;
        }
        .search-form-desktop .search-input {
             min-width: 250px;
        }
        .search-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 8px 12px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .search-form-mobile {
            display: none;
            width: 100%;
            margin-bottom: 25px;
        }
        .search-form-mobile .search-input {
            flex-grow: 1;
        }
        /* --- End Search Form Styles --- */

        .version-switcher {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-version {
            background-color: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: var(--radius-default);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-speed-fast) var(--transition-timing-function);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-version:hover {
            background-color: var(--highlight-color);
            color: var(--text-primary);
            transform: translateY(-2px);
        }

        .btn-version i {
            font-size: 0.9em;
        }

        @media (max-width: 992px) {
            .toolbar .icon-btn span {
                display: none;
            }
             .toolbar .icon-btn {
                padding: 8px 12px;
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

            .search-form-desktop {
                display: none;
            }
            .search-form-mobile {
                display: flex;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                transform: translateX(-100%);
                z-index: 150;
                box-shadow: 5px 0 15px var(--shadow-color);
                border-right: none;
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .file-table thead th:nth-child(3),
            .file-table tbody td:nth-child(3),
            .file-table thead th:nth-child(4),
            .file-table tbody td:nth-child(4),
            .file-table thead th:nth-child(5),
            .file-table tbody td:nth-child(5) {
                display: none;
            }

            .file-actions .action-btn:not(.more-actions-btn) {
                display: none;
            }

            .file-actions .more-actions-btn {
                display: inline-flex;
            }

            .file-table tr:hover .file-actions,
            .file-table tr.selected .file-actions,
            .file-actions {
                opacity: 1;
            }
        }

        .header .user-info {
        color: var(--text-secondary);
        font-size: 0.9em;
        font-weight: 500;
        white-space: nowrap;

        }

        @media (max-width: 768px) {
            .header .user-info {
                display: none; /* Ẩn tên người dùng trên mobile cho gọn */
            }
        }
    </style>
</head>

<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light' ? 'light-mode' : 'dark-mode'; ?>">
    <div class="header">
        <div class="left-section">
            <button class="menu-toggle icon-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="logo"><i class="fas fa-cloud-bolt"></i> <span><?php echo APP_NAME; ?></span></div>
        </div>
        <div class="right-section">
            <form action="index.php" method="GET" class="search-form search-form-desktop">
                <input type="hidden" name="view" value="search">
                <input type="search" name="q" placeholder="Search files & folders..." class="search-input" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>
            <!-- START: Thêm thông tin người dùng và nút đăng xuất -->
            <span class="user-info">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="icon-btn" title="Đăng xuất"><i class="fas fa-sign-out-alt"></i></a>
            <!-- END: Thêm thông tin người dùng và nút đăng xuất -->
            <button class="icon-btn" onclick="toggleTheme()" title="Toggle Theme"><i class="fas fa-adjust"></i></button>
            <button class="icon-btn" onclick="showUserInfoModal()" title="Information"><i class="fas fa-info-circle"></i></button>
        </div>
    </div>
    <div class="main-content">
        <div class="sidebar">
            <div class="sidebar-header">
                <button class="btn-upload" onclick="openUploadModal()"><i class="fas fa-cloud-upload-alt"></i> Upload File</button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="sidebar-nav-item"><a href="?view=recents" class="<?php if ($currentPage === 'recents') echo 'active'; ?>"><i class="far fa-clock"></i> <span>Recents</span></a></li>
                    <li class="sidebar-nav-item"><a href="?view=browse" class="<?php if ($currentPage === 'browse') echo 'active'; ?>"><i class="fas fa-folder"></i> <span>Browse</span></a></li>
                    <li class="sidebar-nav-item"><a href="?view=shared" class="<?php if ($currentPage === 'shared') echo 'active'; ?>"><i class="fas fa-users"></i> <span>Shared</span></a></li>
                    <li class="sidebar-nav-item"><a href="?view=trash" class="<?php if ($currentPage === 'trash') echo 'active'; ?>"><i class="fas fa-trash"></i> <span>Trash</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-storage-info">
                    <div class="details">
                        <span>Storage</span>
                        <span><?php echo formatBytes($totalSize); ?> of <?php echo TOTAL_STORAGE_GB; ?> GB</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-bar-inner" style="width: <?php echo $usagePercentage; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="overlay" class="overlay" onclick="toggleSidebar()"></div>
        <div class="content-area">
            <?php echo $message; ?>
            <div class="content-header">
                <h1><?php echo ucfirst($currentPage); ?></h1>
                <div class="stats"><?php echo count($items); ?> items, <?php echo formatBytes($totalSize); ?> used</div>
            </div>
            
            <form action="index.php" method="GET" class="search-form search-form-mobile">
                <input type="hidden" name="view" value="search">
                <input type="search" name="q" placeholder="Search in Drive..." class="search-input" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>
            
            <div class="toolbar">
                <?php if ($currentPage === 'browse'): ?>
                    <button type="button" class="icon-btn" onclick="openNewFolderModal()"><i class="fas fa-folder-plus"></i> <span>New Folder</span></button>
                <?php endif; ?>
                <button id="batch-download-btn" class="icon-btn disabled" onclick="batchDownloadSelected()"><i class="fas fa-file-archive"></i> <span>Download as ZIP</span></button>
                <button id="batch-delete-btn" class="icon-btn disabled" onclick="batchDeleteSelected()"><i class="fas fa-trash"></i> <span>Delete</span></button>
            </div>
            <?php if ($currentPage === 'browse' && !empty($breadcrumbs)): ?>
                <div class="breadcrumbs">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <?php if ($index > 0): ?><span class="separator">/</span><?php endif; ?>
                        <?php if ($index === count($breadcrumbs) - 1 && !empty($crumb['path'])): ?>
                            <span class="current-folder"><?php echo htmlspecialchars($crumb['name']); ?></span>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL . '?view=browse&path=' . urlencode($crumb['path']); ?>"><?php echo htmlspecialchars($crumb['name']); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($currentPage === 'trash' && !empty($items)): ?>
                <div class="trash-actions"><button type="button" class="btn-clean" onclick="confirmEmptyTrash()"><i class="fas fa-broom"></i> Empty Trash</button></div>
            <?php endif; ?>
            <?php if (empty($items)): ?>
                <div class="no-files"><i class="fas fa-box-open"></i>
                    <p>This folder is empty.</p>
                </div>
            <?php else: ?>
                <table class="file-table">
                    <thead>
                        <tr>
                            <th><input style="display: none;" type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this.checked)"><label for="select-all-checkbox" class="custom-checkbox-label"></label></th>
                            <th>Name</th>
                            <th>Kind</th>
                            <th>Size</th>
                            <th>Date Modified</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($currentPage === 'browse' && $currentFolderId != ROOT_FOLDER_ID):
                            $parentStmt = $pdo->prepare("SELECT parent_id FROM file_system WHERE id = ?");
                            $parentStmt->execute([$currentFolderId]);
                            $parentPath = getPathByItemId($pdo, $parentStmt->fetchColumn());
                        ?>
                            <tr style="animation-delay: 0s">
                                <td></td>
                                <td class="file-name-cell"><a href="?view=browse&path=<?php echo urlencode($parentPath); ?>"><i class="fas fa-level-up-alt"></i><span class="file-text">..</span></a></td>
                                <td>Folder</td>
                                <td>--</td>
                                <td>--</td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (array_values($items) as $index => $item):
                            $isFolder = $item['type'] === 'folder';
                            $fileInfo = getFileIcon($item['name'], $isFolder);
                            $kind = $isFolder ? 'Folder' : (strtoupper(pathinfo($item['name'], PATHINFO_EXTENSION)) ?: 'File');
                        ?>
                            <tr class="selectable" style="animation-delay: <?php echo $index * 0.03; ?>s;" draggable="<?php echo $currentPage === 'browse' ? 'true' : 'false'; ?>" data-id="<?php echo $item['id']; ?>" data-type="<?php echo $item['type']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                <td><input style="display: none;" type="checkbox" id="cb-<?php echo $item['id']; ?>"><label for="cb-<?php echo $item['id']; ?>" class="custom-checkbox-label"></label></td>
                                <td class="file-name-cell">
                                    <?php if ($isFolder && $currentPage === 'browse'): ?>
                                        <a href="<?php echo BASE_URL . '?view=browse&path=' . urlencode($item['relative_path']); ?>"><i class="fas <?php echo $fileInfo['icon']; ?>" style="color: <?php echo $fileInfo['color']; ?>;"></i><span class="file-text"><?php echo htmlspecialchars($item['name']); ?></span></a>
                                    <?php elseif ($currentPage === 'search'): ?>
                                        <a href="<?php echo BASE_URL . '?view=browse&path=' . urlencode($item['full_path']); ?>" title="Go to folder">
                                            <i class="fas <?php echo $fileInfo['icon']; ?>" style="color: <?php echo $fileInfo['color']; ?>;"></i>
                                            <span class="file-text">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                                <small style="display: block; color: var(--text-secondary); font-weight: 400; margin-top: 3px;">
                                                    in /<?php echo htmlspecialchars($item['full_path']); ?>
                                                </small>
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <span><i class="fas <?php echo $fileInfo['icon']; ?>" style="color: <?php echo $fileInfo['color']; ?>;"></i><span class="file-text"><?php echo htmlspecialchars($item['name']); ?></span></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($kind); ?></td>
                                <td><?php echo $isFolder ? '--' : formatBytes($item['size']); ?></td>
                                <td><?php echo date('d/m/Y H:i', $item['modified']); ?></td>
                                <td>
                                    <div class="file-actions">
                                        <?php if ($currentPage === 'trash'): ?>
                                            <a href="restore.php?id=<?php echo $item['id']; ?>" class="action-btn" title="Restore"><i class="fas fa-redo-alt"></i></a>
                                            <a href="#" class="action-btn" title="Delete Permanently" onclick="requestDeletion(event, 'delete.php?id=<?php echo $item['id']; ?>&force_delete=true', 'This will permanently delete the item. This action cannot be undone.', 'Confirm Permanent Deletion')"><i class="fas fa-times"></i></a>
                                        <?php else: ?>
                                            <?php if (!$isFolder): ?>
                                                <a href="download.php?id=<?php echo $item['id']; ?>" class="action-btn" title="Download" onclick="event.stopPropagation()"><i class="fas fa-download"></i></a>
                                                <button type="button" class="action-btn" title="Share" onclick="handleAction(event, 'share', <?php echo $item['id']; ?>, '<?php echo isset($item['share_id']) ? $item['share_id'] : ''; ?>')"><i class="fas fa-share-alt"></i></button>
                                                <button type="button" class="action-btn" title="View" onclick="handleAction(event, 'preview', <?php echo $item['id']; ?>)"><i class="fas fa-eye"></i></button>
                                            <?php endif; ?>
                                            <button type="button" class="action-btn" title="Rename" onclick="handleAction(event, 'rename', <?php echo $item['id']; ?>)"><i class="fas fa-edit"></i></button>
                                            <a href="#" class="action-btn" title="Trash" onclick="requestDeletion(event, 'delete.php?id=<?php echo $item['id']; ?>', 'Are you sure you want to move this item to the trash?')"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                        <button type="button" class="action-btn more-actions-btn" title="More" onclick="showActionPopover(this, event)"><i class="fas fa-ellipsis-v"></i></button>
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
                <h2>Upload Files</h2><button class="close-button" onclick="closeModal('uploadModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="drop-zone"><i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop files here, or <button type="button" class="browse-btn" onclick="document.getElementById('file-input-chunk').click();">browse</button></p><input type="file" id="file-input-chunk" class="hidden-file-input" multiple>
                </div>
                <div id="upload-progress-list"></div>
            </div>
        </div>
    </div>
    <div id="newFolderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Folder</h2><button class="close-button" onclick="closeModal('newFolderModal')"><i class="fas fa-times"></i></button>
            </div>
            <form action="new_folder.php" method="post">
                <div class="modal-body"><input type="hidden" name="parent_id" value="<?php echo htmlspecialchars($currentFolderId); ?>"><label for="folderName">Name:</label><input type="text" id="folderName" name="folder_name" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-cancel" onclick="closeModal('newFolderModal')">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rename</h2><button class="close-button" onclick="closeModal('renameModal')"><i class="fas fa-times"></i></button>
            </div>
            <form action="rename.php" method="post">
                <div class="modal-body"><input type="hidden" id="renameItemId" name="id"><label for="newName">New Name:</label><input type="text" id="newName" name="new_name" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-cancel" onclick="closeModal('renameModal')">Cancel</button><button type="submit" class="btn btn-primary">Rename</button></div>
            </form>
        </div>
    </div>
    <div id="shareModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Share File</h2><button class="close-button" onclick="closeModal('shareModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>Copy the link to share:</p>
                <div style="display:flex;"><input type="text" id="shareLinkInput" readonly style="flex-grow:1;"><button class="btn-primary" onclick="copyShareLink()" style="border-radius:0 var(--radius-default) var(--radius-default) 0;"><i class="fas fa-copy"></i></button></div>
                <p id="shareStatusMessage" style="margin-top:10px;"></p>
            </div>
            <div class="modal-footer"><button class="btn btn-cancel" onclick="closeModal('shareModal')">Close</button></div>
        </div>
    </div>
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="previewModalTitle"></h2><button class="close-button" onclick="closeModal('previewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
        </div>
    </div>
    <div id="userInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Information</h2><button class="close-button" onclick="closeModal('userInfoModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="tab-nav">
                    <div class="tab-nav-item active" data-tab="overview">Overview</div>
                    <div class="tab-nav-item" data-tab="about">About</div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-overview"><label>Storage Usage</label>
                        <div class="sidebar-storage-info">
                            <div class="progress-bar" style="height:10px;margin:8px 0;">
                                <div class="progress-bar-inner" style="width: <?php echo $usagePercentage; ?>%;"></div>
                            </div>
                        </div>
                        <div class="storage-details" style="font-size:0.9em;justify-content:space-between;display:flex;"><span><?php echo formatBytes($totalSize); ?> used</span><span><?php echo TOTAL_STORAGE_GB; ?> GB total</span></div>
                        <div id="storageChartContainer"><canvas id="storageChart"></canvas></div>
                    </div>
                    <div class="tab-pane" id="tab-about">
                        <div class="about-section">
                            <div class="logo"><i class="fas fa-cloud-bolt"></i></div>
                            <h3><?php echo APP_NAME; ?></h3>
                            <p>Version 1.0</p>
                            <p>A simple, self-hosted file management solution.</p>
                            <div class="version-switcher">
                                <a href="beta.php" class="btn-version" style="text-decoration: none;">
                                    <i class="fas fa-flask"></i> Beta Version
                                </a>
                                <a href="previous.php" class="btn-version" style="text-decoration: none;">
                                    <i class="fas fa-history"></i> Previous Version
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" onclick="closeModal('userInfoModal')">OK</button></div>
        </div>
    </div>
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="confirmModalTitle">Confirm Action</h2><button class="close-button" onclick="closeModal('confirmModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage">Are you sure?</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-cancel" onclick="closeModal('confirmModal')">Cancel</button><button type="button" id="confirmModalButton" class="btn btn-danger">Confirm</button></div>
        </div>
    </div>
    <div id="actionPopover" class="action-popover"></div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let storageChartInstance = null;
        let plyrInstance = null;

        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('show');
            if (id === 'previewModal') {
                if (plyrInstance) {
                    plyrInstance.destroy();
                    plyrInstance = null;
                }
                document.getElementById('previewContent').innerHTML = '';
            }
        }

        function toggleTheme() {
            const isLight = document.body.classList.toggle('light-mode');
            document.cookie = `theme=${isLight ? 'light' : 'dark'}; path=/; max-age=31536000`;
            if (storageChartInstance) {
                storageChartInstance.destroy();
                renderStorageChart();
            }
        }

        const sidebar = document.querySelector('.sidebar'),
            overlay = document.getElementById('overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function openNewFolderModal() {
            openModal('newFolderModal');
            document.getElementById('folderName').focus();
        }

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

        function showUserInfoModal() {
            openModal('userInfoModal');
            setTimeout(() => {
                if (!storageChartInstance) renderStorageChart();
            }, 100);
        }

        function renderStorageChart() {
            const storageData = <?php echo json_encode($storageBreakdownForJs); ?>;
            const ctx = document.getElementById('storageChart').getContext('2d');
            const isDarkMode = !document.body.classList.contains('light-mode');
            const textColor = isDarkMode ? 'rgba(240, 240, 240, 0.8)' : 'rgba(28, 28, 30, 0.8)';
            if (storageChartInstance) storageChartInstance.destroy();
            if (storageData.labels.length === 0) {
                document.getElementById('storageChartContainer').innerHTML = '<p style="text-align:center; color: var(--text-secondary); padding-top: 50px;">No file data to display.</p>';
                return;
            }
            storageChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: storageData.labels,
                    datasets: [{
                        data: storageData.data,
                        backgroundColor: ['#0a84ff', '#5ac8fa', '#ff9500', '#ff3b30', '#34c759', '#ffcc00', '#af52de', '#5856d6'],
                        borderColor: isDarkMode ? '#1d1d20' : '#ffffff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor,
                                padding: 15,
                                font: {
                                    size: 13
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => `${c.label || ''}: ${formatBytesJS(c.parsed || 0)}`
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        function openRenameModal(id, oldName, type) {
            openModal('renameModal');
            document.getElementById('renameItemId').value = id;
            const input = document.getElementById('newName');
            if (type === 'file') {
                const lastDot = oldName.lastIndexOf('.');
                input.value = lastDot > 0 ? oldName.substring(0, lastDot) : oldName;
            } else {
                input.value = oldName;
            }
            input.focus();
            input.select();
        }

        function openShareModal(id, shareId = '') {
            openModal('shareModal');
            const linkInput = document.getElementById('shareLinkInput'),
                statusMsg = document.getElementById('shareStatusMessage');
            linkInput.value = 'Generating...';
            statusMsg.textContent = '';
            if (shareId) {
                linkInput.value = `${BASE_URL}/share.php?id=${shareId}`;
                return;
            }
            const formData = new FormData();
            formData.append('create_share_link', true);
            formData.append('file_id', id);
            fetch('share.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(d => {
                if (d.success) linkInput.value = `${BASE_URL}/share.php?id=${d.share_id}`;
                else {
                    linkInput.value = 'Error';
                    statusMsg.textContent = d.message;
                }
            });
        }

        function copyShareLink() {
            const input = document.getElementById('shareLinkInput');
            input.select();
            document.execCommand('copy');
            document.getElementById('shareStatusMessage').textContent = 'Copied!';
        }

        function openPreviewModal(name, id) {
            openModal('previewModal');
            const title = document.getElementById('previewModalTitle'),
                content = document.getElementById('previewContent');
            title.textContent = name;
            content.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:200px;"><div class="loading-spinner"></div></div>';

            fetch(`preview.php?id=${id}`).then(r => r.json()).then(d => {
                if (!d.success) {
                    content.innerHTML = `<p style="padding:20px;">${d.message}</p>`;
                    return;
                }
                let html = '';
                switch (d.type) {
                    case 'image':
                        html = `<img src="${d.data}" alt="${name}">`;
                        break;
                    case 'video':
                        html = `<video id="media-player" playsinline controls><source src="${d.data}" type="${d.mime_type}"></video>`;
                        break;
                    case 'audio':
                        html = `<audio id="media-player" controls><source src="${d.data}" type="${d.mime_type}"></audio>`;
                        break;
                    case 'pdf':
                        html = `<div id="pdf-viewer-container"><canvas id="pdf-canvas"></canvas></div>`;
                        break;
                    case 'code':
                        html = `<pre class="line-numbers" style="width:100%;height:100%;margin:0;max-height:calc(90vh - 55px);"><code class="language-${d.language}">${escapeHtml(d.data)}</code></pre>`;
                        break;
                    default:
                        html = `<div style="text-align:left;width:100%;padding:20px;"><p><strong>Name:</strong> ${d.data.name}</p><p><strong>Size:</strong> ${d.data.size}</p><p><em>No preview available.</em></p></div>`;
                        break;
                }
                content.innerHTML = html;

                if (d.type === 'video' || d.type === 'audio') {
                    plyrInstance = new Plyr('#media-player', {
                        autoplay: true
                    });
                } else if (d.type === 'code') {
                    Prism.highlightAllUnder(content);
                } else if (d.type === 'pdf') {
                    renderPdf(d.data);
                }
            });
        }

        function confirmEmptyTrash() {
            showConfirmModal('Confirm Empty Trash', 'This will permanently delete all items in the trash. This action cannot be undone.', () => {
                window.location.href = 'empty_trash.php';
            });
        }

        function handleAction(event, action, id, extra = '') {
            event.stopPropagation();
            const row = event.target.closest('tr');
            if (!row) return;
            const name = row.dataset.name,
                type = row.dataset.type;
            switch (action) {
                case 'rename':
                    openRenameModal(id, name, type);
                    break;
                case 'share':
                    openShareModal(id, extra);
                    break;
                case 'preview':
                    openPreviewModal(name, id);
                    break;
            }
        }

        function requestDeletion(event, url, message, title = 'Confirm Deletion') {
            event.preventDefault();
            event.stopPropagation();
            showConfirmModal(title, message, () => {
                window.location.href = url;
            });
        }

        const tableBody = document.querySelector('.file-table tbody');
        if (tableBody) {
            tableBody.addEventListener('click', (e) => {
                const row = e.target.closest('tr.selectable');
                if (!row || e.target.closest('a, button')) return;
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
                    if (checkbox) checkbox.checked = !checkbox.checked;
                }
                row.classList.toggle('selected', checkbox.checked);
                updateToolbarState();
            });
        }

        function toggleSelectAll(isChecked) {
            tableBody.querySelectorAll('tr.selectable').forEach(row => {
                row.classList.toggle('selected', isChecked);
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = isChecked;
            });
            updateToolbarState();
        }

        function updateToolbarState() {
            const selectedCount = document.querySelectorAll('tr.selected').length;
            document.getElementById('batch-delete-btn').classList.toggle('disabled', selectedCount === 0);
            document.getElementById('batch-download-btn').classList.toggle('disabled', selectedCount === 0);
            const totalRows = document.querySelectorAll('tr.selectable').length;
            document.getElementById('select-all-checkbox').checked = (totalRows > 0 && selectedCount === totalRows);
        }

        function batchDeleteSelected() {
            const selectedIds = Array.from(document.querySelectorAll('tr.selected')).map(row => row.dataset.id);
            if (selectedIds.length === 0) return;
            showConfirmModal(`Confirm Deletion`, `Are you sure you want to move ${selectedIds.length} item(s) to the trash?`, () => {
                fetch('delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: selectedIds
                        })
                    })
                    .then(r => r.json()).then(d => {
                        if (d.success) window.location.reload();
                        else alert('Error: ' + d.message);
                    });
            });
        }

        function batchDownloadSelected() {
            const selectedIds = Array.from(document.querySelectorAll('tr.selected')).map(row => row.dataset.id);
            if (selectedIds.length === 0) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'create_archive.php';
            
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        let draggedItemId = null;
        document.querySelectorAll('tr[draggable="true"]').forEach(row => {
            row.addEventListener('dragstart', e => {
                draggedItemId = e.currentTarget.dataset.id;
                e.currentTarget.classList.add('dragged');
                e.dataTransfer.setData('text/plain', draggedItemId);
            });
            row.addEventListener('dragend', e => e.currentTarget.classList.remove('dragged'));
            if (row.dataset.type === 'folder') {
                row.addEventListener('dragover', e => {
                    e.preventDefault();
                    if (draggedItemId && draggedItemId !== e.currentTarget.dataset.id) e.currentTarget.classList.add('drag-over');
                });
                row.addEventListener('dragleave', e => e.currentTarget.classList.remove('drag-over'));
                row.addEventListener('drop', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    e.currentTarget.classList.remove('drag-over');
                    const destId = e.currentTarget.dataset.id;
                    if (draggedItemId && draggedItemId !== destId) moveItem(draggedItemId, destId);
                });
            }
        });

        function moveItem(itemId, destId) {
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('destination_id', destId);
            fetch('move.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(d => {
                if (d.success) window.location.reload();
                else alert('Move failed: ' + d.message);
            });
        }

        const actionPopover = document.getElementById('actionPopover');

        function showActionPopover(btn, e) {
            e.preventDefault();
            e.stopPropagation();
            const row = btn.closest('tr'),
                id = row.dataset.id,
                name = row.dataset.name,
                type = row.dataset.type,
                shareId = row.dataset.shareId || '';
            const page = '<?php echo $currentPage; ?>';
            let content = '';
            if (page === 'trash') {
                const pUrl = `delete.php?id=${id}&force_delete=true`,
                    pMsg = 'This will permanently delete the item. This action cannot be undone.';
                content = `<a class="popover-item" href="restore.php?id=${id}"><i class="fas fa-redo-alt"></i> Restore</a><a href="#" class="popover-item" onclick="requestDeletion(event, '${pUrl}', '${pMsg}', 'Confirm Permanent Deletion')"><i class="fas fa-times"></i> Delete Forever</a>`;
            } else {
                if (type !== 'folder') {
                    content += `<a class="popover-item" href="download.php?id=${id}"><i class="fas fa-download"></i> Download</a><button type="button" class="popover-item" onclick="openShareModal(${id},'${shareId}')"><i class="fas fa-share-alt"></i> Share</button><button type="button" class="popover-item" onclick="openPreviewModal('${escapeJS(name)}', ${id})"><i class="fas fa-eye"></i> View</button>`;
                }
                const dUrl = `delete.php?id=${id}`,
                    dMsg = 'Are you sure you want to move this item to the trash?';
                content += `<button type="button" class="popover-item" onclick="openRenameModal(${id},'${escapeJS(name)}', '${type}')"><i class="fas fa-edit"></i> Rename</button><a href="#" class="popover-item" onclick="requestDeletion(event, '${dUrl}', '${dMsg}')"><i class="fas fa-trash-alt"></i> Trash</a>`;
            }
            actionPopover.innerHTML = content;
            const rect = btn.getBoundingClientRect();
            actionPopover.classList.add('show');
            const popoverRect = actionPopover.getBoundingClientRect();
            let top = rect.bottom + 5,
                left = rect.right - popoverRect.width;
            if (top + popoverRect.height > window.innerHeight) {
                top = rect.top - popoverRect.height - 5;
            }
            if (left < 5) {
                left = 5;
            }
            actionPopover.style.top = `${top}px`;
            actionPopover.style.left = `${left}px`;
        }

        function escapeJS(str) {
            return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        const CHUNK_SIZE = 2 * 1024 * 1024,
            MAX_PARALLEL_UPLOADS = 4,
            MAX_RETRIES = 3;
        let parentIdForUpload = <?php echo htmlspecialchars($currentFolderId); ?>;

        function openUploadModal() {
            parentIdForUpload = <?php echo htmlspecialchars($currentFolderId); ?>;
            openModal('uploadModal');
        }
        const dropZone = document.getElementById('drop-zone'),
            fileInput = document.getElementById('file-input-chunk'),
            progressList = document.getElementById('upload-progress-list');
        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files);
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) handleFiles(fileInput.files);
            fileInput.value = '';
        });

        function handleFiles(files) {
            for (const file of files) uploadFile(file);
        }

        function createProgressItem(file) {
            const fileInfo = getFileIconJS(file.name);
            const item = document.createElement('div');
            item.className = 'progress-item';
            item.innerHTML = `<i class="fas ${fileInfo.icon} file-icon" style="color: ${fileInfo.color};"></i><div class="progress-info"><div class="file-name">${escapeHtml(file.name)}</div><div class="progress-bar-container"><div class="progress-bar"></div></div><div class="progress-status">Initializing...</div></div><div class="status-icon"></div>`;
            progressList.appendChild(item);
            return item;
        }
        async function uploadFile(file) {
            const item = createProgressItem(file),
                bar = item.querySelector('.progress-bar'),
                status = item.querySelector('.progress-status'),
                icon = item.querySelector('.status-icon');
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
            status.textContent = 'Preparing...';
            const startForm = new FormData();
            startForm.append('action', 'start');
            startForm.append('fileName', file.name);
            startForm.append('fileSize', file.size);
            startForm.append('mimeType', file.type);
            startForm.append('parentId', parentIdForUpload);
            let fileId;
            try {
                const res = await fetch('chunk_upload.php', {
                    method: 'POST',
                    body: startForm
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Server responded with an error.');
                }
                fileId = data.fileId;
            } catch (e) {
                status.textContent = 'Error: ' + e.message;
                icon.innerHTML = `<i class="fas fa-times-circle error"></i>`;
                return;
            }
            const chunkQueue = Array.from({
                length: totalChunks
            }, (_, i) => i);
            let progress = 0;
            const uploadWorker = async () => {
                while (chunkQueue.length > 0) {
                    const chunkIndex = chunkQueue.shift();
                    let retries = 0;
                    while (retries < MAX_RETRIES) {
                        try {
                            const start = chunkIndex * CHUNK_SIZE;
                            const chunk = file.slice(start, start + CHUNK_SIZE);
                            const chunkForm = new FormData();
                            chunkForm.append('action', 'upload');
                            chunkForm.append('fileId', fileId);
                            chunkForm.append('chunkIndex', chunkIndex);
                            chunkForm.append('chunk', chunk);
                            const res = await fetch('chunk_upload.php', {
                                method: 'POST',
                                body: chunkForm
                            });
                            if (!res.ok) throw new Error(`HTTP ${res.status}`);
                            const data = await res.json();
                            if (!data.success) throw new Error(data.message);
                            progress++;
                            updateProgress(progress, totalChunks, bar, status);
                            break;
                        } catch (e) {
                            retries++;
                            if (retries >= MAX_RETRIES) {
                                throw new Error(`Chunk ${chunkIndex + 1} failed.`);
                            }
                            await new Promise(r => setTimeout(r, 1000 * retries));
                        }
                    }
                }
            };
            const workers = Array(MAX_PARALLEL_UPLOADS).fill(null).map(uploadWorker);
            try {
                await Promise.all(workers);
            } catch (e) {
                status.textContent = 'Upload failed: ' + e.message;
                icon.innerHTML = `<i class="fas fa-times-circle error"></i>`;
                return;
            }
            status.textContent = 'Assembling file...';
            const completeForm = new FormData();
            completeForm.append('action', 'complete');
            completeForm.append('fileId', fileId);
            completeForm.append('totalChunks', totalChunks);
            try {
                const res = await fetch('chunk_upload.php', {
                    method: 'POST',
                    body: completeForm
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Finalization failed.');
                status.textContent = 'Complete!';
                icon.innerHTML = `<i class="fas fa-check-circle success"></i>`;
                setTimeout(() => {
                    if (document.getElementById('uploadModal').classList.contains('show')) window.location.reload();
                }, 1200);
            } catch (e) {
                status.textContent = 'Finalization failed: ' + e.message;
                icon.innerHTML = `<i class="fas fa-exclamation-circle error"></i>`;
            }
        }

        function updateProgress(chunkNum, totalChunks, bar, status) {
            const percent = totalChunks > 0 ? Math.round((chunkNum / totalChunks) * 100) : 100;
            bar.style.width = `${percent}%`;
            status.textContent = `Uploading... ${percent}%`;
        }

        function getFileIconJS(name) {
            const ext = name.split('.').pop().toLowerCase();
            switch (ext) {
                case 'pdf':
                    return {
                        icon: 'fa-file-pdf', color: '#e62e2e'
                    };
                case 'doc':
                case 'docx':
                    return {
                        icon: 'fa-file-word', color: '#2a5699'
                    };
                case 'zip':
                    return {
                        icon: 'fa-file-archive', color: '#f0ad4e'
                    };
                default:
                    return {
                        icon: 'fa-file', color: '#8a8a8e'
                    };
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function formatBytesJS(bytes, decimals = 2) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        async function renderPdf(url) {
            const {
                pdfjsLib
            } = globalThis;
            pdfjsLib.GlobalWorkerOptions.workerSrc = `./src/js/pdf.worker.mjs`;
            const pdfDoc = await pdfjsLib.getDocument(url).promise;
            const viewer = document.getElementById('pdf-viewer-container');
            for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                const page = await pdfDoc.getPage(pageNum);
                const viewport = page.getViewport({
                    scale: 1.5
                });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.style.marginBottom = '10px';
                viewer.appendChild(canvas);
                await page.render({
                    canvasContext: context,
                    viewport: viewport
                }).promise;
            }
        }
        window.addEventListener('click', e => {
            if (e.target.classList.contains('modal')) closeModal(e.target.id);
            const popover = document.getElementById('actionPopover');
            if (popover.classList.contains('show') && !e.target.closest('.action-btn')) {
                popover.classList.remove('show');
            }
        });
        document.querySelectorAll('.tab-nav-item').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabContainer = tab.closest('.modal-body');
                tabContainer.querySelectorAll('.tab-nav-item, .tab-pane').forEach(el => el.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>

</html>