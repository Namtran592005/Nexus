<?php
require_once "bootstrap.php"; // Chỉ cần gọi bootstrap là đủ

// Lấy thông tin chung không đổi
$totalSize =
    $pdo
        ->query(
            "SELECT SUM(size) FROM file_system WHERE is_deleted = 0 AND type = 'file'"
        )
        ->fetchColumn() ?:
    0;
$totalStorageBytes = TOTAL_STORAGE_GB * 1024 * 1024 * 1024;
$usagePercentage =
    $totalStorageBytes > 0 ? ($totalSize / $totalStorageBytes) * 100 : 0;

// Lấy dữ liệu cho biểu đồ (chỉ cần 1 lần)
$stmt = $pdo->query(
    "SELECT mime_type, SUM(size) as total_size FROM file_system WHERE type = 'file' AND is_deleted = 0 GROUP BY mime_type"
);
$storageBreakdownRaw = $stmt->fetchAll();
$storageBreakdown = [];
foreach ($storageBreakdownRaw as $row) {
    $category = getFileTypeCategory($row["mime_type"]);
    if (!isset($storageBreakdown[$category])) {
        $storageBreakdown[$category] = 0;
    }
    $storageBreakdown[$category] += (int) $row["total_size"];
}
arsort($storageBreakdown);
$storageBreakdownForJs = [
    "labels" => array_keys($storageBreakdown),
    "data" => array_values($storageBreakdown),
];

// Lấy thông tin view ban đầu từ URL để JS có thể tải lần đầu
$initial_view = $_GET["view"] ?? "browse";
$initial_path = $_GET["path"] ?? "";
$initial_query = $_GET["q"] ?? "";

// Lấy thông báo session
$session_message = "";
if (isset($_SESSION["message"])) {
    $session_message = json_encode($_SESSION["message"]);
    unset($_SESSION["message"]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="./src/image/favicon.ico">
    <link rel="stylesheet" href="./src/custom-fonts.css">
    <link rel="stylesheet" href="./src/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./src/css/plyr.css" />
    <script src="./src/js/plyr.js"></script>
    <script src="./src/js/chart.js"></script>
    <script src="./src/js/docx-preview.js"></script>
    <script src="./src/js/xlsx.full.min.js"></script>
    <!-- ACE Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.15.2/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.15.2/ext-themelist.js"></script>
    <!-- Flatpickr - Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
    /* --- CSS is unchanged, it remains the same as in your original file --- */
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
        /* Bảng màu tối mới, lấy cảm hứng từ GitHub Dark & VSCode Dark */
        --bg-primary: #0d1117;
        /* Nền chính, xám đen rất đậm có ánh xanh */
        --bg-secondary: #161b22;
        /* Các panel chính, header, sidebar */
        --bg-tertiary: #21262d;
        /* Nền cho input, các khối phụ */

        --text-primary: #c9d1d9;
        /* Màu chữ trắng ngà, dịu mắt hơn trắng tinh */
        --text-secondary: #8b949e;
        /* Màu chữ phụ, xám xanh nhạt */
        --text-accent: #58a6ff;
        /* Màu xanh dương nhấn mạnh, sáng vừa phải */

        --border-color: #30363d;
        /* Viền xám, đủ để phân tách mà không quá sáng */
        --highlight-color: #21262d;
        /* Màu khi hover, đồng bộ bg-tertiary */
        --selection-color: rgba(56, 139, 253, 0.15);
        /* Màu chọn, xanh nhạt */

        --shadow-color: rgba(0, 0, 0, 0.4);
        /* Bóng đổ cho chế độ tối */

        --danger-color: #f85149;
        /* Màu đỏ cảnh báo, sáng hơn một chút */
        --danger-color-hover: #da3633;
        --success-color: #3fb950;
        /* Màu xanh lá thành công, dễ nhìn hơn */

        --plyr-color-main: var(--text-accent);
    }

    body.light-mode {
        /* Bảng màu mới, hiện đại và dễ chịu hơn */
        --bg-primary: #f6f8fa;
        /* Nền xám xanh rất nhạt, sạch sẽ */
        --bg-secondary: #ffffff;
        /* Các panel chính màu trắng tinh */
        --bg-tertiary: #f0f2f5;
        /* Nền cho input, các khối phụ */

        --text-primary: #1f2328;
        /* Màu chữ đen đậm, rõ ràng */
        --text-secondary: #57606a;
        /* Màu chữ phụ, xám xanh */
        --text-accent: #0969da;
        /* Màu xanh dương nhấn mạnh, chuẩn GitHub/VSCode */

        --border-color: #d0d7de;
        /* Viền xám nhạt, tinh tế */
        --highlight-color: #f0f2f5;
        /* Màu khi hover, đồng bộ với bg-tertiary */
        --selection-color: rgba(56, 139, 253, 0.15);
        /* Màu chọn, xanh nhạt hơn */

        --shadow-color: rgba(140, 149, 159, 0.15);
        /* Bóng đổ nhẹ nhàng hơn */

        --danger-color: #d73a49;
        /* Màu đỏ cảnh báo */
        --danger-color-hover: #b92534;
        --success-color: #1a7f37;
        /* Màu xanh lá thành công */

        --plyr-color-main: var(--text-accent);
    }

    /* --- CSS MỚI CHO NỀN ĐỘNG --- */
    .animated-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        /* Đặt nó ở lớp dưới cùng */
        --gradient-color-1: #0d1117;
        --gradient-color-2: #161b22;
        --gradient-color-3: #0969da;
        --gradient-color-4: #58a6ff;

        background: linear-gradient(-45deg, var(--gradient-color-1), var(--gradient-color-2), var(--gradient-color-3), var(--gradient-color-4));
        background-size: 400% 400%;
        animation: gradient-animation 15s ease infinite;
        opacity: 0.2;
        /* Giảm độ mờ để không quá chói */
        transition: opacity 0.5s ease;
    }

    /* Định nghĩa animation */
    @keyframes gradient-animation {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    /* Điều chỉnh màu sắc cho chế độ sáng */
    body.light-mode .animated-bg {
        --gradient-color-1: #f6f8fa;
        --gradient-color-2: #ffffff;
        --gradient-color-3: #58a6ff;
        --gradient-color-4: #0969da;
        opacity: 0.15;
        /* Độ mờ ở chế độ sáng cần nhẹ hơn nữa */
    }

    /* Điều chỉnh màu sắc cho chế độ tối */
    body.dark-mode .animated-bg {
        --gradient-color-1: #0d1117;
        --gradient-color-2: #161b22;
        --gradient-color-3: #1f2328;
        --gradient-color-4: #0969da;
        opacity: 0.1;
        /* Giảm opacity cho nền tối để dịu mắt hơn */
    }

    /* General Body Styles */
    body {
        font-family: var(--font-family-sans);
        margin: 0;
        background-color: transparent;
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
        transition: background-color var(--transition-speed-fast) ease, margin-right var(--transition-speed-normal) var(--transition-timing-function);
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

    /* Header */
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

    .header .user-info {
        color: var(--text-secondary);
        font-size: 0.9em;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Main Layout */
    .main-content {
        display: flex;
        flex: 1;
        overflow: hidden;
        position: relative;
        background-color: var(--bg-primary);
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

    /* Sidebar */
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

    .sidebar-storage-info .details {
        display: flex;
        justify-content: space-between;
        color: var(--text-secondary);
        margin-bottom: 8px;
        font-size: 0.85em;
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

    /* Content Area */
    .content-area {
        flex: 1;
        padding: 30px;
        overflow-y: auto;
        background-color: transparent;
    }

    .main-content.details-panel-active .content-area {
        margin-right: 320px;
    }

    .content-header,
    .toolbar,
    .breadcrumbs,
    .file-table {
        animation: fadeInUp 0.5s var(--transition-timing-function) both;
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
        justify-content: space-between;
        margin-bottom: 25px;
    }

    .toolbar .left-actions,
    .toolbar .right-actions {
        display: flex;
        gap: 10px;
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

    /* Breadcrumbs */
    .breadcrumbs {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 25px;
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

    /* File Table */
    .file-table {
        width: 100%;
        border-collapse: collapse;
    }

    #file-list-container.grid-view .file-table {
        display: none;
    }

    /* Grid View */
    #grid-view-container {
        display: none;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 20px;
    }

    #file-list-container.grid-view #grid-view-container {
        display: grid;
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
    }

    .file-table tbody tr.selected {
        background-color: var(--selection-color) !important;
        color: var(--text-primary);
    }

    .file-table tbody tr:not(.selected):hover {
        background-color: var(--highlight-color);
    }

    .file-table tbody tr.dragged {
        opacity: 0.5;
        background-color: var(--highlight-color);
    }

    .file-table tr[data-type="folder"].drag-over {
        background-color: var(--selection-color);
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
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
        display: inline-flex;
    }

    /* Always show on mobile */
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

    .hidden-file-input {
        display: none;
    }

    #grid-view-container {
        display: none;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 20px;
    }

    .file-list-container.grid-view #grid-view-container {
        display: grid;
    }

    .grid-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 20px 10px;
        border-radius: var(--radius-default);
        cursor: pointer;
        transition: background-color var(--transition-speed-fast) ease;
        word-break: break-word;
    }

    .grid-item:hover {
        background-color: var(--highlight-color);
    }

    .grid-item.selected {
        background-color: var(--selection-color) !important;
        box-shadow: 0 0 0 2px var(--text-accent) inset;
    }

    .grid-item .grid-icon {
        font-size: 4em;
        margin-bottom: 15px;
        width: 50px;
        text-align: center;
    }

    .grid-item .grid-name {
        font-weight: 500;
        color: var(--text-primary);
        font-size: 0.9em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .grid-item .grid-checkbox-overlay {
        position: absolute;
        top: 10px;
        left: 10px;
    }

    .search-form-wrapper {
        position: relative;
    }

    #live-search-results {
        display: none;
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-default);
        box-shadow: 0 5px 15px var(--shadow-color);
        z-index: 101;
        max-height: 400px;
        overflow-y: auto;
    }

    .live-search-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        text-decoration: none;
        color: var(--text-primary);
    }

    .live-search-item:hover {
        background-color: var(--highlight-color);
    }

    .live-search-item i {
        width: 20px;
        text-align: center;
        color: var(--text-secondary);
    }

    .live-search-item-info .name {
        font-weight: 500;
    }

    .live-search-item-info .path {
        font-size: 0.8em;
        color: var(--text-secondary);
    }

    .live-search-spinner {
        padding: 20px;
        text-align: center;
        color: var(--text-secondary);
    }

    #details-panel {
        position: fixed;
        top: var(--header-height);
        right: 0;
        bottom: 0;
        width: 320px;
        background-color: var(--bg-secondary);
        border-left: 1px solid var(--border-color);
        transform: translateX(100%);
        transition: transform var(--transition-speed-normal) var(--transition-timing-function);
        z-index: 90;
        display: flex;
        flex-direction: column;
    }

    #details-panel.active {
        transform: translateX(0);
    }

    .details-panel-header {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .details-panel-header h3 {
        margin: 0;
        font-size: 1.1em;
    }

    .details-panel-body {
        padding: 20px;
        overflow-y: auto;
        flex-grow: 1;
    }

    .details-preview {
        height: 180px;
        background-color: var(--bg-primary);
        border-radius: var(--radius-default);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .details-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .details-preview i {
        font-size: 5em;
        color: var(--text-secondary);
    }

    .details-info-list dt {
        font-size: 0.8em;
        color: var(--text-secondary);
        margin-top: 15px;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .details-info-list dd {
        margin: 0;
        font-size: 0.9em;
        word-wrap: break-word;
    }

    /* Trash Actions */
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

    /* Custom Checkbox */
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

    .file-table input[type="checkbox"]:checked+.custom-checkbox-label::before,
    .grid-item input[type="checkbox"]:checked+.custom-checkbox-label::before {
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

    .file-table input[type="checkbox"]:checked+.custom-checkbox-label::after,
    .grid-item input[type="checkbox"]:checked+.custom-checkbox-label::after {
        transform: rotate(45deg) scale(1);
    }

    .custom-checkbox-label:hover::before {
        border-color: var(--text-accent);
    }

    /* Modals */
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
        flex-grow: 1;
    }

    .modal-header .close-button {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5em;
        cursor: pointer;
        line-height: 1;
        transition: transform var(--transition-speed-fast) ease;
        order: 3;
    }

    .modal-header .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
        padding-right: 15px;
    }

    .modal-header .header-actions .btn,
    .modal-header .header-actions select {
        padding: 5px 10px;
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.9em;
        cursor: pointer;
    }

    .modal-header .header-actions .btn-save {
        background-color: var(--success-color);
        color: white;
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

    /* === BẮT ĐẦU KHỐI CSS NÚT ĐÃ NÂNG CẤP === */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: var(--radius-default);
        cursor: pointer;
        font-weight: 500;
        font-size: 0.95em;
        text-decoration: none;
        /* Quan trọng cho thẻ <a> */
        transition: all var(--transition-speed-fast) var(--transition-timing-function);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px var(--shadow-color);
    }

    .btn-cancel {
        background-color: var(--highlight-color);
        color: var(--text-primary);
    }

    .btn-primary {
        background-color: var(--text-accent);
        color: white;
    }

    .btn-danger {
        background-color: var(--danger-color);
        color: white;
    }

    /* === KẾT THÚC KHỐI CSS NÚT ĐÃ NÂNG CẤP === */

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
        position: relative;
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
        width: 100%;
        height: 100%;
    }

    #code-editor-container {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
    }


    #previewModal .plyr--video,
    #previewModal .plyr--audio {
        max-height: calc(90vh - 55px);
    }

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

    #toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1050;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast {
        padding: 12px 20px;
        border-radius: var(--radius-default);
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px var(--shadow-color);
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .toast-success {
        background-color: var(--success-color);
    }

    .toast-danger {
        background-color: var(--danger-color);
    }

    .toast-info {
        background-color: #5ac8fa;
        color: #1c1c1e;
    }

    @media (max-width: 992px) {
        .toolbar .icon-btn span {
            display: none;
        }

        .toolbar .icon-btn {
            padding: 8px 12px;
        }

        .main-content.details-panel-active .content-area {
            margin-right: 0;
        }

        #details-panel {
            width: 100%;
            z-index: 140;
        }
    }

    @media (max-width: 768px) {
        .content-area {
            padding: 20px;
        }

        .header .logo span,
        .header .user-info {
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

        .file-table .file-actions .more-actions-btn {
            display: inline-flex;
        }

        .file-table tr:hover .file-actions,
        .file-table tr.selected .file-actions,
        .file-actions {
            opacity: 1;
        }
    }

    #file-list-container.loading {
        opacity: 0.5;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .folder-tree ul {
        list-style: none;
        padding-left: 20px;
    }

    .folder-tree li {
        padding: 5px 0;
    }

    .folder-tree .folder-item {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
        border-radius: 6px;
    }

    .folder-tree .folder-item:hover {
        background-color: var(--highlight-color);
    }

    .folder-tree .folder-item.selected {
        background-color: var(--selection-color);
        font-weight: 600;
    }

    .folder-tree .toggle-icon {
        width: 16px;
        text-align: center;
        transition: transform 0.2s ease;
    }

    .folder-tree .toggle-icon.collapsed {
        transform: rotate(-90deg);
    }

    .modal .form-group {
        margin-bottom: 15px;
    }

    .modal .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: var(--text-secondary);
        font-size: 0.9em;
    }

    .modal .form-group input[type="password"],
    .modal .form-group input[type="date"] {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-default);
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        font-size: 1em;
        transition: border-color var(--transition-speed-fast);
    }

    .modal .form-group input[type="password"]:focus,
    .modal .form-group input[type="date"]:focus {
        outline: none;
        border-color: var(--text-accent);
    }

    .modal .form-group-inline {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal .form-group-inline label {
        margin-bottom: 0;
        cursor: pointer;
    }

    .about-section .app-description {
        max-width: 450px;
        margin: 0 auto 25px auto;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .developer-info {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .developer-info h4 {
        font-size: 0.9em;
        color: var(--text-secondary);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
    }

    .developer-links a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0 10px;
        color: var(--text-accent);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .developer-links a:hover {
        color: var(--text-primary);
    }

    #previewModal .modal-content-fullscreen {
        width: 90vw;
        max-width: 1400px;
        height: 90vh;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    #previewModal .modal-content-fullscreen .modal-body {
        flex-grow: 1;
        padding: 0;
        overflow: hidden;
    }

    #previewModal .modal-content-fullscreen #previewContent,
    #previewModal .modal-content-fullscreen #previewContent iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .preview-overlay {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        background: transparent;
    }

    .sheet-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 0.9em;
        color: var(--text-primary);
    }

    .sheet-table th,
    .sheet-table td {
        border: 1px solid var(--border-color);
        padding: 8px;
        text-align: left;
    }

    .sheet-table th {
        background-color: var(--bg-tertiary);
        font-weight: bold;
    }

    /* --- CSS MỚI CHO CHẾ ĐỘ XEM TRƯỚC TOÀN MÀN HÌNH --- */
    body.preview-maximized .header,
    body.preview-maximized .main-content {
        display: none;
        /* Ẩn giao diện chính của app */
    }

    .modal.modal-maximized {
        padding: 0;
        background: none;
    }

    .modal.modal-maximized .modal-content {
        width: 100vw;
        height: 100vh;
        max-width: 100%;
        max-height: 100%;
        border-radius: 0;
        border: none;
        box-shadow: none;
    }

    /* TÙY CHỈNH THANH CUỘN CHO GIAO DIỆN */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--bg-primary);
    }

    ::-webkit-scrollbar-thumb {
        background-color: var(--bg-tertiary);
        border-radius: 4px;
        border: 2px solid var(--bg-primary);
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: var(--border-color);
    }

    .content-area,
    .sidebar,
    #details-panel-body,
    #live-search-results,
    .modal-body,
    #folder-tree-container,
    #upload-progress-list,
    #previewContent {
        scrollbar-width: thin;
        scrollbar-color: var(--bg-tertiary) var(--bg-primary);
    }

    /* --- CSS MỚI CHO GIAO DIỆN CHỌN NGÀY HẾT HẠN --- */
    .expiry-options-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .expiry-quick-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .expiry-quick-buttons .btn-quick-expiry {
        background-color: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 6px 12px;
        border-radius: var(--radius-default);
        cursor: pointer;
        font-size: 0.85em;
        transition: all 0.2s ease;
    }

    .expiry-quick-buttons .btn-quick-expiry:hover {
        background-color: var(--highlight-color);
        color: var(--text-primary);
        border-color: var(--text-accent);
    }

    .expiry-quick-buttons .btn-quick-expiry.active {
        background-color: var(--selection-color);
        color: var(--text-accent);
        border-color: var(--text-accent);
        font-weight: 500;
    }

    .custom-date-picker-wrapper {
        position: relative;
    }

    #shareExpiryCustom {
        background-color: var(--bg-tertiary);
        padding-right: 35px;
        /* Thêm không gian cho nút clear */
        cursor: pointer;
    }

    .clear-date-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        padding: 5px;
        display: none;
        /* Mặc định ẩn */
    }

    .clear-date-btn:hover {
        color: var(--text-primary);
    }

    /* === BẮT ĐẦU KHỐI CSS RESPONSIVE CHO MODAL === */

    @media (max-width: 768px) {

        /* -- Quy tắc chung cho TẤT CẢ các modal trên mobile -- */
        .modal-content {
            /* Chiếm gần hết màn hình, có khoảng đệm nhỏ */
            width: 95%;
            max-width: calc(100vw - 20px);
            max-height: 85vh;
            /* Giảm padding bên trong để có thêm không gian */
            padding: 0;
        }

        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 15px;
            /* Giảm padding từ 24px xuống 15px */
        }

        .modal-header h2 {
            font-size: 1.1em;
            /* Thu nhỏ tiêu đề modal */
        }

        /* -- Quy tắc ĐẶC BIỆT cho Modal Xem trước (Preview & Code Editor) -- */
        #previewModal .modal-content,
        #previewModal.modal-maximized .modal-content {
            /* Ép nó chiếm 100% màn hình, không còn là modal nữa */
            width: 100vw;
            height: 100vh;
            max-width: 100%;
            max-height: 100%;
            border-radius: 0;
            top: 0;
            left: 0;
        }

        #previewModal .modal-header {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        /* Đảm bảo body của modal (chứa code editor) chiếm hết không gian còn lại */
        #previewModal .modal-body {
            height: 100%;
            /* Rất quan trọng cho ACE editor */
        }

        /* -- Quy tắc cho Modal Thông tin User -- */
        #userInfoModal .tab-nav {
            padding: 0 15px;
            /* Giảm padding */
        }

        #userInfoModal .tab-nav-item {
            padding: 12px 10px;
            /* Làm cho các tab nhỏ gọn hơn */
            font-size: 0.9em;
        }

        #storageChartContainer {
            height: 200px;
            /* Giảm chiều cao biểu đồ trên mobile */
        }

        /* Ẩn nút maximize không cần thiết trên mobile vì nó đã full screen */
        .hide-on-mobile {
            display: none !important;
        }
    }

    /* === KẾT THÚC KHỐI CSS RESPONSIVE CHO MODAL === */
    </style>
