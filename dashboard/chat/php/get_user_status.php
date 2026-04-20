<?php
session_start();
include_once "config.php"; // Uses the updated chat config.php

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'status' => 'Offline now', 'message' => ''];

$target_student_id = mysqli_real_escape_string($conn, $_GET['user_id'] ?? '');

if (empty($target_student_id)) {
    $response['message'] = 'User ID not provided.';
    echo json_encode($response);
    exit();
}

try {
    $sql = "SELECT status FROM users WHERE student_id = '{$target_student_id}'";
    $query = mysqli_query($conn, $sql);

    if ($query && mysqli_num_rows($query) > 0) {
        $user_data = mysqli_fetch_assoc($query);
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
