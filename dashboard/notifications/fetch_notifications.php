<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php

header('Content-Type: application/json');

$response = ['success' => false, 'notifications' => [], 'unread_count' => 0, 'message' => ''];

$user_student_id = $_SESSION['user_id'] ?? null;

if (!$user_student_id) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit();
}

try {
    // Fetch unread notifications
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = FALSE");
    $stmt_unread->execute([':user_id' => $user_student_id]);
    $response['unread_count'] = $stmt_unread->fetchColumn();

    // Fetch recent notifications (e.g., top 10, both read and unread)
    $stmt_notifications = $pdo->prepare("
        SELECT
            n.notification_id,
            n.type,
            n.message,
            n.link,
            n.is_read,
            n.created_at,
            s.full_name AS sender_name,
            s.profile_picture AS sender_profile_picture
        FROM
            notifications n
        LEFT JOIN
            users s ON n.sender_id = s.student_id
        WHERE
            n.user_id = :user_id
        ORDER BY
            n.created_at DESC
        LIMIT 10
    ");
    $stmt_notifications->execute([':user_id' => $user_student_id]);
    $response['notifications'] = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (PDOException $e) {
    error_log("Error fetching notifications for user " . $user_student_id . ": " . $e->getMessage());
    $response['message'] = 'Database error fetching notifications.';
}

echo json_encode($response);
exit();
?>