</head>

<body class="<?php echo isset($_COOKIE["theme"]) &&
$_COOKIE["theme"] === "light"
    ? "light-mode"
    : "dark-mode"; ?>">
    <div class="animated-bg"></div>
    <div id="toast-container"></div>
    <div class="header">
        <div class="left-section">
            <button class="menu-toggle icon-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="logo"><i class="fas fa-cloud-bolt"></i> <span><?php echo APP_NAME; ?></span></div>
        </div>
        <div class="right-section">
            <div class="search-form-wrapper">
                <form action="index.php" method="GET" class="search-form search-form-desktop">
                    <input type="hidden" name="view" value="search">
                    <input type="search" name="q" placeholder="Search files & folders..." class="search-input" value=""
                        autocomplete="off">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
                <div id="live-search-results"></div>
            </div>
            <span class="user-info">Hi, <?php echo htmlspecialchars(
                $_SESSION["username"]
            ); ?></span>
            <a href="logout.php" class="icon-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
            <button class="icon-btn" onclick="toggleTheme()" title="Toggle Theme"><i class="fas fa-adjust"></i></button>
            <button class="icon-btn" onclick="showUserInfoModal()" title="Information"><i
                    class="fas fa-info-circle"></i></button>
        </div>
    </div>
    <div class="main-content">
        <div class="sidebar">
            <div class="sidebar-header">
                <button class="btn-upload" onclick="openUploadModal()"><i class="fas fa-cloud-upload-alt"></i> Upload
                    File</button>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="sidebar-nav-item"><a href="?view=recents" data-view="recents"><i
                                class="far fa-clock"></i> <span>Recents</span></a></li>
                    <li class="sidebar-nav-item"><a href="?view=browse" data-view="browse"><i class="fas fa-folder"></i>
                            <span>Browse</span></a></li>
                    <li class="sidebar-nav-item"><a href="?view=shared" data-view="shared"><i class="fas fa-users"></i>
                            <span>Shared</span></a></li>
                    <li class="sidebar-nav-item"><a href="?view=trash" data-view="trash"><i class="fas fa-trash"></i>
                            <span>Trash</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-storage-info">
                    <div class="details">
                        <span>Storage</span>
                        <span id="storage-usage-text"><?php echo formatBytes(
                            $totalSize
                        ); ?> of
                            <?php echo TOTAL_STORAGE_GB; ?> GB</span>
                    </div>
                    <div class="progress-bar">
                        <div id="storage-usage-bar" class="progress-bar-inner"
                            style="width: <?php echo $usagePercentage; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="overlay" class="overlay" onclick="toggleSidebar(); closeDetailsPanel();"></div>
        <div class="content-area">
            <div class="content-header">
                <h1 id="page-title">Loading...</h1>
                <div id="page-stats" class="stats"></div>
            </div>
            <form action="index.php" method="GET" class="search-form search-form-mobile">
                <input type="hidden" name="view" value="search">
                <input type="search" name="q" placeholder="Search in Drive..." class="search-input" value="">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>
            <div class="toolbar">
                <div class="left-actions">
                    <button type="button" class="icon-btn" onclick="openNewFolderModal()"><i
                            class="fas fa-folder-plus"></i> <span>New Folder</span></button>
                    <button id="batch-move-btn" class="icon-btn disabled" onclick="openMoveModal()"><i
                            class="fas fa-folder-open"></i> <span>Move</span></button>
                    <button id="batch-download-btn" class="icon-btn disabled" onclick="batchDownloadSelected()"><i
                            class="fas fa-file-archive"></i> <span>Download as ZIP</span></button>
                    <button id="batch-restore-btn" class="icon-btn disabled" onclick="batchRestoreSelected()"
                        style="display: none;"><i class="fas fa-redo-alt"></i> <span>Restore</span></button>
                    <button id="batch-delete-btn" class="icon-btn disabled" onclick="batchDeleteSelected(false)"><i
                            class="fas fa-trash"></i> <span>Delete</span></button>
                    <button id="batch-unshare-btn" class="icon-btn disabled" onclick="batchRemoveShareLinks()"
                        style="display: none;"><i class="fas fa-user-slash"></i> <span>Unshare</span></button>
                </div>
                <div class="right-actions">
                    <button type="button" class="icon-btn" id="list-view-btn" onclick="setViewMode('list')"
                        title="List View" style="display: none;"><i class="fas fa-list"></i></button>
                    <button type="button" class="icon-btn" id="grid-view-btn" onclick="setViewMode('grid')"
                        title="Grid View"><i class="fas fa-th-large"></i></button>
                </div>
            </div>
            <div id="main-content-area">
                <div class="no-files"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
        <div id="details-panel">
            <div class="details-panel-header">
                <h3>Details</h3>
                <button class="icon-btn" onclick="closeDetailsPanel()"><i class="fas fa-times"></i></button>
            </div>
            <div id="details-panel-body" class="details-panel-body">
                <div style="text-align:center; padding-top:50px; color:var(--text-secondary);">Select an item to see
                    details.</div>
            </div>
        </div>
    </div>

    <!-- Modals Section -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Files</h2><button class="close-button" onclick="closeModal('uploadModal')"><i
                        class="fas fa-times"></i></button>
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
                <h2>New Folder</h2><button class="close-button" onclick="closeModal('newFolderModal')"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="newFolderForm">
                <div class="modal-body">
                    <input type="hidden" name="parent_id" value="1">
                    <label for="folderName">Name:</label>
                    <input type="text" id="folderName" name="folder_name" required autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('newFolderModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rename</h2><button class="close-button" onclick="closeModal('renameModal')"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="renameForm">
                <div class="modal-body">
                    <input type="hidden" id="renameItemId" name="id">
                    <label for="newName">New Name:</label>
                    <input type="text" id="newName" name="new_name" required autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal('renameModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Rename</button>
                </div>
            </form>
        </div>
    </div>

    <div id="shareModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Share Settings</h2>
                <button class="close-button" onclick="closeModal('shareModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="share-modal-body">
                <input type="hidden" id="shareFileId">
                <div id="share-link-section" style="display: none;">
                    <label>Public Link</label>
                    <div class="form-group" style="display:flex;">
                        <input type="text" id="shareLinkInput" readonly
                            style="flex-grow:1; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                        <button class="btn btn-primary" onclick="copyShareLink()"
                            style="border-radius:0 var(--radius-default) var(--radius-default) 0;"><i
                                class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div id="create-share-link-section">
                    <p style="text-align: center; color: var(--text-secondary);">This file is not currently shared.</p>
                </div>
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 25px 0;">
                <div id="share-options-section" style="display: none;">
                    <h4>Link Settings</h4>
                    <div class="form-group">
                        <label for="sharePassword">Password (optional)</label>
                        <input type="password" id="sharePassword" placeholder="Protect with a password">
                    </div>
                    <div class="form-group">
                        <label for="shareExpiryCustom">Expiration Date (optional)</label>
                        <div class="expiry-options-container">
                            <div class="expiry-quick-buttons">
                                <button type="button" class="btn-quick-expiry" data-days="1">1 Day</button>
                                <button type="button" class="btn-quick-expiry" data-days="7">7 Days</button>
                                <button type="button" class="btn-quick-expiry" data-days="30">30 Days</button>
                            </div>
                            <div class="custom-date-picker-wrapper">
                                <input type="text" id="shareExpiryCustom" placeholder="Or pick a custom date..."
                                    readonly>
                                <button type="button" class="clear-date-btn" id="clearExpiryBtn"
                                    title="Clear date">&times;</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group form-group-inline">
                        <input type="checkbox" id="shareAllowDownload" checked style="width: 18px; height: 18px;">
                        <label for="shareAllowDownload">Allow downloading</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="share-modal-footer">
                <button type="button" class="btn btn-danger" id="removeShareLinkBtn"
                    style="margin-right: auto; display: none;" onclick="removeShareLink()">Remove Link</button>
                <button class="btn btn-cancel" onclick="closeModal('shareModal')">Close</button>
                <button class="btn btn-primary" id="saveShareSettingsBtn" onclick="saveShareSettings()">Create
                    Link</button>
            </div>
        </div>
    </div>

    <div id="moveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Move Items</h2>
                <button class="close-button" onclick="closeModal('moveModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p>Select a destination folder:</p>
                <div id="folder-tree-container"
                    style="max-height: 40vh; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-default); padding: 10px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('moveModal')">Cancel</button>
                <button type="button" id="confirmMoveBtn" class="btn btn-primary" disabled>Move Here</button>
            </div>
        </div>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="previewModalTitle"></h2>
                <div id="previewHeaderActions" class="header-actions" style="display: none;"></div>
                <!-- NÚT MỚI ĐƯỢC THÊM -->
                <button id="previewMaximizeBtn" class="icon-btn hide-on-mobile" onclick="togglePreviewFullscreen()"
                    title="Maximize" style="font-size: 1em; margin-left: auto; margin-right: 10px;">
                    <i class="fas fa-expand"></i>
                </button>
                <button class="close-button" onclick="closeModal('previewModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
        </div>
    </div>

    <div id="userInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Information</h2><button class="close-button" onclick="closeModal('userInfoModal')"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="tab-nav">
                    <div class="tab-nav-item active" data-tab="overview">Overview</div>
                    <div class="tab-nav-item" data-tab="security">Security</div>
                    <div class="tab-nav-item" data-tab="about">About</div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-overview">
                        <label>Storage Usage</label>
                        <div class="sidebar-storage-info">
                            <div class="progress-bar" style="height:10px;margin:8px 0;">
                                <div class="progress-bar-inner" style="width: <?php echo $usagePercentage; ?>%;"></div>
                            </div>
                        </div>
                        <div class="storage-details"
                            style="font-size:0.9em;justify-content:space-between;display:flex;">
                            <span><?php echo formatBytes(
                                $totalSize
                            ); ?> used</span>
                            <span><?php echo TOTAL_STORAGE_GB; ?> GB total</span>
                        </div>
                        <div id="storageChartContainer"><canvas id="storageChart"></canvas></div>

                    </div>
                    <!-- === BẮT ĐẦU KHỐI MÃ BỊ THIẾU === -->
                    <div class="tab-pane" id="tab-security">
                        <h4 style="margin-top:0;">Two-Factor Authentication (2FA)</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9em; line-height: 1.6;">
                            Add an extra layer of security to your account. Once enabled, you will be required to
                            enter a 6-digit code from your authenticator app each time you log in.
                        </p>
                        <a href="setup_2fa.php" class="btn btn-primary" style="margin-top: 10px;">
                            <i class="fas fa-shield-alt"></i> Manage 2FA Settings
                        </a>
                    </div>
                    <!-- === KẾT THÚC KHỐI MÃ BỊ THIẾU === -->
                    <div class="tab-pane" id="tab-about">
                        <div class="about-section">
                            <div class="logo"><i class="fas fa-cloud-bolt"></i></div>
                            <h3><?php echo APP_NAME; ?></h3>
                            <p>Version 2.0 - "Phoenix"</p>

                            <p class="app-description">
                                A modern, lightweight, and self-hostable cloud storage solution.
                                Built with a fast Single Page Application experience, focusing on performance,
                                mobility, and user-friendliness.
                            </p>

                            <div class="developer-info">
                                <h4>Developed & Maintained By</h4>
                                <p style="font-size: 1.2em; font-weight: 500; margin: 0; padding-bottom: 10px;">
                                    <!-- THAY TÊN CỦA BẠN VÀO ĐÂY -->
                                    Nam Trần
                                </p>
                                <div class="developer-links">
                                    <!-- THAY CÁC LIÊN KẾT CỦA BẠN VÀO ĐÂY -->
                                    <a href="https://github.com/namtran592005" target="_blank">
                                        <i class="fab fa-github"></i> GitHub
                                    </a>
                                    <a href="https://tranvohoangnam.id.vn/portfolio" target="_blank">
                                        <i class="fas fa-globe"></i> Portfolio
                                    </a>
                                    <!-- <a href="https://www.facebook.com/namtran5905" target="_blank">
                                        <i class="fab fa-facebook"></i> Facebook
                                    </a>
                                    <a href="https://www.instagram.com/namtran5905/" target="_blank">
                                        <i class="fab fa-instagram"></i> Instagram
                                    </a> -->
                                </div>
                            </div>
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
                <h2 id="confirmModalTitle">Confirm Action</h2><button class="close-button"
                    onclick="closeModal('confirmModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage">Are you sure?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal('confirmModal')">Cancel</button>
                <button type="button" id="confirmModalButton" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </div>

    <div id="actionPopover" class="action-popover"></div>
</body>
<script>
// ===================================================================================
// JAVASCRIPT SECTION - UPDATED FOR REFACTORED BACKEND
// ===================================================================================

const G = {
    BASE_URL: '<?php echo BASE_URL; ?>',
    currentPage: '',
    currentFolderId: 1,
    currentPath: '',
    storageChartInstance: null,
    plyrInstance: null,
    aceEditorInstance: null,
    viewMode: 'list',
    itemsToMove: [],
    destinationFolderId: null
};

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => Array.from(document.querySelectorAll(selector));

function showToast(message, type = 'success') {
    const container = $('#toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        toast.addEventListener('transitionend', () => toast.remove());
    }, 4000);
}

async function apiCall(action, data = {}, method = 'POST', showToastOnError = true) {
    try {
        const isJson = !(data instanceof FormData) && method === 'POST';

        const response = await fetch('api.php' + (method === 'GET' ? '?' + new URLSearchParams({
            action,
            ...data
        }) : ''), {
            method: method,
            headers: isJson ? {
                'Content-Type': 'application/json'
            } : {},
            body: method === 'POST' ? (isJson ? JSON.stringify({
                action,
                ...data
            }) : data) : undefined
        });

        if (!response.ok) {
            const errorText = await response.text();
            try {
                const errorJson = JSON.parse(errorText);
                throw new Error(errorJson.message || `HTTP error! Status: ${response.status}`);
            } catch (e) {
                throw new Error(errorText || `HTTP error! Status: ${response.status}`);
            }
        }

        return await response.json();

    } catch (error) {
        console.error('API Call Error:', {
            action,
            data,
            error
        });
        if (showToastOnError) {
            showToast(error.message, 'danger');
        }
        return Promise.reject(error);
    }
}

async function batchRemoveShareLinks() {
    const selectedItems = $$('.selectable.selected');
    if (selectedItems.length === 0) return;

    // Lấy file_id (được lưu trong data-id)
    const selectedFileIds = selectedItems.map(el => el.dataset.id);

    const message =
        `Are you sure you want to stop sharing ${selectedFileIds.length} item(s)? The public links will no longer work.`;
    showConfirmModal('Confirm Unshare', message, async () => {
        const result = await apiCall('remove_share_link', {
            file_ids: selectedFileIds
        });
        if (result.success) {
            showToast(result.message);
            // Tải lại view "Shared" để cập nhật danh sách
            navigateToPath('?view=shared', true);
        }
    });
}


function renderMainContent(data) {
    G.currentPage = data.view;
    G.currentFolderId = data.currentFolderId;
    G.currentPath = data.currentPath;

    $('#page-title').textContent = data.pageTitle;
    $('#page-stats').textContent = `${data.items.length} items`;
    document.title = `${data.pageTitle} - <?php echo APP_NAME; ?>`;

    $$('.sidebar-nav-item a').forEach(a => a.classList.remove('active'));
    const activeLink = $(`.sidebar-nav-item a[data-view="${data.view}"]`);
    if (activeLink) activeLink.classList.add('active');

    const mainArea = $('#main-content-area');
    let breadcrumbsHTML = '';
    if (data.view === 'browse' && data.breadcrumbs.length > 0) {
        breadcrumbsHTML = `<div class="breadcrumbs">`;
        data.breadcrumbs.forEach((crumb, index) => {
            if (index > 0) breadcrumbsHTML += `<span class="separator">/</span>`;
            if (index === data.breadcrumbs.length - 1 && crumb.path) {
                breadcrumbsHTML += `<span class="current-folder">${escapeHtml(crumb.name)}</span>`;
            } else {
                breadcrumbsHTML +=
                    `<a href="?view=browse&path=${encodeURIComponent(crumb.path)}">${escapeHtml(crumb.name)}</a>`;
            }
        });
        breadcrumbsHTML += `</div>`;
    }

    let trashActionsHTML = '';
    if (data.view === 'trash' && data.items.length > 0) {
        trashActionsHTML =
            `<div class="trash-actions"><button type="button" class="btn-clean" onclick="confirmEmptyTrash()"><i class="fas fa-broom"></i> Empty Trash</button></div>`;
    }

    $('#newFolderForm input[name="parent_id"]').value = G.currentFolderId;

    // --- CẬP NHẬT LOGIC HIỂN THỊ NÚT ---
    const newFolderBtn = $('.toolbar .left-actions .icon-btn[onclick="openNewFolderModal()"]');
    const batchRestoreBtn = $('#batch-restore-btn');
    const batchDeleteBtn = $('#batch-delete-btn');
    const batchMoveBtn = $('#batch-move-btn');
    const batchDownloadBtn = $('#batch-download-btn');
    const batchUnshareBtn = $('#batch-unshare-btn');

    // Ẩn tất cả các nút hành động theo mặc định, sau đó bật lại các nút cần thiết cho view hiện tại
    [newFolderBtn, batchRestoreBtn, batchDeleteBtn, batchMoveBtn, batchDownloadBtn, batchUnshareBtn].forEach(btn => btn
        .style.display = 'none');

    if (data.view === 'browse' || data.view === 'recents' || data.view === 'search') {
        if (data.view === 'browse') newFolderBtn.style.display = 'flex';
        batchMoveBtn.style.display = 'flex';
        batchDownloadBtn.style.display = 'flex';
        batchDeleteBtn.style.display = 'flex';
        batchDeleteBtn.querySelector('span').textContent = 'Delete';
    } else if (data.view === 'trash') {
        batchRestoreBtn.style.display = 'flex';
        batchDeleteBtn.style.display = 'flex';
        batchDeleteBtn.querySelector('span').textContent = 'Delete Permanently';
    } else if (data.view === 'shared') {
        batchDownloadBtn.style.display = 'flex';
        batchUnshareBtn.style.display = 'flex';
    }
    // --- KẾT THÚC CẬP NHẬT ---


    let contentHTML = '';
    if (data.items.length === 0) {
        contentHTML = '<div class="no-files"><i class="fas fa-box-open"></i><p>This folder is empty.</p></div>';
    } else {
        let tableRows = '';
        let gridItems = '';

        if (data.view === 'browse' && data.parentPath !== null) {
            const parentUrl = `?view=browse&path=${encodeURIComponent(data.parentPath)}`;
            tableRows +=
                `<tr data-type="parent-folder"><td></td><td class="file-name-cell"><a href="${parentUrl}"><i class="fas fa-level-up-alt"></i><span class="file-text">..</span></a></td><td>Folder</td><td>--</td><td>--</td><td></td></tr>`;
            gridItems +=
                `<div class="grid-item" data-type="parent-folder" onclick="navigateToPath('${parentUrl}')"><i class="fas fa-level-up-alt grid-icon"></i><span class="grid-name">..</span></div>`;
        }

        data.items.forEach(item => {
            tableRows += renderFileRowHTML(item);
            gridItems += renderGridItemHTML(item);
        });

        contentHTML = `
                <div id="file-list-container">
                    <table class="file-table">
                        <thead>
                            <tr>
                                <th><input style="display: none;" type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this.checked)"><label for="select-all-checkbox" class="custom-checkbox-label"></label></th>
                                <th>Name</th><th>Kind</th><th>Size</th><th>Date Modified</th><th></th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>
                    <div id="grid-view-container">${gridItems}</div>
                </div>`;
    }

    mainArea.innerHTML = breadcrumbsHTML + trashActionsHTML + contentHTML;

    setViewMode(G.viewMode);
    updateToolbarState();
    closeDetailsPanel();
}

async function navigateToPath(url, isPopState = false) {
    const fullUrl = new URL(url, G.BASE_URL);
    if (!isPopState) {
        history.pushState({
            path: url
        }, '', url);
    }

    $('#main-content-area').innerHTML = '<div class="no-files"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    const params = Object.fromEntries(fullUrl.searchParams);
    const data = await apiCall('get_view_data', params, 'GET');

    if (data.success) {
        renderMainContent(data);
    } else {
        showToast(data.message, 'danger');
        $('#main-content-area').innerHTML =
            `<div class="no-files"><i class="fas fa-exclamation-triangle"></i><p>${data.message}</p></div>`;
    }
}

function renderFileRowHTML(item) {
    const fileInfo = getFileIconJS(item.name, item.type === 'folder');
    const kind = item.type === 'folder' ? 'Folder' : (item.name.split('.').pop().toUpperCase() || 'File');
    const size = item.type === 'folder' ? '--' : formatBytesJS(item.size);
    const modifiedDate = new Date(item.modified * 1000).toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).replace(',', '');
    const nameEscaped = escapeHtml(item.name);

    let nameCellContent;
    if (item.type === 'folder' && (G.currentPage === 'browse' || G.currentPage === 'search')) {
        const path = G.currentPage === 'browse' ? item.relative_path : (item.full_path ? item.full_path + '/' + item
            .name : item.name);
        const linkUrl = `?view=browse&path=${encodeURIComponent(path)}`;
        nameCellContent =
            `<a href="${linkUrl}"><i class="fas ${fileInfo.icon}" style="color: ${fileInfo.color};"></i><span class="file-text">${nameEscaped}</span></a>`;
        if (G.currentPage === 'search') {
            const pathInfo = item.full_path ? `in /${escapeHtml(item.full_path)}` : 'in Drive';
            nameCellContent = `<a href="${linkUrl}" title="Go to folder">
                    <i class="fas ${fileInfo.icon}" style="color: ${fileInfo.color};"></i>
                    <span class="file-text">${nameEscaped}<small style="display: block; color: var(--text-secondary); font-weight: 400; margin-top: 3px;">${pathInfo}</small></span>
                </a>`;
        }
    } else {
        nameCellContent =
            `<span><i class="fas ${fileInfo.icon}" style="color: ${fileInfo.color};"></i><span class="file-text">${nameEscaped}</span></span>`;
    }

    return `<tr class="selectable" draggable="${G.currentPage === 'browse'}" data-id="${item.id}" data-type="${item.type}" data-name="${nameEscaped}" data-share-id="${item.share_id || ''}">
            <td><input style="display: none;" type="checkbox" id="cb-${item.id}"><label for="cb-${item.id}" class="custom-checkbox-label"></label></td>
            <td class="file-name-cell">${nameCellContent}</td><td>${kind}</td><td>${size}</td><td>${modifiedDate}</td>
            <td><div class="file-actions"><button type="button" class="action-btn" title="More" onclick="showActionPopover(this, event)"><i class="fas fa-ellipsis-v"></i></button></div></td>
        </tr>`;
}

