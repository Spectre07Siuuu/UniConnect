<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$user_student_id = $_SESSION['user_id'] ?? null;
$notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
$mark_all = filter_input(INPUT_POST, 'mark_all', FILTER_VALIDATE_BOOLEAN); // New: for marking all as read

if (!$user_student_id) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit();
}

try {
    if ($mark_all) {
        // Mark all notifications for the user as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_student_id]);
        $response['success'] = true;
        $response['message'] = 'All notifications marked as read.';
    } elseif ($notification_id) {
        // Mark a specific notification as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id = :notification_id AND user_id = :user_id");
        $stmt->execute([':notification_id' => $notification_id, ':user_id' => $user_student_id]);
        $response['success'] = true;
        $response['message'] = 'Notification marked as read.';
    } else {
        $response['message'] = 'No notification ID or mark_all flag provided.';
    }
} catch (PDOException $e) {
    error_log("Error marking notification as read for user " . $user_student_id . ": " . $e->getMessage());
    $response['message'] = 'Database error marking notification as read.';
}

echo json_encode($response);
exit();
?>
