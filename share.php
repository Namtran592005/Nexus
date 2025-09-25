<?php
define('IS_PUBLIC_PAGE', true); // This is a public page
require_once 'bootstrap.php';

/**
 * Renders the 404 error page.
 */
function show_404_page() {
    http_response_code(404);
    if (file_exists('404.php')) {
        require_once '404.php';
    } else {
        echo '<h1>404 Not Found</h1><p>The share link is invalid or has expired.</p>';
    }
    exit();
}

// --- LOGIC: PUBLIC ACCESS (No login required) ---
if (isset($_GET['id'])) {
    $shareId = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT fs.id, fs.name, fs.mime_type, fs.size, fs.content 
            FROM file_system fs
            JOIN share_links sl ON fs.id = sl.file_id
            WHERE sl.id = ? AND fs.is_deleted = 0
        ");
        $stmt->execute([$shareId]);
        $file = $stmt->fetch();

        if (!$file) {
            show_404_page();
        }
        
        $action = $_GET['action'] ?? 'view';

        if ($action === 'download') {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $file['size']);
            ob_clean();
            flush();
            echo $file['content'];
            exit();
        }
        
        if ($action === 'preview') {
            header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
            header('Content-Disposition: inline; filename="' . basename($file['name']) . '"');
            header('Content-Length: ' . $file['size']);
            ob_clean();
            flush();
            echo $file['content'];
            exit();
        }
        
        $fileInfo = getFileIcon($file['name']);
        $fileSizeFormatted = formatBytes($file['size']);
        $fileKind = strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)) . ' File';
        $canPreview = isImage($file['mime_type']) || isVideo($file['mime_type']) || isAudio($file['mime_type']) || isPdf($file['mime_type']) || (isTextOrCode($file['mime_type']) && $file['size'] < 5 * 1024 * 1024);

    } catch (PDOException $e) {
        error_log("Share page access error: " . $e->getMessage());
        show_404_page();
    }
} else {
    show_404_page();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download: <?php echo htmlspecialchars($file['name']); ?></title>
    <link rel="stylesheet" href="./src/custom-fonts.css">
    <link rel="stylesheet" href="./src/css/all.min.css">
    <style>
    /* CSS is unchanged */
    :root {
        --bg-primary: #161618;
        --bg-secondary: #1d1d20;
        --text-primary: #f0f0f0;
        --text-secondary: #a0a0a0;
        --text-accent: #0a84ff;
        --border-color: #3a3a3c;
        --radius-default: 10px;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background-color: var(--bg-primary);
        color: var(--text-primary);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
        box-sizing: border-box;
    }

    .share-container {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-default);
        padding: 40px;
        max-width: 600px;
        width: 100%;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .file-icon {
        font-size: 5em;
        margin-bottom: 20px;
    }

    .file-name {
        font-size: 1.8em;
        font-weight: 700;
        margin-bottom: 10px;
        word-break: break-all;
    }

    .file-details {
        color: var(--text-secondary);
        margin-bottom: 30px;
    }

    .actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 25px;
        border-radius: var(--radius-default);
        text-decoration: none;
        font-weight: 500;
        font-size: 1em;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .btn-download {
        background-color: var(--text-accent);
        color: white;
        border-color: var(--text-accent);
    }

    .btn-download:hover {
        background-color: #007aff;
        transform: translateY(-2px);
    }

    .btn-preview {
        background-color: transparent;
        color: var(--text-primary);
        border-color: var(--border-color);
    }

    .btn-preview:hover {
        background-color: var(--border-color);
    }

    #preview-box {
        margin-top: 30px;
        width: 100%;
        max-height: 70vh;
        display: none;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-default);
        overflow: hidden;
        background-color: #000;
    }

    #preview-box iframe,
    #preview-box img,
    #preview-box video,
    #preview-box audio {
        width: 100%;
        height: 60vh;
        border: none;
        display: block;
    }
    </style>
</head>

<body>
    <div class="share-container">
        <i class="fas <?php echo htmlspecialchars($fileInfo['icon']); ?> file-icon"
            style="color: <?php echo htmlspecialchars($fileInfo['color']); ?>;"></i>
        <h1 class="file-name"><?php echo htmlspecialchars($file['name']); ?></h1>
        <p class="file-details"><?php echo htmlspecialchars($fileKind); ?> &bull; <?php echo $fileSizeFormatted; ?></p>

        <div class="actions">
            <?php if ($canPreview): ?>
            <button class="btn btn-preview" onclick="togglePreview()">
                <i class="fas fa-eye"></i> <span>Preview</span>
            </button>
            <?php endif; ?>
            <a href="?id=<?php echo htmlspecialchars($shareId); ?>&action=download" class="btn btn-download">
                <i class="fas fa-download"></i> <span>Download</span>
            </a>
        </div>

        <div id="preview-box"></div>
    </div>

    <script>
    // JS is unchanged
    function togglePreview() {
        const previewBox = document.getElementById('preview-box');
        if (previewBox.style.display === 'block') {
            previewBox.style.display = 'none';
            previewBox.innerHTML = '';
        } else {
            previewBox.style.display = 'block';
            const fileType = '<?php echo $file['mime_type']; ?>';
            const previewUrl = '?id=<?php echo htmlspecialchars($shareId); ?>&action=preview';
            let content = '';

            if (fileType.startsWith('image/')) {
                content = `<img src="${previewUrl}" alt="File preview">`;
            } else if (fileType.startsWith('video/')) {
                content = `<video controls autoplay src="${previewUrl}"></video>`;
            } else if (fileType.startsWith('audio/')) {
                content = `<audio controls autoplay src="${previewUrl}"></audio>`;
            } else {
                content = `<iframe src="${previewUrl}"></iframe>`;
            }
            previewBox.innerHTML = content;
        }
    }
    </script>
</body>

</html>