function renderGridItemHTML(item) {
    const fileInfo = getFileIconJS(item.name, item.type === 'folder');
    const nameEscaped = escapeHtml(item.name);
    let clickHandler = '';
    if (item.type === 'folder' && G.currentPage === 'browse') {
        const path = item.relative_path;
        const linkUrl = `?view=browse&path=${encodeURIComponent(path)}`;
        clickHandler = `onclick="navigateToPath('${linkUrl}')"`;
    } else if (item.type === 'file') {
        clickHandler = `ondblclick="openPreviewModal(${item.id},'${escapeJS(nameEscaped)}')"`;
    }

    return `<div class="grid-item selectable" draggable="${G.currentPage === 'browse'}" data-id="${item.id}" data-type="${item.type}" data-name="${nameEscaped}" data-share-id="${item.share_id || ''}" ${clickHandler}>
            <div class="grid-checkbox-overlay">
                <input style="display: none;" type="checkbox" id="cb-grid-${item.id}"><label for="cb-grid-${item.id}" class="custom-checkbox-label"></label>
            </div>
            <i class="fas ${fileInfo.icon} grid-icon" style="color: ${fileInfo.color};"></i>
            <span class="grid-name">${nameEscaped}</span>
        </div>`;
}

async function openMoveModal() {
    G.itemsToMove = $$('.selectable.selected').map(el => parseInt(el.dataset.id));
    if (G.itemsToMove.length === 0) return;

    openModal('moveModal');
    const container = $('#folder-tree-container');
    container.innerHTML = '<div class="live-search-spinner"><i class="fas fa-spinner fa-spin"></i></div>';

    const result = await apiCall('get_folder_tree', {
        exclude_ids: G.itemsToMove
    });

    if (result.success) {
        container.innerHTML = '<ul class="folder-tree">' + renderFolderTree(result.tree) + '</ul>';
    } else {
        container.innerHTML = '<p style="color: var(--danger-color)">Could not load folder tree.</p>';
    }

    $('#confirmMoveBtn').disabled = true;
    G.destinationFolderId = null;
}

