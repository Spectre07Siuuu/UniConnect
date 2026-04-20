<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php

header('Content-Type: text/html'); // Respond with HTML

$output_html = '';
$user_student_id = $_SESSION['user_id'] ?? null; // Current logged-in user's student_id

// Get the post_id from GET parameters
$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);

if (!$post_id) {
    echo '<p style="color: red; text-align: center;">Invalid post ID provided.</p>';
    exit();
}

try {
    // Fetch comments for the given post_id, joining with users to get commenter's details
    $stmt_comments = $pdo->prepare("
        SELECT
            C.comment_id,
            C.comment_text,
            C.created_at,
            U.full_name,
            U.profile_picture,
            U.student_id -- Commenter's student_id
        FROM comments AS C
        JOIN users AS U ON C.user_id = U.student_id
        WHERE C.post_id = :post_id
        ORDER BY C.created_at ASC
    ");
    $stmt_comments->execute([':post_id' => $post_id]);
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($comments)) {
        foreach ($comments as $comment) {
            // User avatar path for comment
            $commenter_avatar_path = ($comment['profile_picture'] ? '../' . $comment['profile_picture'] : '../images/uniconnect.png');
            if (empty($comment['profile_picture']) || $comment['profile_picture'] === 'images/uniconnect.png') {
                 $commenter_avatar_path = '../images/uniconnect.png'; // Default provided by the system
            }

            // Format time for display
            $time_ago = time_ago_string($comment['created_at']);

            $output_html .= '
                <div class="comment-item">
                    <img src="' . htmlspecialchars($commenter_avatar_path) . '" alt="Commenter Avatar" class="comment-avatar" onerror="this.src=\'../images/uniconnect.png\';" style="object-fit: cover;"/>
                    <div class="comment-content-wrapper">
                        <div class="comment-meta">
                            <span class="comment-author">' . htmlspecialchars($comment['full_name']) . '</span>
                            <span class="comment-timestamp">' . $time_ago . '</span>
                        </div>
                        <p class="comment-text">' . nl2br(htmlspecialchars($comment['comment_text'])) . '</p>
                    </div>
                </div>';
        }
    } else {
        $output_html = '<div class="no-comments-message" style="text-align: center; color: var(--text-secondary); margin-top: 10px; font-size: 1.4rem;">No comments yet.</div>';
    }

} catch (PDOException $e) {
    error_log("Error fetching comments for post {$post_id}: " . $e->getMessage());
    $output_html = '<p class="error-message" style="color: red; text-align: center;">Error loading comments. Please try again later. (DB Error)</p>';
}

echo $output_html;

// Helper function to convert timestamp to "time ago" string (duplicated for self-containment, can be shared if preferred)
function time_ago_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

exit();
