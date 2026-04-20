<?php
require_once 'config.php';

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'status' => 'Offline now', 'message' => ''];

$target_student_id = trim($_GET['user_id'] ?? '');

if (empty($target_student_id)) {
    $response['message'] = 'User ID not provided.';
    echo json_encode($response);
    exit();
}

try {
    $user_data = chatFetchUser($pdo, $target_student_id);
    if ($user_data) {
        $response['success'] = true;
        $response['status'] = $user_data['status'];
    } else {
        $response['message'] = 'User not found or status not available.';
    }
} catch (Exception $e) {
    error_log("Error fetching user status for ID {$target_student_id}: " . $e->getMessage());
    $response['message'] = 'Database error fetching status.';
}

echo json_encode($response);
exit();
?>