function renderFolderTree(nodes) {
    let html = '';
    nodes.forEach(node => {
        const hasChildren = node.children && node.children.length > 0;
        html += `
                <li>
                    <div class="folder-item" data-id="${node.id}">
                        ${hasChildren ? '<i class="fas fa-chevron-down toggle-icon"></i>' : '<i class="fas fa-empty toggle-icon" style="width:16px"></i>'}
                        <i class="fas fa-folder"></i>
                        <span>${escapeHtml(node.name)}</span>
                    </div>
                    ${hasChildren ? `<ul style="display: none;">${renderFolderTree(node.children)}</ul>` : ''}
                </li>
            `;
    });
    return html;
}

function openMoveModalWithSingleItem(itemId) {
    $$('.selectable.selected').forEach(el => el.classList.remove('selected'));
    const itemElement = $(`.selectable[data-id="${itemId}"]`);
    if (itemElement) itemElement.classList.add('selected');
    updateToolbarState();
    openMoveModal();
}

function updateToolbarState() {
    const selectedCount = $$('.selectable.selected').length;
    $('#batch-delete-btn').classList.toggle('disabled', selectedCount === 0);
    $('#batch-download-btn').classList.toggle('disabled', selectedCount === 0);
    $('#batch-move-btn').classList.toggle('disabled', selectedCount === 0 || G.currentPage === 'trash');
    // --- THÊM DÒNG NÀY ---
    if ($('#batch-unshare-btn')) $('#batch-unshare-btn').classList.toggle('disabled', selectedCount === 0);
    // ---
    if ($('#batch-restore-btn')) $('#batch-restore-btn').classList.toggle('disabled', selectedCount === 0);
    const totalRows = $$('.selectable').length;
    const selectAllCheckbox = $('#select-all-checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = (totalRows > 0 && selectedCount === totalRows);
    }
}

