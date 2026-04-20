<?php
session_start();
// This script serves the actual file for download.
// It assumes check_auth.php and the coin deduction logic (from download_note.php)
// have already been handled.

if (!isset($_GET['file'])) {
    die("File not specified.");
}

$file_path_db = $_GET['file']; // Path as stored in the database (e.g., 'uploads/notes/filename.pdf')

// Construct the full absolute path to the file
// Path from Admin-Dashboard/notes/ to uniconnect/uploads/notes/
$full_file_path = __DIR__ . '/../../' . $file_path_db;

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
