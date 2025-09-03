<?php
require_once 'config.php';
require_once 'auth_check.php';


set_time_limit(600);
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request'];

// Define a directory for temporary chunk storage
define('TEMP_UPLOAD_DIR', __DIR__ . '/temp_uploads');

// Ensure the temporary directory exists and is writable
if (!is_dir(TEMP_UPLOAD_DIR)) {
    if (!mkdir(TEMP_UPLOAD_DIR, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create temporary upload directory.']);
        exit;
    }
}
if (!is_writable(TEMP_UPLOAD_DIR)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Temporary upload directory is not writable.']);
    exit;
}


try {
    $action = $_POST['action'] ?? '';
    if (empty($action)) {
        throw new Exception('Action is required.');
    }

    if ($action === 'start') {
        $pdo->beginTransaction();

        $fileName = $_POST['fileName'] ?? 'uploading...';
        $fileSize = (int)($_POST['fileSize'] ?? 0);
        $parentId = (int)($_POST['parentId'] ?? ROOT_FOLDER_ID);
        $mimeType = $_POST['mimeType'] ?? 'application/octet-stream';

        // Create a new row in file_system to get a file_id
        $stmt = $pdo->prepare("INSERT INTO file_system (parent_id, name, type, mime_type, size, content) VALUES (?, ?, 'file', ?, ?, NULL)");
        $stmt->execute([$parentId, $fileName, $mimeType, $fileSize]);
        $fileId = $pdo->lastInsertId();

        // Create a temporary directory for this specific upload
        $uploadDir = TEMP_UPLOAD_DIR . '/' . $fileId;
        if (is_dir($uploadDir)) {
            // Clear old chunks if resuming
            array_map('unlink', glob("$uploadDir/*"));
        } else {
            mkdir($uploadDir, 0777, true);
        }
        
        $pdo->commit();
        $response = ['success' => true, 'fileId' => $fileId];

    } elseif ($action === 'upload') {
        $fileId = (int)($_POST['fileId'] ?? 0);
        $chunkIndex = (int)($_POST['chunkIndex'] ?? -1);
        $chunkFile = $_FILES['chunk']['tmp_name'] ?? null;
        $uploadDir = TEMP_UPLOAD_DIR . '/' . $fileId;

        if ($fileId <= 0 || $chunkIndex < 0 || !$chunkFile || !is_dir($uploadDir)) {
            throw new Exception('Invalid chunk data, file ID, or temporary directory not found.');
        }

        $chunkPath = $uploadDir . '/' . $chunkIndex;
        if (!move_uploaded_file($chunkFile, $chunkPath)) {
            throw new Exception("Failed to move chunk {$chunkIndex} to temporary storage.");
        }

        $response = ['success' => true, 'message' => "Chunk {$chunkIndex} stored."];

    } elseif ($action === 'complete') {
        $fileId = (int)($_POST['fileId'] ?? 0);
        $totalChunks = (int)($_POST['totalChunks'] ?? 0);
        $uploadDir = TEMP_UPLOAD_DIR . '/' . $fileId;

        if ($fileId <= 0 || $totalChunks <= 0 || !is_dir($uploadDir)) {
            throw new Exception('Invalid file ID or total chunks for completion.');
        }
        
        $uploadedCount = count(glob($uploadDir . '/*'));
        if ($uploadedCount < $totalChunks) {
            throw new Exception("Incomplete upload. Expected {$totalChunks}, found {$uploadedCount}.");
        }

        $pdo->beginTransaction();
        
        // Initialize the content column to an empty blob
        $init_stmt = $pdo->prepare("UPDATE file_system SET content = X'' WHERE id = ?");
        $init_stmt->execute([$fileId]);
        
        // Assemble file by reading chunks from filesystem and appending to DB BLOB
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $uploadDir . '/' . $i;
            $chunkContent = file_get_contents($chunkPath);
            if ($chunkContent === false) {
                throw new Exception("Could not read chunk {$i}.");
            }
            
            // Use SQLite's concatenation operator ||
            $update_stmt = $pdo->prepare("UPDATE file_system SET content = content || ? WHERE id = ?");
            $update_stmt->execute([$chunkContent, $fileId]);
            
            // Delete chunk file after processing
            unlink($chunkPath);
        }
        
        // Clean up: remove the temporary directory
        rmdir($uploadDir);

        $pdo->commit();
        $response = ['success' => true, 'message' => 'File assembled successfully.'];
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database Error in chunk_upload.php: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'A database error occurred. Please check server logs.'];
    http_response_code(500);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General Error in chunk_upload.php: " . $e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
    http_response_code(500);
}

echo json_encode($response);
?>