function showActionPopover(targetElement, e) {
    e.preventDefault();
    e.stopPropagation();

    const itemElement = targetElement.closest('.selectable');
    if (!itemElement) return;

    const id = itemElement.dataset.id,
        name = itemElement.dataset.name,
        type = itemElement.dataset.type,
        shareId = itemElement.dataset.shareId || '';
    let content = '';
    if (G.currentPage === 'trash') {
        content =
            `<button type="button" class="popover-item" onclick="batchRestoreSelectedWrapper(${id})"><i class="fas fa-redo-alt"></i> Restore</button>
                       <button type="button" class="popover-item" onclick="batchDeleteSelectedWrapper(true, ${id})"><i class="fas fa-times"></i> Delete Forever</button>`;
    } else {
        if (type !== 'folder') {
            content +=
                `<a class="popover-item" href="api.php?action=download_file&id=${id}"><i class="fas fa-download"></i> Download</a>
                            <button type="button" class="popover-item" onclick="openShareModal(${id})"><i class="fas fa-share-alt"></i> Share</button>
                            <button type="button" class="popover-item" onclick="openPreviewModal(${id},'${escapeJS(name)}')"><i class="fas fa-eye"></i> View</button>`;
        }
        content +=
            `<button type="button" class="popover-item" onclick="openMoveModalWithSingleItem(${id})"><i class="fas fa-folder-open"></i> Move</button>
                        <button type="button" class="popover-item" onclick="openRenameModal(${id},'${escapeJS(name)}', '${type}')"><i class="fas fa-edit"></i> Rename</button>
                        <button type="button" class="popover-item" onclick="batchDeleteSelectedWrapper(false, ${id})"><i class="fas fa-trash-alt"></i> Trash</button>`;
    }
    const popover = $('#actionPopover');
    popover.innerHTML = content;
    popover.classList.add('show');
    const popoverRect = popover.getBoundingClientRect();
    let top, left;
    if (e.type === 'contextmenu') {
        top = e.clientY;
        left = e.clientX;
    } else {
        const rect = targetElement.getBoundingClientRect();
        top = rect.bottom;
        left = rect.right - popoverRect.width;
    }
    if (top + popoverRect.height > window.innerHeight) top = window.innerHeight - popoverRect.height - 10;
    if (left + popoverRect.width > window.innerWidth) left = window.innerWidth - popoverRect.width - 10;
    if (top < 10) top = 10;
    if (left < 10) left = 10;
    popover.style.top = `${top}px`;
    popover.style.left = `${left}px`;
}

function updateUIOnItemChange(idsToRemove = [], itemsToAdd = []) {
    idsToRemove.forEach(id => {
        $$(`.selectable[data-id="${id}"]`).forEach(el => el.remove());
    });

    if (itemsToAdd.length > 0) {
        navigateToPath(window.location.search, true);
        return;
    }

    if ($$('.selectable').length === 0 && !$('[data-type="parent-folder"]')) {
        const container = $('#file-list-container');
        if (container) container.innerHTML =
            '<div class="no-files"><i class="fas fa-box-open"></i><p>This folder is empty.</p></div>';
    }

    const currentItemCount = $$('.selectable').length;
    $('#page-stats').textContent = `${currentItemCount} items`;
    updateToolbarState();
    closeDetailsPanel();
}

// Biến toàn cục để lưu instance của flatpickr
let flatpickrInstance = null;

async function openShareModal(fileId) {
    openModal('shareModal');
    $('#shareFileId').value = fileId;

    const linkSection = $('#share-link-section');
    const createSection = $('#create-share-link-section');
    const optionsSection = $('#share-options-section');
    const removeBtn = $('#removeShareLinkBtn');
    const saveBtn = $('#saveShareSettingsBtn');
    const linkInput = $('#shareLinkInput');

    // === BẮT ĐẦU NÂNG CẤP LOGIC NGÀY HẾT HẠN ===
    const customDateInput = $('#shareExpiryCustom');
    const clearDateBtn = $('#clearExpiryBtn');

    // Hủy instance cũ nếu có để tránh lỗi
    if (flatpickrInstance) {
        flatpickrInstance.destroy();
    }

    // Khởi tạo Flatpickr
    flatpickrInstance = flatpickr(customDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        // === LOGIC MỚI: Tự động chọn theme dựa trên chế độ hiện tại ===
        "theme": document.body.classList.contains('dark-mode') ? "dark" : "light",
        onChange: function(selectedDates, dateStr, instance) {
            // Hiển thị nút xóa khi có ngày được chọn
            clearDateBtn.style.display = dateStr ? 'block' : 'none';
            // Xóa active khỏi các nút chọn nhanh
            $$('.btn-quick-expiry').forEach(b => b.classList.remove('active'));
        }
    });

    const resetExpiryUI = () => {
        flatpickrInstance.clear();
        clearDateBtn.style.display = 'none';
        $$('.btn-quick-expiry').forEach(b => b.classList.remove('active'));
    };

    clearDateBtn.onclick = resetExpiryUI;

    $$('.btn-quick-expiry').forEach(button => {
        button.onclick = () => {
            $$('.btn-quick-expiry').forEach(b => b.classList.remove('active'));
            button.classList.add('active');
            const days = parseInt(button.dataset.days, 10);
            const date = new Date();
            date.setDate(date.getDate() + days);
            flatpickrInstance.setDate(date, true);
        };
    });
    // === KẾT THÚC NÂNG CẤP LOGIC NGÀY HẾT HẠN ===

    linkSection.style.display = 'none';
    createSection.innerHTML = '<div class="live-search-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
    optionsSection.style.display = 'none';
    removeBtn.style.display = 'none';
    saveBtn.textContent = 'Create Link';
    $('#share-modal-body').querySelectorAll('input[type="password"]').forEach(i => i.value = '');
    $('#shareAllowDownload').checked = true;
    resetExpiryUI(); // Reset giao diện chọn ngày

    const result = await apiCall('get_share_details', {
        file_id: fileId
    });

    if (result.success && result.details) {
        linkSection.style.display = 'block';
        createSection.style.display = 'none';
        optionsSection.style.display = 'block';
        removeBtn.style.display = 'block';
        saveBtn.textContent = 'Update Settings';

        linkInput.value = `${G.BASE_URL}share.php?id=${result.details.id}`;
        $('#sharePassword').placeholder = result.details.has_password ? 'Password is set. Enter new to change.' :
            'Protect with a password';
        $('#shareAllowDownload').checked = result.details.allow_download == 1;

        // Đặt ngày hết hạn hiện tại (nếu có)
        if (result.details.expires_at) {
            flatpickrInstance.setDate(result.details.expires_at.split(' ')[0], false);
        }
    } else {
        createSection.innerHTML =
            '<p style="text-align: center; color: var(--text-secondary);">This file is not currently shared.</p>';
        createSection.style.display = 'block';
        optionsSection.style.display = 'block';
    }
}

async function batchDeleteSelected(isPermanent = false) {
    const selectedItems = $$('.selectable.selected');
    if (selectedItems.length === 0) return;
    const selectedIds = selectedItems.map(el => el.dataset.id);
    const msgAction = G.currentPage === 'trash' || isPermanent ? 'permanently delete' : 'move to trash';
    const message = `Are you sure you want to ${msgAction} ${selectedIds.length} item(s)?`;
    showConfirmModal('Confirm Deletion', message, () => {
        const originalParents = new Map();
        selectedItems.forEach(item => {
            originalParents.set(item, item.parentNode);
            item.style.transition = 'opacity 0.3s ease';
            item.style.opacity = '0';
        });
        setTimeout(() => {
            selectedItems.forEach(item => item.remove());
            updateUIOnItemChange([], []);
        }, 300);
        showToast(`${selectedIds.length} item(s) moved to trash.`, 'info');
        apiCall('delete', {
                ids: selectedIds,
                force_delete: (G.currentPage === 'trash' || isPermanent)
            })
            .catch(error => {
                showToast(`Failed to delete items: ${error.message}. Restoring view.`, 'danger');
                navigateToPath(window.location.search, true);
            });
    });
}

function batchDeleteSelectedWrapper(isPermanent, id) {
    $$('.selectable.selected').forEach(el => el.classList.remove('selected'));
    const itemElement = $(`.selectable[data-id="${id}"]`);
    if (itemElement) {
        itemElement.classList.add('selected');
        batchDeleteSelected(isPermanent);
    }
}

async function batchRestoreSelected() {
    const selectedIds = $$('.selectable.selected').map(el => el.dataset.id);
    if (selectedIds.length === 0) return;
    showConfirmModal('Confirm Restore', `Are you sure you want to restore ${selectedIds.length} item(s)?`,
        async () => {
            const result = await apiCall('restore', {
                ids: selectedIds
            });
            if (result.success) {
                showToast(result.message);
                navigateToPath('?view=trash', true);
            }
        });
}
async function confirmEmptyTrash() {
    showConfirmModal('Confirm Empty Trash',
        'This will permanently delete all items in the trash. This cannot be undone.', async () => {
            const result = await apiCall('empty_trash');
            if (result.success) {
                showToast(result.message);
                navigateToPath('?view=trash', true);
            }
        });
}
let draggedItemId = null;
document.addEventListener('dragstart', e => {
    const target = e.target.closest('.selectable[draggable="true"]');
    if (target) {
        draggedItemId = target.dataset.id;
        target.classList.add('dragged');
        e.dataTransfer.setData('text/plain', draggedItemId);
    }
});
document.addEventListener('dragend', e => {
    const target = e.target.closest('.dragged');
    if (target) target.classList.remove('dragged');
});
document.addEventListener('dragover', e => {
    e.preventDefault();
});
document.addEventListener('dragenter', e => {
    const dropTarget = e.target.closest('.selectable[data-type="folder"]');
    if (dropTarget && draggedItemId && draggedItemId !== dropTarget.dataset.id) dropTarget.classList.add(
        'drag-over');
});
document.addEventListener('dragleave', e => {
    const dropTarget = e.target.closest('.selectable[data-type="folder"]');
    if (dropTarget) dropTarget.classList.remove('drag-over');
});
document.addEventListener('drop', async e => {
    e.preventDefault();
    const dropTarget = e.target.closest('.selectable[data-type="folder"]');
    $$('.drag-over').forEach(el => el.classList.remove('drag-over'));
    if (dropTarget && draggedItemId && draggedItemId !== dropTarget.dataset.id) {
        const result = await apiCall('move', {
            item_ids: [draggedItemId],
            destination_id: dropTarget.dataset.id
        });
        if (result.success) {
            showToast(result.message);
            updateUIOnItemChange([draggedItemId]);
        }
    }
});

