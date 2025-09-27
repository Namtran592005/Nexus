<?php
// Tệp bootstrap.php cần được gọi trước tiên
// Tuy nhiên, với các action download, chúng ta không muốn output JSON
// nên sẽ xử lý header sau
$isDownloadAction =
    isset($_REQUEST["action"]) &&
    in_array($_REQUEST["action"], ["download_file", "download_archive"]);

if (!$isDownloadAction) {
    require_once "bootstrap.php";
    header("Content-Type: application/json");
    $response = ["success" => false, "message" => "Invalid action specified."];
} else {
    // Chỉ gọi bootstrap cho các action download để có kết nối DB và helpers
    // không set header JSON
    require_once "bootstrap.php";
}

// Get POST data (prioritize JSON body, then form data)
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}
$action = $input["action"] ?? ($_GET["action"] ?? ($_POST["action"] ?? null));

// Hàm đệ quy để xây dựng cây thư mục
function buildFolderTree($pdo, $parentId, $excludeIds = [])
{
    $stmt = $pdo->prepare(
        "SELECT id, name FROM file_system WHERE parent_id = ? AND type = 'folder' AND is_deleted = 0 ORDER BY name ASC"
    );
    $stmt->execute([$parentId]);
    $folders = [];
    while ($folder = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Loại bỏ các thư mục đang được chọn di chuyển để tránh di chuyển vào chính nó
        if (in_array($folder["id"], $excludeIds)) {
            continue;
        }
        $children = buildFolderTree($pdo, $folder["id"], $excludeIds);
        $folder["children"] = $children;
        $folders[] = $folder;
    }
    return $folders;
}

