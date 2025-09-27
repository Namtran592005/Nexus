<?php
define("IS_PUBLIC_PAGE", true);
require_once "bootstrap.php";

function show_share_error_page($title, $message)
{
    http_response_code(404);
    // Đây là một template lỗi đơn giản, bạn có thể tạo một file riêng nếu muốn
    echo "<!DOCTYPE html><html><head><title>$title</title><style>body{font-family:sans-serif;text-align:center;padding-top:50px;background:#f0f2f5;color:#333;}h1{color:#ff453a;}</style></head><body><h1>$title</h1><p>$message</p><a href='" .
        BASE_URL .
        "'>Go to Homepage</a></body></html>";
    exit();
}

if (!isset($_GET["id"])) {
    show_share_error_page("Invalid Link", "The share link is missing an ID.");
}

$shareId = $_GET["id"];
$pdo->beginTransaction();
$stmt = $pdo->prepare("
    SELECT fs.id, fs.name, fs.mime_type, fs.size, fs.content, sl.password, sl.expires_at, sl.allow_download
    FROM file_system fs
    JOIN share_links sl ON fs.id = sl.file_id
    WHERE sl.id = ? AND fs.is_deleted = 0
");
$stmt->execute([$shareId]);
$file = $stmt->fetch();
$pdo->commit();

if (!$file) {
    show_share_error_page(
        "Not Found",
        "The share link you are trying to access is invalid or has been removed."
    );
}

// Kiểm tra ngày hết hạn
if ($file["expires_at"] && strtotime($file["expires_at"]) < time()) {
    show_share_error_page("Link Expired", "This share link has expired.");
}

$is_authorized = false;
// Kiểm tra mật khẩu
if (!empty($file["password"])) {
    if (
        isset($_SESSION["share_authorized"][$shareId]) &&
        $_SESSION["share_authorized"][$shareId] === true
    ) {
        $is_authorized = true;
    }

    $password_error = "";
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        isset($_POST["share_password"])
    ) {
        if (password_verify($_POST["share_password"], $file["password"])) {
            $_SESSION["share_authorized"][$shareId] = true;
            $is_authorized = true;
            header("Location: " . $_SERVER["REQUEST_URI"]); // Tải lại trang để xóa dữ liệu POST
            exit();
        } else {
            $password_error = "Incorrect password. Please try again.";
        }
    }

    if (!$is_authorized) {
        // Hiển thị form nhập mật khẩu
        http_response_code(401);
        echo "<!DOCTYPE html><html><head><title>Password Required</title><style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f0f2f5;} form{background:white;padding:40px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.1);width:300px;text-align:center;} input{width:100%;padding:10px;margin:10px 0;box-sizing:border-box;border-radius:5px;border:1px solid #ccc;} button{width:100%;padding:10px;background:#007aff;color:white;border:none;border-radius:5px;cursor:pointer;} .error{color:red;font-size:0.9em;}</style></head><body>
            <form method='POST'>
                <h2><i class='fas fa-lock'></i> Password Required</h2>
                <p>This content is password protected.</p>
                <input type='password' name='share_password' placeholder='Enter password' autofocus>
                " .
            ($password_error ? "<p class='error'>$password_error</p>" : "") .
            "
                <button type='submit'>Unlock</button>
            </form>
        </body></html>";
        exit();
    }
}

// Nếu đã qua tất cả các bước kiểm tra, xử lý action
$action = $_GET["action"] ?? "view";

if ($action === "download" || $action === "preview") {
    if ($action === "download" && $file["allow_download"] != 1) {
        show_share_error_page(
            "Permission Denied",
            "Downloading is not permitted for this link."
        );
    }

    header(
        "Content-Type: " . ($file["mime_type"] ?: "application/octet-stream")
    );
    $disposition = $action === "preview" ? "inline" : "attachment";
    header(
        "Content-Disposition: $disposition; filename=\"" .
            basename($file["name"]) .
            "\""
    );
    header("Content-Length: " . $file["size"]);
    ob_clean();
    flush();
    echo $file["content"];
    exit();
}

// Dữ liệu cho trang view mặc định
$fileInfo = getFileIcon($file["name"]);
$fileSizeFormatted = formatBytes($file["size"]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download: <?php echo htmlspecialchars($file["name"]); ?></title>
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
        <i class="fas <?php echo htmlspecialchars(
            $fileInfo["icon"]
        ); ?> file-icon"
            style="color: <?php echo htmlspecialchars(
                $fileInfo["color"]
            ); ?>;"></i>
        <h1 class="file-name"><?php echo htmlspecialchars(
            $file["name"]
        ); ?></h1>
        <p class="file-details"><?php echo $fileSizeFormatted; ?></p>
        <div class="actions">
            <button class="btn btn-preview" onclick="togglePreview()">
                <i class="fas fa-eye"></i> <span>Preview</span>
            </button>
            <?php if (
                $file["allow_download"] == 1
            ):// Chỉ hiển thị nút Download nếu được phép
                 ?>
            <a href="?id=<?php echo htmlspecialchars(
                $shareId
            ); ?>&action=download" class="btn btn-download">
                <i class="fas fa-download"></i> <span>Download</span>
            </a>
            <?php endif; ?>
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
            const fileType = '<?php echo $file["mime_type"]; ?>';
            const previewUrl = '?id=<?php echo htmlspecialchars(
                $shareId
            ); ?>&action=preview';
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