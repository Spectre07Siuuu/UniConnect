<?php
// This script processes liking/unliking a post.
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php
require_once '../../auth/includes/auth_helpers.php';

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken(getCsrfTokenFromRequest())) {
        $response['message'] = 'Invalid or missing security token.';
        echo json_encode($response);
        exit();
    }

    $user_student_id = $_SESSION['user_id'] ?? null; // The person who clicked like/unlike
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? ''; // 'like' or 'unlike'

    if (!$user_student_id) {
        $response['message'] = 'Authentication required to like/unlike.';
        echo json_encode($response);
        exit();
    }
    if (!$post_id) {
        $response['message'] = 'Invalid post ID.';
        echo json_encode($response);
        exit();
    }
    if (!in_array($action, ['like', 'unlike'])) {
        $response['message'] = 'Invalid action.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Get post details and author's ID
        $stmt_post_info = $pdo->prepare("SELECT user_id, post_text FROM posts WHERE post_id = ?");
        $stmt_post_info->execute([$post_id]);
        $post_info = $stmt_post_info->fetch(PDO::FETCH_ASSOC);

        if (!$post_info) {
            $pdo->rollBack();
            $response['message'] = 'Post not found.';
            echo json_encode($response);
            exit();
        }

        $post_author_id = $post_info['user_id'];
        $post_preview = substr($post_info['post_text'], 0, 50) . (strlen($post_info['post_text']) > 50 ? '...' : '');

        // Prevent self-notification
        if ($user_student_id === $post_author_id) {
            // User liked their own post, still process like/unlike but no notification
            // Proceed to the like/unlike logic below
        }

        if ($action === 'like') {
            // Check if already liked to prevent duplicate entries
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_student_id]);
            if ($stmt->fetchColumn() > 0) {
                $response['success'] = true; // Already liked, treat as success (no change needed)
                $response['message'] = 'Post already liked.';
            } else {
                // Insert new like
                $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
                $stmt->execute([$post_id, $user_student_id]);
                $response['success'] = true;
                $response['message'] = 'Post liked successfully!';

                // Insert notification ONLY if not self-like
                if ($user_student_id !== $post_author_id) {
                    // Get the name of the user who liked the post for the notification message
                    $stmt_liker_name = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
                    $stmt_liker_name->execute([$user_student_id]);
                    $liker_name = $stmt_liker_name->fetchColumn();

                    $notification_message = $liker_name . " liked your post: \"" . substr(strip_tags($post_info['post_text']), 0, 50) . (strlen($post_info['post_text']) > 50 ? '...' : '') . "\"";
                    // Correct link to directly scroll to the post on index.php
                    $notification_link = 'dashboard/index.php#post-' . $post_id;

                    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
                    $stmt_notify->execute([$post_author_id, $user_student_id, 'like_post', $notification_message, $notification_link]);
                }
            }
        } elseif ($action === 'unlike') {
            // Delete like
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_student_id]);
            $response['success'] = true;
            $response['message'] = 'Post unliked successfully!';
            // No notification needed for unliking
        }

        // Fetch the updated like count for the post
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $response['likes_count'] = $stmt->fetchColumn();

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Like/Unlike DB error for user {$user_student_id}, post {$post_id}: " . $e->getMessage());
        $response['message'] = 'Database error during like/unlike.';
    }

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