try {
    switch ($action) {
        // --- CÁC ACTION CŨ VẪN GIỮ NGUYÊN ---
        // (new_folder, rename, delete, restore, ..., create_share_link)

        // --- ACTION MỚI: LẤY DỮ LIỆU CHO CÁC VIEW ---
        // THAY THẾ TOÀN BỘ CASE 'get_view_data' BẰNG PHIÊN BẢN NÀY
        case "get_folder_tree":
            $excludeIds = isset($input["exclude_ids"])
                ? (array) $input["exclude_ids"]
                : [];
            $folderTree = buildFolderTree($pdo, ROOT_FOLDER_ID, $excludeIds);
            // Thêm thư mục gốc vào đầu
            array_unshift($folderTree, [
                "id" => ROOT_FOLDER_ID,
                "name" => "Drive (Root)",
                "children" => [],
            ]);
            $response = ["success" => true, "tree" => $folderTree];
            break;

        case "get_view_data":
            // Luôn đọc từ $_GET cho action này
            $view = $_GET["view"] ?? "browse";
            $path = $_GET["path"] ?? "";
            $searchTerm = $_GET["q"] ?? "";

            $items = [];
            $currentFolderId = ROOT_FOLDER_ID;
            $pageTitle = ucfirst($view);

            if ($view === "browse") {
                if (!empty($path)) {
                    $currentFolderId = getItemIdByPath($pdo, $path);
                    if ($currentFolderId === null) {
                        throw new Exception("Invalid path specified.");
                    }
                }
                $stmt = $pdo->prepare(
                    "SELECT id, name, type, size, modified_at AS modified FROM file_system WHERE parent_id = ? AND is_deleted = 0"
                );
                $stmt->execute([$currentFolderId]);
                $items = $stmt->fetchAll();
            } elseif ($view === "recents") {
                $stmt = $pdo->query(
                    "SELECT id, name, type, size, modified_at AS modified FROM file_system WHERE type = 'file' AND is_deleted = 0 ORDER BY modified_at DESC LIMIT 50"
                );
                $items = $stmt->fetchAll();
            } elseif ($view === "shared") {
                $stmt = $pdo->query(
                    "SELECT fs.id, fs.name, fs.type, fs.size, fs.modified_at AS modified, sl.id AS share_id FROM file_system fs JOIN share_links sl ON fs.id = sl.file_id WHERE fs.is_deleted = 0 ORDER BY fs.name ASC"
                );
                $items = $stmt->fetchAll();
            } elseif ($view === "trash") {
                $stmt = $pdo->query(
                    "SELECT id, name, type, size, deleted_at AS modified FROM file_system WHERE is_deleted = 1 ORDER BY deleted_at DESC"
                );
                $items = $stmt->fetchAll();
            } elseif ($view === "search") {
                $pageTitle = "Search Results";
                if (!empty($searchTerm)) {
                    $stmt = $pdo->prepare(
                        "SELECT id, name, type, size, modified_at AS modified, parent_id FROM file_system WHERE name LIKE ? AND is_deleted = 0 ORDER BY type, name"
                    );
                    $stmt->execute(["%" . $searchTerm . "%"]);
                    $items = $stmt->fetchAll();
                }
            }

            foreach ($items as &$item) {
                if ($view === "browse" && $item["type"] === "folder") {
                    $item["relative_path"] = !empty($path)
                        ? $path . "/" . $item["name"]
                        : $item["name"];
                }
                if ($view === "search") {
                    $item["full_path"] = getPathByItemId(
                        $pdo,
                        $item["parent_id"]
                    );
                }
                $item["modified"] = strtotime($item["modified"]);
            }
            unset($item);

            usort($items, function ($a, $b) {
                if ($a["type"] !== $b["type"]) {
                    return $a["type"] === "folder" ? -1 : 1;
                }
                return strcasecmp($a["name"], $b["name"]);
            });

            $breadcrumbs = [];
            $parentPath = null;
            if ($view === "browse") {
                $breadcrumbs[] = ["name" => "Drive", "path" => ""];
                if (!empty($path)) {
                    $pathParts = explode("/", $path);
                    $accumulatedPath = "";
                    foreach ($pathParts as $part) {
                        if (!empty($part)) {
                            $accumulatedPath .=
                                (empty($accumulatedPath) ? "" : "/") . $part;
                            $breadcrumbs[] = [
                                "name" => $part,
                                "path" => $accumulatedPath,
                            ];
                        }
                    }
                }
                if ($currentFolderId != ROOT_FOLDER_ID) {
                    $parentStmt = $pdo->prepare(
                        "SELECT parent_id FROM file_system WHERE id = ?"
                    );
                    $parentStmt->execute([$currentFolderId]);
                    $parentOfCurrentId = $parentStmt->fetchColumn();
                    if ($parentOfCurrentId) {
                        $parentPath = getPathByItemId($pdo, $parentOfCurrentId);
                    }
                }
            }

            $response = [
                "success" => true,
                "view" => $view,
                "pageTitle" => $pageTitle,
                "items" => $items,
                "breadcrumbs" => $breadcrumbs,
                "currentFolderId" => $currentFolderId,
                "currentPath" => $path,
                "parentPath" => $parentPath,
            ];
            break;

        case "new_folder":
            // ... code giữ nguyên ...
            $folderName = trim($input["folder_name"] ?? "");
            $parentId = (int) ($input["parent_id"] ?? ROOT_FOLDER_ID);
            $folderName = preg_replace("/[^\p{L}\p{N} _.-]/u", "", $folderName);
            if (empty($folderName)) {
                throw new Exception("Invalid or empty folder name.");
            }
            $stmt = $pdo->prepare(
                "SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0"
            );
            $stmt->execute([$folderName, $parentId]);
            if ($stmt->fetch()) {
                throw new Exception(
                    'Folder "' .
                        htmlspecialchars($folderName) .
                        '" already exists.'
                );
            }
            $stmt = $pdo->prepare(
                "INSERT INTO file_system (parent_id, name, type, size) VALUES (?, ?, 'folder', 0)"
            );
            $stmt->execute([$parentId, $folderName]);
            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare(
                "SELECT id, name, type, size, modified_at AS modified FROM file_system WHERE id = ?"
            );
            $stmt->execute([$newId]);
            $newItem = $stmt->fetch();
            $newItem["modified"] = strtotime($newItem["modified"]);
            $response = [
                "success" => true,
                "message" => "Folder created.",
                "item" => $newItem,
            ];
            break;
        case "rename":
            // ... code giữ nguyên ...
            $id = (int) ($input["id"] ?? 0);
            $newNameFromForm = trim($input["new_name"] ?? "");
            $stmt = $pdo->prepare(
                "SELECT name, type, parent_id, is_deleted FROM file_system WHERE id = ?"
            );
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if (!$item) {
                throw new Exception("Item not found.");
            }
            $sanitizedFilename = preg_replace(
                "/[^\p{L}\p{N} _.-]/u",
                "",
                $newNameFromForm
            );
            if (empty($sanitizedFilename)) {
                throw new Exception("The new name is invalid.");
            }
            $finalNewName = $sanitizedFilename;
            if ($item["type"] === "file") {
                $originalExtension = pathinfo(
                    $item["name"],
                    PATHINFO_EXTENSION
                );
                if (!empty($originalExtension)) {
                    $finalNewName =
                        $sanitizedFilename . "." . $originalExtension;
                }
            }
            $stmt = $pdo->prepare(
                "SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND id != ? AND is_deleted = ?"
            );
            $stmt->execute([
                $finalNewName,
                $item["parent_id"],
                $id,
                $item["is_deleted"],
            ]);
            if ($stmt->fetch()) {
                throw new Exception("An item with that name already exists.");
            }
            $stmt = $pdo->prepare(
                "UPDATE file_system SET name = ? WHERE id = ?"
            );
            $stmt->execute([$finalNewName, $id]);
            $response = [
                "success" => true,
                "message" => "Rename successful.",
                "newName" => $finalNewName,
            ];
            break;
        case "delete":
            // ... code giữ nguyên ...
            $ids = (array) ($input["ids"] ?? []);
            $forceDelete = ($input["force_delete"] ?? "false") === "true";
            if (empty($ids)) {
                throw new Exception("No items selected.");
            }
            $placeholders = rtrim(str_repeat("?,", count($ids)), ",");
            if ($forceDelete) {
                $stmt = $pdo->prepare(
                    "DELETE FROM file_system WHERE id IN ($placeholders)"
                );
                $stmt->execute($ids);
                $message = count($ids) . " item(s) permanently deleted.";
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE file_system SET is_deleted = 1, deleted_at = datetime('now') WHERE id IN ($placeholders)"
                );
                $stmt->execute($ids);
                $message = count($ids) . " item(s) moved to trash.";
            }
            $response = ["success" => true, "message" => $message];
            break;
        case "restore":
            // ... code giữ nguyên ...
            $ids = (array) ($input["ids"] ?? []);
            if (empty($ids)) {
                throw new Exception("No items to restore.");
            }
            $pdo->beginTransaction();
            foreach ($ids as $id) {
                $stmt = $pdo->prepare(
                    "SELECT name, parent_id FROM file_system WHERE id = ? AND is_deleted = 1"
                );
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                if (!$item) {
                    continue;
                }
                $checkStmt = $pdo->prepare(
                    "SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0"
                );
                $checkStmt->execute([$item["name"], $item["parent_id"]]);
                $newName = $item["name"];
                if ($checkStmt->fetch()) {
                    $info = pathinfo($item["name"]);
                    $newName =
                        $info["filename"] .
                        "_restored_" .
                        time() .
                        (isset($info["extension"])
                            ? "." . $info["extension"]
                            : "");
                }
                $updateStmt = $pdo->prepare(
                    "UPDATE file_system SET is_deleted = 0, deleted_at = NULL, name = ? WHERE id = ?"
                );
                $updateStmt->execute([$newName, $id]);
            }
            $pdo->commit();
            $response = [
                "success" => true,
                "message" => count($ids) . " item(s) restored.",
            ];
            break;
        case "empty_trash":
            // ... code giữ nguyên ...
            $stmt = $pdo->query("DELETE FROM file_system WHERE is_deleted = 1");
            $response = [
                "success" => true,
                "message" => "Trash has been emptied.",
            ];
            break;
        // --- NÂNG CẤP ACTION 'move' ---
        case "move":
            // Chấp nhận một mảng các itemIds
            $itemIds = (array) ($input["item_ids"] ?? []);
            $destinationId = (int) ($input["destination_id"] ?? 0);

            if (empty($itemIds)) {
                throw new Exception("No items selected to move.");
            }

            $pdo->beginTransaction();

            foreach ($itemIds as $itemId) {
                $itemId = (int) $itemId;
                if ($itemId === 0) {
                    continue;
                }

                $stmt = $pdo->prepare(
                    "SELECT name, type, parent_id FROM file_system WHERE id = ?"
                );
                $stmt->execute([$itemId]);
                $item = $stmt->fetch();
                if (!$item) {
                    continue;
                } // Bỏ qua nếu không tìm thấy

                if ($itemId === $destinationId) {
                    throw new Exception("Cannot move an item into itself.");
                }
                if ($item["parent_id"] === $destinationId) {
                    continue;
                } // Bỏ qua nếu đã ở đúng vị trí

                // Kiểm tra di chuyển thư mục vào thư mục con của nó
                if ($item["type"] === "folder") {
                    $currentParent = $destinationId;
                    while (
                        $currentParent != ROOT_FOLDER_ID &&
                        $currentParent != null
                    ) {
                        if ($currentParent == $itemId) {
                            throw new Exception(
                                "Cannot move a folder into one of its own subfolders."
                            );
                        }
                        $stmt = $pdo->prepare(
                            "SELECT parent_id FROM file_system WHERE id = ?"
                        );
                        $stmt->execute([$currentParent]);
                        $currentParent = $stmt->fetchColumn();
                    }
                }

                // Kiểm tra tên trùng lặp
                $stmt = $pdo->prepare(
                    "SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0"
                );
                $stmt->execute([$item["name"], $destinationId]);
                if ($stmt->fetch()) {
                    // Nếu có thể, bạn có thể thêm logic đổi tên tự động ở đây
                    // ví dụ: 'file_copy.txt'
                    throw new Exception(
                        'An item named "' .
                            htmlspecialchars($item["name"]) .
                            '" already exists in the destination.'
                    );
                }

                $stmt = $pdo->prepare(
                    "UPDATE file_system SET parent_id = ? WHERE id = ?"
                );
                $stmt->execute([$destinationId, $itemId]);
            }

            $pdo->commit();

            $response = [
                "success" => true,
                "message" => count($itemIds) . " item(s) moved successfully.",
            ];
            break;
            // ... code giữ nguyên ...
            $itemId = (int) ($input["item_id"] ?? 0);
            $destinationId = (int) ($input["destination_id"] ?? 0);
            $stmt = $pdo->prepare(
                "SELECT name, type, parent_id FROM file_system WHERE id = ?"
            );
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            if (!$item) {
                throw new Exception("Source item not found.");
            }
            if ($itemId === $destinationId) {
                throw new Exception("Cannot move an item into itself.");
            }
            if ($item["parent_id"] === $destinationId) {
                throw new Exception("Item is already in this folder.");
            }
            if ($item["type"] === "folder") {
                $currentParent = $destinationId;
                while (
                    $currentParent != ROOT_FOLDER_ID &&
                    $currentParent != null
                ) {
                    if ($currentParent == $itemId) {
                        throw new Exception(
                            "Cannot move a folder into its own subfolder."
                        );
                    }
                    $stmt = $pdo->prepare(
                        "SELECT parent_id FROM file_system WHERE id = ?"
                    );
                    $stmt->execute([$currentParent]);
                    $currentParent = $stmt->fetchColumn();
                }
            }
            $stmt = $pdo->prepare(
                "SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0"
            );
            $stmt->execute([$item["name"], $destinationId]);
            if ($stmt->fetch()) {
                throw new Exception(
                    'An item named "' .
                        htmlspecialchars($item["name"]) .
                        '" already exists.'
                );
            }
            $stmt = $pdo->prepare(
                "UPDATE file_system SET parent_id = ? WHERE id = ?"
            );
            $stmt->execute([$destinationId, $itemId]);
            $response = [
                "success" => true,
                "message" => "Item moved successfully.",
            ];
            break;
        case "live_search":
            // ... code giữ nguyên ...
            $searchTerm = $input["q"] ?? "";
            $items = [];
            if (strlen($searchTerm) >= 2) {
                $stmt = $pdo->prepare(
                    "SELECT id, name, type, parent_id FROM file_system WHERE name LIKE ? AND is_deleted = 0 ORDER BY type, name LIMIT 10"
                );
                $stmt->execute(["%" . $searchTerm . "%"]);
                $items = $stmt->fetchAll();
                foreach ($items as &$item) {
                    $item["full_path"] = getPathByItemId(
                        $pdo,
                        $item["parent_id"]
                    );
                }
                unset($item);
            }
            $response = ["success" => true, "items" => $items];
            break;
        case "get_details":
            // ... code giữ nguyên ...
            $id = (int) ($input["id"] ?? 0);
            $stmt = $pdo->prepare(
                "SELECT id, name, type, size, mime_type, created_at, modified_at FROM file_system WHERE id = ?"
            );
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if (!$item) {
                throw new Exception("Item not found.");
            }
            $item["created_at_formatted"] = date(
                "d/m/Y H:i",
                strtotime($item["created_at"])
            );
            $item["modified_at_formatted"] = date(
                "d/m/Y H:i",
                strtotime($item["modified_at"])
            );
            $item["size_formatted"] = formatBytes($item["size"]);
            $item["kind"] =
                $item["type"] === "folder"
                    ? "Folder"
                    : (strtoupper(
                        pathinfo($item["name"], PATHINFO_EXTENSION)
                    ) ?:
                    "File");
            $image_mimes = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
            ];
            if (in_array($item["mime_type"], $image_mimes)) {
                $item["preview_url"] =
                    "api.php?action=download_file&id=" .
                    $item["id"] .
                    "&thumbnail=true";
            } else {
                $item["preview_url"] = null;
            }
            $response = ["success" => true, "item" => $item];
            break;
        case "start_upload":
            // ... code giữ nguyên ...
            define("TEMP_UPLOAD_DIR", __DIR__ . "/temp_uploads");
            if (!is_dir(TEMP_UPLOAD_DIR)) {
                if (!mkdir(TEMP_UPLOAD_DIR, 0777, true)) {
                    throw new Exception(
                        "Failed to create temporary upload directory."
                    );
                }
            }
            if (!is_writable(TEMP_UPLOAD_DIR)) {
                throw new Exception(
                    "Temporary upload directory is not writable."
                );
            }
            $pdo->beginTransaction();
            $fileName = $_POST["fileName"] ?? "uploading...";
            $fileSize = (int) ($_POST["fileSize"] ?? 0);
            $parentId = (int) ($_POST["parentId"] ?? ROOT_FOLDER_ID);
            $mimeType = $_POST["mimeType"] ?? "application/octet-stream";
            $stmt = $pdo->prepare(
                "INSERT INTO file_system (parent_id, name, type, mime_type, size, content) VALUES (?, ?, 'file', ?, ?, NULL)"
            );
            $stmt->execute([$parentId, $fileName, $mimeType, $fileSize]);
            $fileId = $pdo->lastInsertId();
            $uploadDir = TEMP_UPLOAD_DIR . "/" . $fileId;
            if (is_dir($uploadDir)) {
                array_map("unlink", glob("$uploadDir/*"));
            } else {
                mkdir($uploadDir, 0777, true);
            }
            $pdo->commit();
            $response = ["success" => true, "fileId" => $fileId];
            break;
        case "upload_chunk":
            // ... code giữ nguyên ...
            define("TEMP_UPLOAD_DIR", __DIR__ . "/temp_uploads");
            $fileId = (int) ($_POST["fileId"] ?? 0);
            $chunkIndex = (int) ($_POST["chunkIndex"] ?? -1);
            $chunkFile = $_FILES["chunk"]["tmp_name"] ?? null;
            $uploadDir = TEMP_UPLOAD_DIR . "/" . $fileId;
            if (
                $fileId <= 0 ||
                $chunkIndex < 0 ||
                !$chunkFile ||
                !is_dir($uploadDir)
            ) {
                throw new Exception(
                    "Invalid chunk data, file ID, or temporary directory not found."
                );
            }
            $chunkPath = $uploadDir . "/" . $chunkIndex;
            if (!move_uploaded_file($chunkFile, $chunkPath)) {
                throw new Exception(
                    "Failed to move chunk {$chunkIndex} to temporary storage."
                );
            }
            $response = [
                "success" => true,
                "message" => "Chunk {$chunkIndex} stored.",
            ];
            break;
        case "complete_upload":
            // ... code giữ nguyên ...
            define("TEMP_UPLOAD_DIR", __DIR__ . "/temp_uploads");
            set_time_limit(600);
            $fileId = (int) ($_POST["fileId"] ?? 0);
            $totalChunks = (int) ($_POST["totalChunks"] ?? 0);
            $uploadDir = TEMP_UPLOAD_DIR . "/" . $fileId;
            if ($fileId <= 0 || $totalChunks <= 0 || !is_dir($uploadDir)) {
                throw new Exception(
                    "Invalid file ID or total chunks for completion."
                );
            }
            $uploadedCount = count(glob($uploadDir . "/*"));
            if ($uploadedCount < $totalChunks) {
                throw new Exception(
                    "Incomplete upload. Expected {$totalChunks}, found {$uploadedCount}."
                );
            }
            $pdo->beginTransaction();
            $init_stmt = $pdo->prepare(
                "UPDATE file_system SET content = X'' WHERE id = ?"
            );
            $init_stmt->execute([$fileId]);
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $uploadDir . "/" . $i;
                $chunkContent = file_get_contents($chunkPath);
                if ($chunkContent === false) {
                    throw new Exception("Could not read chunk {$i}.");
                }
                $update_stmt = $pdo->prepare(
                    "UPDATE file_system SET content = content || ? WHERE id = ?"
                );
                $update_stmt->execute([$chunkContent, $fileId]);
                unlink($chunkPath);
            }
            rmdir($uploadDir);
            $pdo->commit();
            $response = [
                "success" => true,
                "message" => "File assembled successfully.",
            ];
            break;
        case "get_preview_data":
            // ... code giữ nguyên ...
            $id = (int) ($input["id"] ?? 0);
            $stmt = $pdo->prepare(
                "SELECT id, name, type, mime_type, size, modified_at, content FROM file_system WHERE id = ? AND type = 'file' AND is_deleted = 0"
            );
            $stmt->execute([$id]);
            $file = $stmt->fetch();
            if (!$file) {
                throw new Exception("File not found or cannot be accessed.");
            }
            $mimeType = $file["mime_type"] ?: "application/octet-stream";
            $fileUrl =
                BASE_URL .
                "api.php?action=download_file&id=" .
                $file["id"] .
                "&inline=true";
            if (isImage($mimeType)) {
                $response = [
                    "success" => true,
                    "type" => "image",
                    "data" => $fileUrl,
                    "mime_type" => $mimeType,
                ];
            } elseif (isVideo($mimeType)) {
                $response = [
                    "success" => true,
                    "type" => "video",
                    "data" => $fileUrl,
                    "mime_type" => $mimeType,
                ];
            } elseif (isAudio($mimeType)) {
                $response = [
                    "success" => true,
                    "type" => "audio",
                    "data" => $fileUrl,
                    "mime_type" => $mimeType,
                ];
            } elseif (isPdf($mimeType)) {
                $response = [
                    "success" => true,
                    "type" => "pdf",
                    "data" => $fileUrl,
                    "mime_type" => $mimeType,
                ];
            } elseif (
                isTextOrCode($mimeType) &&
                $file["size"] < 2 * 1024 * 1024
            ) {
                $response = [
                    "success" => true,
                    "type" => "code",
                    "data" => $file["content"],
                    "mime_type" => $mimeType,
                    "language" => guessCodeLanguage($file["name"]),
                ];
            } else {
                $response = [
                    "success" => true,
                    "type" => "details",
                    "data" => [
                        "name" => $file["name"],
                        "size" => formatBytes($file["size"]),
                    ],
                    "message" => "Preview is not available for this file type.",
                ];
            }
            break;
        case "get_share_details":
            $file_id = (int) ($input["file_id"] ?? 0);
            if ($file_id <= 0) {
                throw new Exception("Invalid file ID.");
            }

            $stmt = $pdo->prepare(
                "SELECT id, password, expires_at, allow_download FROM share_links WHERE file_id = ?"
            );
            $stmt->execute([$file_id]);
            $details = $stmt->fetch(PDO::FETCH_ASSOC);

            // Không trả về hash mật khẩu, chỉ cần cho biết nó có tồn tại hay không
            if ($details) {
                $details["has_password"] = !empty($details["password"]);
                unset($details["password"]);
            }

            $response = ["success" => true, "details" => $details];
            break;

        case "update_share_link":
            $file_id = (int) ($input["file_id"] ?? 0);
            if ($file_id <= 0) {
                throw new Exception("Invalid file ID.");
            }

            $password = $input["password"] ?? null;
            $expires_at = $input["expires_at"] ?? null;
            $allow_download = isset($input["allow_download"])
                ? (int) $input["allow_download"]
                : 1;

            $hashed_password = null;
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }

            // Kiểm tra link đã tồn tại chưa
            $stmt = $pdo->prepare(
                "SELECT id FROM share_links WHERE file_id = ?"
            );
            $stmt->execute([$file_id]);
            $existing_link = $stmt->fetch();

            if ($existing_link) {
                // Cập nhật link hiện có
                $shareId = $existing_link["id"];
                $stmt = $pdo->prepare(
                    "UPDATE share_links SET password = ?, expires_at = ?, allow_download = ? WHERE file_id = ?"
                );
                $stmt->execute([
                    $hashed_password,
                    $expires_at,
                    $allow_download,
                    $file_id,
                ]);
            } else {
                // Tạo link mới
                $shareId = bin2hex(random_bytes(8));
                $stmt = $pdo->prepare(
                    "INSERT INTO share_links (id, file_id, password, expires_at, allow_download) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $shareId,
                    $file_id,
                    $hashed_password,
                    $expires_at,
                    $allow_download,
                ]);
            }
            $response = [
                "success" => true,
                "message" => "Share settings updated.",
                "share_id" => $shareId,
            ];
            break;

        case "remove_share_link":
            $file_id = (int) ($input["file_id"] ?? 0);
            if ($file_id <= 0) {
                throw new Exception("Invalid file ID.");
            }

            $stmt = $pdo->prepare("DELETE FROM share_links WHERE file_id = ?");
            $stmt->execute([$file_id]);

            $response = ["success" => true, "message" => "Share link removed."];
            break;
            // ... code giữ nguyên ...
            $file_id = (int) ($input["file_id"] ?? 0);
            if ($file_id <= 0) {
                throw new Exception("Invalid file ID.");
            }
            $stmt = $pdo->prepare(
                "SELECT id FROM share_links WHERE file_id = ?"
            );
            $stmt->execute([$file_id]);
            if ($existing = $stmt->fetch()) {
                $response = ["success" => true, "share_id" => $existing["id"]];
            } else {
                $shareId = bin2hex(random_bytes(8));
                $stmt = $pdo->prepare(
                    "INSERT INTO share_links (id, file_id) VALUES (?, ?)"
                );
                $stmt->execute([$shareId, $file_id]);
                $response = ["success" => true, "share_id" => $shareId];
            }
            break;

        // --- NÂNG CẤP ACTION: TẢI FILE (từ download.php) VỚI STREAMING ---
        case "download_file":
            // Tắt bộ đệm đầu ra của PHP và Gzip để kiểm soát hoàn toàn việc gửi dữ liệu
            if (ob_get_level()) {
                ob_end_clean();
            }

            $id = (int) ($_GET["id"] ?? 0);

            // Chỉ lấy metadata trước
            $meta_stmt = $pdo->prepare(
                "SELECT name, mime_type, size FROM file_system WHERE id = ? AND is_deleted = 0 AND type = 'file'"
            );
            $meta_stmt->execute([$id]);
            $file = $meta_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                http_response_code(404);
                die("File not found or inaccessible.");
            }

            // Gửi các header cần thiết cho trình duyệt
            header("Content-Description: File Transfer");
            if (isset($_GET["inline"]) && $_GET["inline"] === "true") {
                header(
                    'Content-Disposition: inline; filename="' .
                        basename($file["name"]) .
                        '"'
                );
            } else {
                header(
                    'Content-Disposition: attachment; filename="' .
                        basename($file["name"]) .
                        '"'
                );
            }
            header(
                "Content-Type: " .
                    ($file["mime_type"] ?: "application/octet-stream")
            );
            header("Content-Length: " . $file["size"]);
            header("Expires: 0");
            header("Cache-Control: must-revalidate");
            header("Pragma: public");

            // Đảm bảo tất cả header đã được gửi đi
            flush();

            // Bây giờ, chuẩn bị để stream nội dung BLOB
            $pdo->beginTransaction();
            $stream_stmt = $pdo->prepare(
                "SELECT content FROM file_system WHERE id = ?"
            );
            $stream_stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stream_stmt->execute();

            // Liên kết cột 'content' với một biến stream
            $stream_stmt->bindColumn("content", $stream, PDO::PARAM_LOB);
            $stream_stmt->fetch(PDO::FETCH_BOUND);

            if ($stream) {
                // Đọc và gửi stream theo từng đoạn 8KB
                while (!feof($stream)) {
                    echo fread($stream, 8192);
                    flush(); // Gửi ngay đoạn vừa đọc đến trình duyệt
                }
                fclose($stream);
            }

            $pdo->commit();
            exit(); // Kết thúc script

        // --- ACTION MỚI: TẢI ARCHIVE (từ create_archive.php) ---
        case "download_archive":
            $itemIds = array_map("intval", $_POST["ids"] ?? []);
            if (empty($itemIds)) {
                redirect_with_message(
                    BASE_URL . "index.php",
                    "No items selected for download.",
                    "danger"
                );
            }

            $zip = new ZipArchive();
            $zipFileName = "archive_" . time() . ".zip";
            $zipFilePath = sys_get_temp_dir() . "/" . $zipFileName;

            if (
                $zip->open(
                    $zipFilePath,
                    ZipArchive::CREATE | ZipArchive::OVERWRITE
                ) !== true
            ) {
                die("Could not open archive");
            }

            // Hàm đệ quy để thêm thư mục vào zip (cần định nghĩa lại trong scope này)
            function addFolderToZip($pdo, $folderId, $zip, $parentPath)
            {
                $stmt = $pdo->prepare(
                    "SELECT id, name, type FROM file_system WHERE parent_id = ? AND is_deleted = 0"
                );
                $stmt->execute([$folderId]);
                $items = $stmt->fetchAll();
                foreach ($items as $item) {
                    $localPath = $parentPath . $item["name"];
                    if ($item["type"] === "folder") {
                        $zip->addEmptyDir($localPath);
                        addFolderToZip(
                            $pdo,
                            $item["id"],
                            $zip,
                            $localPath . "/"
                        );
                    } else {
                        $fileStmt = $pdo->prepare(
                            "SELECT content FROM file_system WHERE id = ?"
                        );
                        $fileStmt->execute([$item["id"]]);
                        $fileContent = $fileStmt->fetchColumn();
                        if ($fileContent !== false) {
                            $zip->addFromString($localPath, $fileContent);
                        }
                    }
                }
            }

            $placeholders = rtrim(str_repeat("?,", count($itemIds)), ",");
            $stmt = $pdo->prepare(
                "SELECT id, name, type FROM file_system WHERE id IN ($placeholders) AND is_deleted = 0"
            );
            $stmt->execute($itemIds);
            $itemsToArchive = $stmt->fetchAll();

            foreach ($itemsToArchive as $item) {
                if ($item["type"] === "folder") {
                    $zip->addEmptyDir($item["name"]);
                    addFolderToZip(
                        $pdo,
                        $item["id"],
                        $zip,
                        $item["name"] . "/"
                    );
                } else {
                    $fileStmt = $pdo->prepare(
                        "SELECT content FROM file_system WHERE id = ?"
                    );
                    $fileStmt->execute([$item["id"]]);
                    $fileContent = $fileStmt->fetchColumn();
                    if ($fileContent !== false) {
                        $zip->addFromString($item["name"], $fileContent);
                    }
                }
            }

            $zip->close();

            header("Content-Type: application/zip");
            header(
                'Content-Disposition: attachment; filename="' .
                    $zipFileName .
                    '"'
            );
            header("Content-Length: " . filesize($zipFilePath));
            header("Pragma: no-cache");
            header("Expires: 0");

            ob_clean();
            flush();
            readfile($zipFilePath);

            unlink($zipFilePath);
            exit(); // Quan trọng
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Chỉ set http code và response nếu không phải là action download
    if (!$isDownloadAction) {
        http_response_code(400);
        $response = ["success" => false, "message" => $e->getMessage()];
    } else {
        // Với action download, chỉ cần die với thông báo lỗi
        die("Error: " . $e->getMessage());
    }
}

// Chỉ echo JSON nếu không phải action download
if (!$isDownloadAction) {
    echo json_encode($response);
}
