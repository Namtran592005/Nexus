<?php
function formatBytes($bytes, $precision = 2) {
    if ($bytes === null || $bytes == 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getFileIcon($fileName, $isFolder = false) {
    if ($isFolder) {
        return ['icon' => 'fa-folder', 'color' => '#5aa4f0'];
    }
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $color = '#8a8a8e';
    $icon = 'fa-file';
    switch ($extension) {
        case 'pdf': $icon = 'fa-file-pdf'; $color = '#e62e2e'; break;
        case 'doc': case 'docx': $icon = 'fa-file-word'; $color = '#2a5699'; break;
        case 'xls': case 'xlsx': $icon = 'fa-file-excel'; $color = '#217346'; break;
        case 'ppt': case 'pptx': $icon = 'fa-file-powerpoint'; $color = '#d24726'; break;
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': case 'heic': $icon = 'fa-file-image'; $color = '#5cb85c'; break;
        case 'zip': case 'rar': case '7z': $icon = 'fa-file-archive'; $color = '#f0ad4e'; break;
        case 'txt': case 'log': case 'md': $icon = 'fa-file-alt'; $color = '#a0a0a5'; break;
        case 'mp3': case 'wav': case 'aac': $icon = 'fa-file-audio'; $color = '#c06c84'; break;
        case 'mp4': case 'mov': case 'avi': $icon = 'fa-file-video'; $color = '#6c5b7b'; break;
        case 'html': case 'css': case 'js': case 'php': case 'py': case 'java': case 'c': case 'cpp': case 'json': case 'xml': $icon = 'fa-file-code'; $color = '#8d6e63'; break;
        default: $icon = 'fa-file'; $color = '#8a8a8e'; break;
    }
    return ['icon' => $icon, 'color' => $color];
}

function isImage($mimeType) { return str_starts_with($mimeType, 'image/'); }
function isVideo($mimeType) { return str_starts_with($mimeType, 'video/'); }
function isAudio($mimeType) { return str_starts_with($mimeType, 'audio/'); }
function isPdf($mimeType) { return $mimeType === 'application/pdf'; }
function isTextOrCode($mimeType) { return str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript']); }
function guessCodeLanguage($fileName) {
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    switch (strtolower($extension)) {
        case 'php': return 'php'; case 'js': return 'javascript'; case 'css': return 'css';
        case 'html': case 'htm': return 'xml'; case 'json': return 'json'; case 'xml': return 'xml';
        case 'py': return 'python'; case 'java': return 'java'; case 'c': return 'c'; case 'cpp': return 'cpp';
        case 'md': return 'markdown'; default: return 'plaintext';
    }
}

function getFileTypeCategory($mimeType) {
    if (str_starts_with($mimeType, 'image/')) return 'Images';
    if (str_starts_with($mimeType, 'video/')) return 'Videos';
    if (str_starts_with($mimeType, 'audio/')) return 'Audio';
    if (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])) return 'Documents';
    if (in_array($mimeType, ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/gzip'])) return 'Archives';
    if (str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml', 'application/javascript'])) return 'Code & Text';
    return 'Other';
}

function getItemIdByPath($pdo, $path) {
    $path = trim($path, '/');
    if (empty($path)) {
        return ROOT_FOLDER_ID;
    }
    $parts = explode('/', $path);
    $currentParentId = ROOT_FOLDER_ID;
    $itemId = null;
    foreach ($parts as $part) {
        $stmt = $pdo->prepare("SELECT id FROM file_system WHERE name = ? AND parent_id = ? AND is_deleted = 0");
        $stmt->execute([$part, $currentParentId]);
        $result = $stmt->fetch();
        if ($result) {
            $itemId = $result['id'];
            $currentParentId = $itemId;
        } else {
            return null;
        }
    }
    return $itemId;
}

function getPathByItemId($pdo, $id) {
    if ($id == ROOT_FOLDER_ID || $id == null) {
        return '';
    }
    $pathParts = [];
    $currentId = $id;
    while ($currentId != null && $currentId != ROOT_FOLDER_ID) {
        $stmt = $pdo->prepare("SELECT name, parent_id FROM file_system WHERE id = ?");
        $stmt->execute([$currentId]);
        $item = $stmt->fetch();
        if ($item) {
            array_unshift($pathParts, $item['name']);
            $currentId = $item['parent_id'];
        } else {
            break;
        }
    }
    return implode('/', $pathParts);
}

function deleteFolderRecursiveDb($pdo, $folderId) {
    $stmt = $pdo->prepare("SELECT id, type FROM file_system WHERE parent_id = ?");
    $stmt->execute([$folderId]);
    $children = $stmt->fetchAll();
    foreach ($children as $child) {
        if ($child['type'] === 'folder') {
            deleteFolderRecursiveDb($pdo, $child['id']);
        }
    }
    $deleteStmt = $pdo->prepare("DELETE FROM file_system WHERE id = ?");
    $deleteStmt->execute([$folderId]);
}
?>