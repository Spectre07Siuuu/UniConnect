<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php
require_once '../../auth/includes/auth_helpers.php';

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken(getCsrfTokenFromRequest())) {
        $response['message'] = 'Invalid or missing security token.';
        echo json_encode($response);
        exit();
    }

    // Get user's student_id from session (the person who posted the comment)
    $user_student_id = $_SESSION['user_id'] ?? null;
    if (!$user_student_id) {
        $response['message'] = 'Authentication required.';
        echo json_encode($response);
        exit();
    }

    // Validate input
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $comment_text = trim($_POST['comment_text'] ?? '');

    if (!$post_id) {
        $response['message'] = 'Invalid post ID.';
        echo json_encode($response);
        exit();
    }
    if (empty($comment_text)) {
        $response['message'] = 'Comment content cannot be empty.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction(); // Start transaction

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

        // 2. Insert comment into database
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        if ($stmt->execute([$post_id, $user_student_id, $comment_text])) {
            $response['success'] = true;
            $response['message'] = 'Comment posted successfully!';

            // Fetch the updated comment count for the post
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $stmt_count->execute([$post_id]);
            $response['comments_count'] = $stmt_count->fetchColumn();

            // 3. Insert notification for the post author, but only if not self-comment
            if ($user_student_id !== $post_author_id) {
                // Get the name of the user who commented
                $stmt_commenter_name = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
                $stmt_commenter_name->execute([$user_student_id]);
                $commenter_name = $stmt_commenter_name->fetchColumn();

                $notification_message = $commenter_name . " commented on your post: \"" . substr(strip_tags($post_info['post_text']), 0, 50) . (strlen($post_info['post_text']) > 50 ? '...' : '') . "\"";
                // Correct link to directly scroll to the post and potentially open comments
                // We'll use a fragment identifier for JS to pick up later
                $notification_link = 'dashboard/index.php#post-' . $post_id . '-comments';

                $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
                $stmt_notify->execute([$post_author_id, $user_student_id, 'comment_post', $notification_message, $notification_link]);
            }

        } else {
            $pdo->rollBack(); // Rollback if comment insertion failed
            $response['message'] = 'Failed to post comment in the database.';
        }

        $pdo->commit(); // Commit transaction

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on any PDO error
        error_log("Database error posting comment for user " . $user_student_id . ", post " . $post_id . ": " . $e->getMessage());
        $response['message'] = 'Database error posting comment.';
    }

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
