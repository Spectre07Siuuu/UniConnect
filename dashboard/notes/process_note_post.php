<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php
require_once '../../auth/includes/auth_helpers.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Validate CSRF token
if (!validateCsrfToken(getCsrfTokenFromRequest())) {
    $response['message'] = 'Invalid or missing security token.';
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id']; // The ID of the user uploading the note
$title = trim($_POST['title'] ?? basename($_FILES['note_file']['name'] ?? '', '.' . pathinfo($_FILES['note_file']['name'] ?? '', PATHINFO_EXTENSION)));
$description = trim($_POST['description'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$exam_type = trim($_POST['exam_type'] ?? '');
$file_path = null;
$thumbnail_path = null; // Assuming no thumbnail generation for now, but column exists

// Basic validation
if (empty($subject) || empty($exam_type) || !isset($_FILES['note_file']) || $_FILES['note_file']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Please fill all required fields and upload a file.';
    echo json_encode($response);
    exit();
}

// Validate exam_type against allowed ENUM values (from notes.php list)
$allowed_exam_types = ['midterm', 'final', 'quiz', 'assignment', 'other'];
if (!in_array(strtolower($exam_type), $allowed_exam_types)) {
    $response['message'] = 'Invalid exam type selected.';
    echo json_encode($response);
    exit();
}

// Handle file upload
$uploadBaseDir = 'uploads/notes/'; // Relative to project root
$uploadFullPath = __DIR__ . '/../../' . $uploadBaseDir; // Absolute path on server

if (!is_dir($uploadFullPath)) {
    if (!mkdir($uploadFullPath, 0777, true)) {
        $response['message'] = 'Failed to create upload directory.';
        echo json_encode($response);
        exit();
    }
}
if (!is_writable($uploadFullPath)) {
    $response['message'] = 'Upload directory is not writable.';
    echo json_encode($response);
    exit();
}

$fileTmpPath = $_FILES['note_file']['tmp_name'];
$fileName = $_FILES['note_file']['name'];
$fileSize = $_FILES['note_file']['size'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileSize > 25 * 1024 * 1024) { // Max 25MB for notes
    $response['message'] = 'File size must be less than 25MB.';
    echo json_encode($response);
    exit();
}

$allowedFileExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif']; // Example allowed types
if (!in_array($fileExtension, $allowedFileExtensions)) {
    $response['message'] = 'Invalid file type. Only PDF, DOCX, PPTX, TXT, ZIP, RAR, and common image formats are allowed.';
    echo json_encode($response);
    exit();
}

$newFileName = uniqid('note_') . '_' . md5($fileName . microtime()) . '.' . $fileExtension;
$targetFilePath = $uploadFullPath . $newFileName;

if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
    $file_path = $uploadBaseDir . $newFileName; // Path to store in DB
} else {
    $response['message'] = 'Error saving file on server.';
    echo json_encode($response);
    exit();
}

try {
    $pdo->beginTransaction();

    // Insert note into database
    $stmt_insert_note = $pdo->prepare("INSERT INTO notes (user_id, title, description, subject, exam_type, file_path, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt_insert_note->execute([$user_id, $title, $description, $subject, $exam_type, $file_path, $thumbnail_path])) {
        // Award 5 coins to the user who posted the note
        $stmt_award_coins = $pdo->prepare("UPDATE users SET coin_balance = coin_balance + 5 WHERE student_id = ?");
        $stmt_award_coins->execute([$user_id]);

        // Get the ID of the newly inserted note (useful for notification link if linking to specific note)
        $new_note_id = $pdo->lastInsertId();

        // Notify users who have this subject in their profile
        $stmt_interested_users = $pdo->prepare("
            SELECT DISTINCT user_id
            FROM user_courses_profile
            WHERE course_name = :subject_name
            AND user_id != :uploader_id
        ");
        $stmt_interested_users->execute([':subject_name' => $subject, ':uploader_id' => $user_id]);
        $interested_users = $stmt_interested_users->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($interested_users)) {
            // Get the name of the user who uploaded the note
            $stmt_uploader_name = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
            $stmt_uploader_name->execute([$user_id]);
            $uploader_name = $stmt_uploader_name->fetchColumn();

            $notification_message = $uploader_name . " uploaded a new note for " . $subject . ": \"" . $title . "\"";
            // Correct link to the notes page
            $notification_link = 'dashboard/notes.php';

            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
            foreach ($interested_users as $receiver_id) {
                $stmt_notify->execute([$receiver_id, $user_id, 'new_note_upload', $notification_message, $notification_link]);
            }
        }

        // Notify the uploader themselves if coins were awarded (Coin/Reputation Change)
        $uploader_notification_message = "You gained 5 coins for uploading note: \"" . $title . "\"";
        $uploader_notification_link = 'dashboard/notes.php'; // Link to their notes

        $stmt_notify_uploader = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
        $stmt_notify_uploader->execute([$user_id, $user_id, 'coins_gained', $uploader_notification_message, $uploader_notification_link]);


        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Note uploaded successfully! You gained 5 coins.';
        // Re-fetch updated coin balance for frontend display
        $stmt_coins = $pdo->prepare("SELECT coin_balance FROM users WHERE student_id = ?");
        $stmt_coins->execute([$user_id]);
        $response['new_coin_balance'] = $stmt_coins->fetchColumn();

    } else {
        $pdo->rollBack();
        $response['message'] = 'Failed to save note in database.';
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Note Post DB Error: " . $e->getMessage());
    $response['message'] = 'Database error during note upload.';
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Note Post System Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
exit();