function setViewMode(mode) {
    G.viewMode = mode;
    localStorage.setItem('viewMode', mode);
    const container = $('#file-list-container');
    if (container) {
        if (mode === 'grid') {
            container.classList.add('grid-view');
            $('#grid-view-btn').style.display = 'none';
            $('#list-view-btn').style.display = 'flex';
        } else {
            container.classList.remove('grid-view');
            $('#list-view-btn').style.display = 'none';
            $('#grid-view-btn').style.display = 'flex';
        }
    }
}

function batchDownloadSelected() {
    const selectedIds = $$('.selectable.selected').map(el => el.dataset.id);
    if (selectedIds.length === 0) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api.php';
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'download_archive';
    form.appendChild(actionInput);
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

function batchRestoreSelectedWrapper(id) {
    showConfirmModal('Confirm Restore', 'Are you sure you want to restore this item?', async () => {
        const result = await apiCall('restore', {
            ids: [id]
        });
        if (result.success) {
            showToast(result.message);
            navigateToPath('?view=trash', true);
        }
    });
}
async function openDetailsPanel(itemId) {
    const panel = $('#details-panel');
    const body = $('#details-panel-body');
    panel.classList.add('active');
    $('.main-content').classList.add('details-panel-active');
    body.innerHTML = '<div class="live-search-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
    const result = await apiCall('get_details', {
        id: itemId
    });
    if (result.success) {
        const item = result.item;
        const fileInfo = getFileIconJS(item.name, item.type === 'folder');
        const previewHTML = item.preview_url ? `<img src="${item.preview_url}" alt="Preview">` :
            `<i class="fas ${fileInfo.icon}" style="color: ${fileInfo.color};"></i>`;
        body.innerHTML =
            ` <div class="details-preview">${previewHTML}</div> <dl class="details-info-list"> <dt>Name</dt> <dd>${escapeHtml(item.name)}</dd> <dt>Kind</dt> <dd>${escapeHtml(item.kind)}</dd> <dt>Size</dt> <dd>${item.size_formatted}</dd> <dt>Date Modified</dt> <dd>${item.modified_at_formatted}</dd> <dt>Date Created</dt> <dd>${item.created_at_formatted}</dd> </dl> `;
    } else {
        body.innerHTML = `<p style="padding:20px; color:var(--danger-color);">${result.message}</p>`;
    }
}

function closeDetailsPanel() {
    $('#details-panel').classList.remove('active');
    $('.main-content').classList.remove('details-panel-active');
}

function debounce(func, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}
async function performLiveSearch(query) {
    const resultsContainer = $('#live-search-results');
    if (query.length < 2) {
        resultsContainer.style.display = 'none';
        return;
    }
    resultsContainer.style.display = 'block';
    resultsContainer.innerHTML = '<div class="live-search-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
    const result = await apiCall('live_search', {
        q: query
    }, 'GET');
    if (result.success && result.items.length > 0) {
        resultsContainer.innerHTML = result.items.map(item => {
            const fileInfo = getFileIconJS(item.name, item.type === 'folder');
            const link = item.type === 'folder' ?
                `?view=browse&path=${encodeURIComponent(item.full_path + '/' + item.name)}` :
                `?view=browse&path=${encodeURIComponent(item.full_path)}`;
            return ` <a href="${link}" class="live-search-item"> <i class="fas ${fileInfo.icon}" style="color: ${fileInfo.color};"></i> <div class="live-search-item-info"> <div class="name">${escapeHtml(item.name)}</div> <div class="path">/${escapeHtml(item.full_path)}</div> </div> </a> `;
        }).join('');
    } else {
        resultsContainer.innerHTML =
            '<div style="padding:15px; color:var(--text-secondary);">No results found.</div>';
    }
}

function hideLiveSearch() {
    setTimeout(() => {
        $('#live-search-results').style.display = 'none';
    }, 200);
}

function openModal(id) {
    // THÊM LOGIC MỚI: Tự động đóng popover khi mở modal
    const popover = $('#actionPopover');
    if (popover && popover.classList.contains('show')) {
        popover.classList.remove('show');
    }
    // Giữ nguyên logic cũ
    $(`#${id}`).classList.add('show');
}

function closeModal(id) {
    const modal = $(`#${id}`);
    if (!modal) return;
    modal.classList.remove('show');

    if (id === 'previewModal') {
        // Hủy các instance media
        if (G.plyrInstance) {
            G.plyrInstance.destroy();
            G.plyrInstance = null;
        }
        if (G.aceEditorInstance) {
            G.aceEditorInstance.destroy();
            G.aceEditorInstance = null;
        }
        $('#previewContent').innerHTML = '';

        // --- LOGIC MỚI: Reset trạng thái fullscreen khi đóng ---
        if (modal.classList.contains('modal-maximized')) {
            const body = document.body;
            const btnIcon = $('#previewMaximizeBtn i');

            modal.classList.remove('modal-maximized');
            body.classList.remove('preview-maximized');

            btnIcon.classList.remove('fa-compress');
            btnIcon.classList.add('fa-expand');
            $('#previewMaximizeBtn').title = 'Maximize';
        }
    }
}

function toggleTheme() {
    const isLight = document.body.classList.toggle('light-mode');
    document.cookie = `theme=${isLight ? 'light' : 'dark'}; path=/; max-age=31536000`;

    // Cập nhật biểu đồ
    if (G.storageChartInstance) {
        G.storageChartInstance.destroy();
        G.storageChartInstance = null;
        renderStorageChart();
    }

    // === LOGIC MỚI: Cập nhật theme của Flatpickr nếu nó đang tồn tại ===
    if (flatpickrInstance) {
        // Tìm element cha của lịch
        const calendar = flatpickrInstance.calendarContainer;
        if (calendar) {
            if (isLight) {
                calendar.classList.remove('dark');
                calendar.classList.add('light');
            } else {
                calendar.classList.remove('light');
                calendar.classList.add('dark');
            }
        }
    }
}

const sidebar = $('.sidebar'),
    overlay = $('#overlay');

function toggleSidebar() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

function openNewFolderModal() {
    openModal('newFolderModal');
    $('#folderName').focus();
}

function showConfirmModal(title, message, onConfirmCallback) {
    $('#confirmModalTitle').textContent = title;
    $('#confirmModalMessage').textContent = message;
    const confirmBtn = $('#confirmModalButton');
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

    // Đặt lại trạng thái các tab về mặc định khi mở modal
    const modal = $('#userInfoModal');
    modal.querySelectorAll('.tab-nav-item, .tab-pane').forEach(el => el.classList.remove('active'));

    const overviewTab = modal.querySelector('.tab-nav-item[data-tab="overview"]');
    const overviewPane = modal.querySelector('#tab-overview');

    if (overviewTab && overviewPane) {
        overviewTab.classList.add('active');
        overviewPane.classList.add('active');
    }

    // Chỉ render biểu đồ KHI tab overview được active VÀ biểu đồ chưa được vẽ
    if (!G.storageChartInstance) {
        setTimeout(() => renderStorageChart(), 50); // Thêm một độ trễ nhỏ để đảm bảo modal hiển thị
    }
}

function renderStorageChart() {
    const storageData = <?php echo json_encode($storageBreakdownForJs); ?>;
    const ctx = document.getElementById('storageChart').getContext('2d');
    const isDarkMode = !document.body.classList.contains('light-mode');
    const textColor = isDarkMode ? 'rgba(240, 240, 240, 0.8)' : 'rgba(28, 28, 30, 0.8)';
    if (G.storageChartInstance) G.storageChartInstance.destroy();
    if (storageData.labels.length === 0) {
        $('#storageChartContainer').innerHTML =
            '<p style="text-align:center; color: var(--text-secondary); padding-top: 50px;">No file data to display.</p>';
        return;
    }
    G.storageChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: storageData.labels,
            datasets: [{
                data: storageData.data,
                backgroundColor: ['#0a84ff', '#5ac8fa', '#ff9500', '#ff3b30', '#34c759', '#ffcc00',
                    '#af52de', '#5856d6'
                ],
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
    $('#renameItemId').value = id;
    const input = $('#newName');
    if (type === 'file') {
        const lastDot = oldName.lastIndexOf('.');
        input.value = lastDot > 0 ? oldName.substring(0, lastDot) : oldName;
    } else {
        input.value = oldName;
    }
    input.focus();
    input.select();
}

async function saveCodeFromEditor(fileId) {
    if (!G.aceEditorInstance) return;
    const content = G.aceEditorInstance.getValue();

    const result = await apiCall('save_file_content', {
        file_id: fileId,
        content: content
    });

    if (result.success) {
        showToast(result.message);
        // Optional: Update size in the details panel if it's open
        const detailsSize = document.querySelector('#details-panel-body dd:nth-of-type(3)');
        if (detailsSize && $('#details-panel').classList.contains('active')) {
            detailsSize.textContent = result.new_size_formatted;
        }
    }
}

async function openPreviewModal(id, name) {
    const modal = $('#previewModal');
    const modalContent = modal.querySelector('.modal-content');
    openModal('previewModal');

    const title = $('#previewModalTitle');
    const content = $('#previewContent');
    const headerActions = $('#previewHeaderActions');

    title.textContent = name;
    content.innerHTML =
        '<div style="display:flex;justify-content:center;align-items:center;height:200px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    headerActions.innerHTML = '';
    headerActions.style.display = 'none';
    modalContent.classList.remove('modal-content-fullscreen');

    const d = await apiCall('get_preview_data', {
        id: id
    });

    if (!d.success) {
        content.innerHTML = `<p style="padding:20px;">${d.message}</p>`;
        return;
    }

    let html = '';
    switch (d.type) {
        case 'pdf_viewer':
            modalContent.classList.add('modal-content-fullscreen');
            html = `<iframe src="${d.data}" style="width: 100%; height: 100%; border: none;"></iframe>`;
            content.innerHTML = html;
            break;
        case 'client_office_viewer':
            modalContent.classList.add('modal-content-fullscreen');
            content.innerHTML =
                `<div style="padding:20px; text-align:center;">
                    <i class="fas fa-circle-notch fa-spin"></i> Loading document preview...
                    <p style="font-size:0.8em; color:var(--text-secondary); margin-top:10px;">Please wait, larger files may take a moment.</p>
                 </div>`;
            try {
                const response = await fetch(d.data.fileUrl);
                if (!response.ok) throw new Error('Failed to download file for preview.');
                const blob = await response.blob();

                content.innerHTML = ''; // Clear loading indicator

                if (d.data.mimeType.includes('wordprocessingml')) {
                    docx.renderAsync(blob, content);
                } else if (d.data.mimeType.includes('spreadsheetml') || d.data.mimeType.includes('ms-excel')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, {
                            type: 'array'
                        });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const html_string = XLSX.utils.sheet_to_html(worksheet, {
                            className: 'sheet-table'
                        });
                        content.innerHTML =
                            `<div style="padding: 15px; overflow: auto; height: 100%;">${html_string}</div>`;
                    };
                    reader.readAsArrayBuffer(blob);
                }
            } catch (error) {
                console.error("Client viewer error:", error);
                content.innerHTML =
                    `<p style="padding:20px; color:var(--danger-color)">Could not load preview: ${error.message}</p>`;
            }
            break;
        case 'image':
            html = `<img src="${d.data}" alt="${name}">`;
            content.innerHTML = html;
            break;
        case 'video':
            html =
                `<video id="media-player" playsinline controls><source src="${d.data}" type="${d.mime_type}"></video>`;
            content.innerHTML = html;
            G.plyrInstance = new Plyr('#media-player', {
                autoplay: true
            });
            break;
        case 'audio':
            html = `<audio id="media-player" controls><source src="${d.data}" type="${d.mime_type}"></audio>`;
            content.innerHTML = html;
            G.plyrInstance = new Plyr('#media-player', {
                autoplay: true
            });
            break;
        case 'code_editor':
            modalContent.classList.add('modal-content-fullscreen');
            html = `<div id="code-editor-container"></div>`;
            content.innerHTML = html;
            headerActions.style.display = 'flex';

            const themeSelector = document.createElement('select');
            themeSelector.id = 'aceThemeSelector';
            const themelist = ace.require("ace/ext/themelist");
            themelist.themes.forEach(theme => {
                let option = new Option(theme.caption, theme.theme);
                themeSelector.add(option);
            });
            themeSelector.value = 'ace/theme/tomorrow_night';
            themeSelector.onchange = () => G.aceEditorInstance.setTheme(themeSelector.value);

            const saveButton = document.createElement('button');
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
            saveButton.className = 'btn btn-save';
            saveButton.onclick = () => saveCodeFromEditor(id);

            headerActions.appendChild(themeSelector);
            headerActions.appendChild(saveButton);

            G.aceEditorInstance = ace.edit("code-editor-container");
            G.aceEditorInstance.setTheme("ace/theme/tomorrow_night");
            G.aceEditorInstance.session.setMode("ace/mode/" + d.language);
            G.aceEditorInstance.setValue(d.data, -1);
            G.aceEditorInstance.setOptions({
                fontSize: "14px",
                showPrintMargin: false
            });
            break;
        default:
            html =
                `<div style="text-align:center; padding: 40px; color: var(--text-secondary);"><i class="fas fa-file fa-3x" style="margin-bottom: 15px;"></i><p>No preview available for <strong>${escapeHtml(d.data.name)}</strong>.</p><p>Size: ${d.data.size}</p></div>`;
            content.innerHTML = html;
            break;
    }
}

async function saveShareSettings() {
    const fileId = $('#shareFileId').value;
    const password = $('#sharePassword').value;
    // Lấy ngày từ flatpickr instance thay vì input cũ
    const expires_at = flatpickrInstance.selectedDates.length > 0 ? flatpickrInstance.formatDate(flatpickrInstance
        .selectedDates[0], "Y-m-d") : null;
    const allow_download = $('#shareAllowDownload').checked ? 1 : 0;

    const data = {
        file_id: fileId,
        allow_download: allow_download
    };
    if (password) data.password = password;
    if (expires_at) data.expires_at = expires_at;

    const result = await apiCall('update_share_link', data);

    if (result.success) {
        showToast('Share settings saved!');
        $('#shareLinkInput').value = `${G.BASE_URL}share.php?id=${result.share_id}`;
        $('#share-link-section').style.display = 'block';
        $('#create-share-link-section').style.display = 'none';
        $('#removeShareLinkBtn').style.display = 'block';
        $('#saveShareSettingsBtn').textContent = 'Update Settings';
        $('#sharePassword').value = '';
        $('#sharePassword').placeholder = password ? 'Password is set. Enter new to change.' :
            'Protect with a password';
    }
}

async function removeShareLink() {
    const fileId = $('#shareFileId').value;
    showConfirmModal('Remove Share Link',
        'Are you sure you want to remove this share link? This will make the link invalid.', async () => {
            const result = await apiCall('remove_share_link', {
                file_id: fileId
            });
            if (result.success) {
                showToast(result.message);
                closeModal('shareModal');
            }
        });
}

function copyShareLink() {
    const input = $('#shareLinkInput');
    if (!input.value) return;
    input.select();
    document.execCommand('copy');
    showToast('Link copied to clipboard!', 'info');
}

function toggleSelectAll(isChecked) {
    $$('.selectable').forEach(el => {
        el.classList.toggle('selected', isChecked);
        const checkbox = el.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = isChecked;
    });
    updateToolbarState();
    if (isChecked && $$('.selectable').length > 0) {
        openDetailsPanel($$('.selectable')[0].dataset.id);
    } else {
        closeDetailsPanel();
    }
}

function togglePreviewFullscreen() {
    const modal = $('#previewModal');
    const body = document.body;
    const btnIcon = $('#previewMaximizeBtn i');

    modal.classList.toggle('modal-maximized');
    body.classList.toggle('preview-maximized');

    if (modal.classList.contains('modal-maximized')) {
        btnIcon.classList.remove('fa-expand');
        btnIcon.classList.add('fa-compress');
        $('#previewMaximizeBtn').title = 'Restore';
    } else {
        btnIcon.classList.remove('fa-compress');
        btnIcon.classList.add('fa-expand');
        $('#previewMaximizeBtn').title = 'Maximize';
    }
}

function escapeJS(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
}
const CHUNK_SIZE = 5 * 1024 * 1024; // 5 MB
const MAX_PARALLEL_UPLOADS = 3;
const MAX_RETRIES = 3;

let parentIdForUpload = G.currentFolderId;

function openUploadModal() {
    parentIdForUpload = G.currentFolderId;
    openModal('uploadModal');
}

const dropZone = $('#drop-zone'),
    fileInput = $('#file-input-chunk'),
    progressList = $('#upload-progress-list');

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
    item.innerHTML =
        `<i class="fas ${fileInfo.icon} file-icon" style="color: ${fileInfo.color};"></i><div class="progress-info"><div class="file-name">${escapeHtml(file.name)}</div><div class="progress-bar-container"><div class="progress-bar"></div></div><div class="progress-status">Initializing...</div></div><div class="status-icon"></div>`;
    progressList.appendChild(item);
    return item;
}
async function uploadFile(file) {
    const item = createProgressItem(file);
    const bar = item.querySelector('.progress-bar');
    const status = item.querySelector('.progress-status');
    const icon = item.querySelector('.status-icon');
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

    status.textContent = 'Preparing...';

    const startData = new FormData();
    startData.append('action', 'start_upload');
    startData.append('fileName', file.name);
    startData.append('fileSize', file.size);
    startData.append('mimeType', file.type);
    startData.append('parentId', parentIdForUpload);

    const startResult = await apiCall('start_upload', startData, 'POST');

    if (!startResult.success) {
        status.textContent = 'Error: ' + startResult.message;
        icon.innerHTML = `<i class="fas fa-times-circle" style="color:var(--danger-color)"></i>`;
        return;
    }

    const fileId = startResult.fileId;
    const chunkQueue = Array.from({
        length: totalChunks
    }, (_, i) => i);
    let completedChunks = 0;

    const uploadWorker = async () => {
        while (chunkQueue.length > 0) {
            const chunkIndex = chunkQueue.shift();
            if (chunkIndex === undefined) continue;

            let retries = 0;
            while (retries < MAX_RETRIES) {
                try {
                    const start = chunkIndex * CHUNK_SIZE;
                    const chunk = file.slice(start, start + CHUNK_SIZE);

                    const chunkData = new FormData();
                    chunkData.append('action', 'upload_chunk');
                    chunkData.append('fileId', fileId);
                    chunkData.append('chunkIndex', chunkIndex);
                    chunkData.append('chunk', chunk);

                    const chunkResult = await apiCall('upload_chunk', chunkData, 'POST');
                    if (!chunkResult.success) throw new Error(chunkResult.message);

                    completedChunks++;
                    updateProgress(completedChunks, totalChunks, bar, status);
                    break;
                } catch (e) {
                    retries++;
                    if (retries >= MAX_RETRIES) {
                        // Push failed chunk back to the queue for another worker to try
                        chunkQueue.push(chunkIndex);
                        throw new Error(`Chunk ${chunkIndex + 1} failed after ${MAX_RETRIES} retries.`);
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
        icon.innerHTML = `<i class="fas fa-times-circle" style="color:var(--danger-color)"></i>`;
        return;
    }

    if (completedChunks < totalChunks) {
        status.textContent = 'Upload failed: Not all chunks were uploaded.';
        icon.innerHTML = `<i class="fas fa-times-circle" style="color:var(--danger-color)"></i>`;
        return;
    }

    status.textContent = 'Assembling file...';

    const completeData = new FormData();
    completeData.append('action', 'complete_upload');
    completeData.append('fileId', fileId);
    completeData.append('totalChunks', totalChunks);

    const completeResult = await apiCall('complete_upload', completeData, 'POST');

    if (completeResult.success) {
        status.textContent = 'Complete!';
        icon.innerHTML = `<i class="fas fa-check-circle" style="color:var(--success-color)"></i>`;
        setTimeout(() => {
            if ($('#uploadModal').classList.contains('show')) {
                navigateToPath(window.location.search, true);
            }
        }, 1200);
    } else {
        status.textContent = 'Finalization failed: ' + completeResult.message;
        icon.innerHTML = `<i class="fas fa-exclamation-circle" style="color:var(--danger-color)"></i>`;
    }
}


function updateProgress(chunkNum, totalChunks, bar, status) {
    const percent = totalChunks > 0 ? Math.round((chunkNum / totalChunks) * 100) : 100;
    bar.style.width = `${percent}%`;
    status.textContent = `Uploading... ${percent}%`;
}

function getFileIconJS(name, isFolder = false) {
    if (isFolder) {
        return {
            icon: 'fa-folder',
            color: '#5ac8fa'
        };
    }

    const nameLower = name.toLowerCase();
    const extension = nameLower.split('.').pop();

    // Mặc định
    let icon = 'fa-file';
    let color = '#8a8a8e';

    const iconMap = {
        // --- Office & Documents ---
        'pdf': {
            i: 'fa-file-pdf',
            c: '#e62e2e'
        },
        'doc': {
            i: 'fa-file-word',
            c: '#2a5699'
        },
        'docx': {
            i: 'fa-file-word',
            c: '#2a5699'
        },
        'xls': {
            i: 'fa-file-excel',
            c: '#217346'
        },
        'xlsx': {
            i: 'fa-file-excel',
            c: '#217346'
        },
        'csv': {
            i: 'fa-file-excel',
            c: '#217346'
        },
        'ppt': {
            i: 'fa-file-powerpoint',
            c: '#d24726'
        },
        'pptx': {
            i: 'fa-file-powerpoint',
            c: '#d24726'
        },
        'txt': {
            i: 'fa-file-alt',
            c: '#a0a0a5'
        },
        'rtf': {
            i: 'fa-file-alt',
            c: '#a0a0a5'
        },

        // --- Archives ---
        'zip': {
            i: 'fa-file-archive',
            c: '#f0ad4e'
        },
        'rar': {
            i: 'fa-file-archive',
            c: '#f0ad4e'
        },
        '7z': {
            i: 'fa-file-archive',
            c: '#f0ad4e'
        },
        'tar': {
            i: 'fa-file-archive',
            c: '#f0ad4e'
        },
        'gz': {
            i: 'fa-file-archive',
            c: '#f0ad4e'
        },

        // --- Images ---
        'jpg': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'jpeg': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'png': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'gif': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'bmp': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'webp': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'heic': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'tiff': {
            i: 'fa-file-image',
            c: '#5cb85c'
        },
        'svg': {
            i: 'fa-file-image',
            c: '#ffb13b'
        },

        // --- Design Files ---
        'psd': {
            i: 'fa-file-image',
            c: '#3498db'
        },
        'ai': {
            i: 'fa-file-image',
            c: '#f39c12'
        },
        'fig': {
            i: 'fa-file-image',
            c: '#a259ff'
        },

        // --- Audio ---
        'mp3': {
            i: 'fa-file-audio',
            c: '#9b59b6'
        },
        'wav': {
            i: 'fa-file-audio',
            c: '#9b59b6'
        },
        'aac': {
            i: 'fa-file-audio',
            c: '#9b59b6'
        },
        'flac': {
            i: 'fa-file-audio',
            c: '#9b59b6'
        },
        'm4a': {
            i: 'fa-file-audio',
            c: '#9b59b6'
        },

        // --- Video ---
        'mp4': {
            i: 'fa-file-video',
            c: '#e74c3c'
        },
        'mov': {
            i: 'fa-file-video',
            c: '#e74c3c'
        },
        'avi': {
            i: 'fa-file-video',
            c: '#e74c3c'
        },
        'mkv': {
            i: 'fa-file-video',
            c: '#e74c3c'
        },
        'webm': {
            i: 'fa-file-video',
            c: '#e74c3c'
        },

        // --- Code & Text-based Files ---
        'html': {
            i: 'fa-file-code',
            c: '#e44d26'
        },
        'htm': {
            i: 'fa-file-code',
            c: '#e44d26'
        },
        'css': {
            i: 'fa-file-code',
            c: '#264de4'
        },
        'scss': {
            i: 'fa-file-code',
            c: '#264de4'
        },
        'sass': {
            i: 'fa-file-code',
            c: '#264de4'
        },
        'js': {
            i: 'fa-file-code',
            c: '#f0db4f'
        },
        'ts': {
            i: 'fa-file-code',
            c: '#f0db4f'
        },
        'jsx': {
            i: 'fa-file-code',
            c: '#f0db4f'
        },
        'tsx': {
            i: 'fa-file-code',
            c: '#f0db4f'
        },
        'json': {
            i: 'fa-file-code',
            c: '#8a8a8e'
        },
        'xml': {
            i: 'fa-file-code',
            c: '#ff6600'
        },
        'md': {
            i: 'fa-file-alt',
            c: '#34495e'
        },
        'php': {
            i: 'fa-file-code',
            c: '#8892be'
        },
        'py': {
            i: 'fa-file-code',
            c: '#3572A5'
        },
        'java': {
            i: 'fa-file-code',
            c: '#b07219'
        },
        'jar': {
            i: 'fa-file-code',
            c: '#b07219'
        },
        'c': {
            i: 'fa-file-code',
            c: '#00599c'
        },
        'cpp': {
            i: 'fa-file-code',
            c: '#00599c'
        },
        'h': {
            i: 'fa-file-code',
            c: '#00599c'
        },
        'cs': {
            i: 'fa-file-code',
            c: '#68217a'
        },
        'sql': {
            i: 'fa-database',
            c: '#f29111'
        },
        'sh': {
            i: 'fa-terminal',
            c: '#4EAA25'
        },
        'bash': {
            i: 'fa-terminal',
            c: '#4EAA25'
        },
        'yml': {
            i: 'fa-file-code',
            c: '#cb171e'
        },
        'yaml': {
            i: 'fa-file-code',
            c: '#cb171e'
        },
        'rb': {
            i: 'fa-file-code',
            c: '#CC342D'
        },
        'go': {
            i: 'fa-file-code',
            c: '#00ADD8'
        },
        'swift': {
            i: 'fa-file-code',
            c: '#F05138'
        },
        'kt': {
            i: 'fa-file-code',
            c: '#7F52FF'
        },
        'rs': {
            i: 'fa-file-code',
            c: '#000000'
        },
        'dockerfile': {
            i: 'fa-file-code',
            c: '#384d54'
        },

        // --- Fonts ---
        'ttf': {
            i: 'fa-font',
            c: '#94a2b0'
        },
        'otf': {
            i: 'fa-font',
            c: '#94a2b0'
        },
        'woff': {
            i: 'fa-font',
            c: '#94a2b0'
        },
        'woff2': {
            i: 'fa-font',
            c: '#94a2b0'
        },

        // --- Executables & System ---
        'exe': {
            i: 'fa-cog',
            c: '#34495e'
        },
        'app': {
            i: 'fa-cog',
            c: '#34495e'
        },
        'dmg': {
            i: 'fa-cog',
            c: '#34495e'
        },
        'iso': {
            i: 'fa-compact-disc',
            c: '#7f8c8d'
        },
        'apk': {
            i: 'fa-robot',
            c: '#a4c639'
        },
    };

    if (iconMap[extension]) {
        icon = iconMap[extension].i;
        color = iconMap[extension].c;
    }

    // Xử lý các file không có phần mở rộng
    if (nameLower.indexOf('.') === -1) {
        if (nameLower === 'dockerfile') {
            icon = 'fa-file-code';
            color = '#384d54';
        }
    }

    return {
        icon,
        color
    };
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function formatBytesJS(bytes, decimals = 2) {
    if (bytes === 0 || !bytes) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

document.addEventListener('DOMContentLoaded', () => {
    const sessionMessage = <?php echo !empty($session_message) ? $session_message : 'null'; ?>;
    if (sessionMessage) {
        showToast(sessionMessage.text, sessionMessage.type);
    }

    navigateToPath(
        `?view=<?php echo $initial_view; ?>&path=<?php echo $initial_path; ?>&q=<?php echo $initial_query; ?>`,
        true);

    document.body.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (link && link.href.includes(G.BASE_URL) && !link.href.includes('download_file') && !link
            .target) {
            const url = new URL(link.href);
            if (url.searchParams.has('view')) {
                e.preventDefault();
                navigateToPath(link.search);
            }
        }
    });

    window.addEventListener('popstate', e => {
        const initialUrl =
            `?view=<?php echo $initial_view; ?>&path=<?php echo $initial_path; ?>&q=<?php echo $initial_query; ?>`;
        navigateToPath(e.state && e.state.path ? e.state.path : initialUrl, true);
    });

    $('.search-form-desktop').addEventListener('submit', e => {
        e.preventDefault();
        const query = e.target.q.value;
        hideLiveSearch();
        navigateToPath(`?view=search&q=${encodeURIComponent(query)}`);
    });
    $('.search-form-mobile').addEventListener('submit', e => {
        e.preventDefault();
        const query = e.target.q.value;
        navigateToPath(`?view=search&q=${encodeURIComponent(query)}`);
    });

    $('#newFolderForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const result = await apiCall('new_folder', {
            folder_name: form.folder_name.value,
            parent_id: form.parent_id.value
        });
        if (result.success) {
            closeModal('newFolderModal');
            showToast(`Folder created.`);
            updateUIOnItemChange([], [result.item]);
            form.reset();
        }
    });
    $('#renameForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const result = await apiCall('rename', {
            id: form.id.value,
            new_name: form.new_name.value
        });
        if (result.success) {
            closeModal('renameModal');
            showToast('Item renamed.');
            navigateToPath(window.location.search, true);
            form.reset();
        }
    });

    const mainContentArea = $('.content-area');
    mainContentArea.addEventListener('click', (e) => {
        if (document.querySelector('.modal.show')) {
            return;
        }
        const item = e.target.closest('.selectable');
        if (!item || e.target.closest('a, button, label, input, .grid-checkbox-overlay') || (e.target
                .closest('.grid-item') && e.target.closest('.grid-item').getAttribute('onclick')))
            return;
        if (!e.ctrlKey && !e.shiftKey) {
            $$('.selectable.selected').forEach(el => el.classList.remove('selected'));
        }
        item.classList.toggle('selected');
        const checkbox = item.querySelector(`input[type="checkbox"]`);
        if (checkbox) checkbox.checked = item.classList.contains('selected');
        updateToolbarState();
        if ($$('.selectable.selected').length === 1) {
            openDetailsPanel(item.dataset.id);
        } else {
            closeDetailsPanel();
        }
    });
    mainContentArea.addEventListener('dblclick', (e) => {
        if (document.querySelector('.modal.show')) {
            return;
        }
        const item = e.target.closest('.selectable');
        if (!item) return;
        if (item.dataset.type === 'folder' && G.currentPage === 'browse') {
            const link = item.querySelector('a');
            if (link) {
                navigateToPath(link.search);
            }
        } else if (item.dataset.type === 'file') {
            openPreviewModal(item.dataset.id, item.dataset.name);
        }
    });
    mainContentArea.addEventListener('contextmenu', e => {
        if (document.querySelector('.modal.show')) {
            return;
        }
        const item = e.target.closest('.selectable');
        if (item) {
            e.preventDefault();
            if (!item.classList.contains('selected')) {
                $$('.selectable.selected').forEach(el => el.classList.remove('selected'));
                item.classList.add('selected');
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = true;
                updateToolbarState();
                openDetailsPanel(item.dataset.id);
            }
            showActionPopover(item, e);
        }
    });

    const searchInput = $('.search-form-desktop .search-input');
    const debouncedSearch = debounce(performLiveSearch, 300);
    searchInput.addEventListener('input', () => {
        debouncedSearch(searchInput.value);
    });
    searchInput.addEventListener('blur', hideLiveSearch);

    window.addEventListener('click', e => {
        if (e.target.classList.contains('modal')) closeModal(e.target.id);

        const popover = $('#actionPopover');
        if (popover.classList.contains('show') && !e.target.closest('.action-popover') && !e.target
            .closest('.action-btn')) {
            popover.classList.remove('show');
        }
    });

    // === BẮT ĐẦU KHỐI LOGIC ĐÚNG CHO TAB ===
    $$('.tab-nav-item').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabContainer = tab.closest('.modal-body');
            if (!tabContainer) return;

            // Xóa active class khỏi tất cả các tab và content pane
            tabContainer.querySelectorAll('.tab-nav-item').forEach(el => el.classList.remove(
                'active'));
            tabContainer.querySelectorAll('.tab-pane').forEach(el => el.classList.remove(
                'active'));

            // Thêm active class cho tab được click
            tab.classList.add('active');

            // Thêm active class cho content pane tương ứng
            const tabContentId = '#tab-' + tab.dataset.tab;
            const tabContent = tabContainer.querySelector(tabContentId);
            if (tabContent) {
                tabContent.classList.add('active');
            }

            // Nếu người dùng click vào tab overview và biểu đồ chưa được vẽ, hãy vẽ nó.
            if (tab.dataset.tab === 'overview' && !G.storageChartInstance) {
                setTimeout(() => renderStorageChart(), 50);
            }
        });
    });
    // === KẾT THÚC KHỐI LOGIC ĐÚNG CHO TAB ===

    $('#folder-tree-container').addEventListener('click', e => {
        const folderItem = e.target.closest('.folder-item');
        if (folderItem) {
            $$('#folder-tree-container .folder-item').forEach(item => item.classList.remove(
                'selected'));
            folderItem.classList.add('selected');
            G.destinationFolderId = parseInt(folderItem.dataset.id);
            $('#confirmMoveBtn').disabled = false;
            const toggleIcon = folderItem.querySelector('.toggle-icon');
            const sublist = folderItem.nextElementSibling;
            if (toggleIcon && sublist && sublist.tagName === 'UL') {
                sublist.style.display = sublist.style.display === 'none' ? 'block' : 'none';
                toggleIcon.classList.toggle('collapsed');
            }
        }
    });
    $('#confirmMoveBtn').addEventListener('click', async () => {
        if (G.itemsToMove.length > 0 && G.destinationFolderId !== null) {
            const result = await apiCall('move', {
                item_ids: G.itemsToMove,
                destination_id: G.destinationFolderId
            });
            if (result.success) {
                showToast(result.message);
                closeModal('moveModal');
                navigateToPath(window.location.search, true);
            }
        }
    });
});
</script>

</html>