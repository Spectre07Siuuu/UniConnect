<?php
session_start();
// This script serves the actual file for download.
// It assumes check_auth.php and the coin deduction logic (from download_note.php)
// have already been handled.

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Authentication required.");
}

if (!isset($_GET['file'])) {
    die("File not specified.");
}

// Allowed base directory for note uploads (absolute, no trailing slash)
$allowed_base = realpath(__DIR__ . '/../../uploads/notes');
if ($allowed_base === false) {
    die("Server configuration error.");
}

// Strip any directory-separator prefix to prevent traversal before joining
$file_path_db = ltrim(basename(dirname($_GET['file'])) . '/' . basename($_GET['file']), '/');
// Resolve the full path and verify it is inside the allowed base
$full_file_path = realpath($allowed_base . '/' . basename($_GET['file']));

if ($full_file_path === false || strncmp($full_file_path, $allowed_base . DIRECTORY_SEPARATOR, strlen($allowed_base) + 1) !== 0) {
    http_response_code(403);
    die("Access denied.");
}

if (!file_exists($full_file_path)) {
    die("File not found.");
}

// Get file info
$file_name = basename($full_file_path);
$file_size = filesize($full_file_path);
$mime_type = mime_content_type($full_file_path); // Requires php_fileinfo extension enabled

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . ($mime_type ?: 'application/octet-stream')); // Fallback if mime_content_type fails
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// Read the file and output it
readfile($full_file_path);
exit();
?>
