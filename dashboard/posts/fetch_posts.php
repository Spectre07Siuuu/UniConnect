<?php
// This script fetches and returns a list of recent posts.
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php

header('Content-Type: text/html'); // Respond with HTML

$output_html = '';
$user_student_id = $_SESSION['user_id'] ?? null; // Current logged-in user's student_id

try {
    // Fetch recent posts, joining with users table to get sender's details
    // Also LEFT JOIN with post_likes table to count likes and check if current user liked it.
    // ADDED: LEFT JOIN with comments to get comment count
    $stmt_posts = $pdo->prepare("
        SELECT
            P.post_id,
            P.post_text,
            P.post_image_url,
            P.category,
            P.created_at,
            U.full_name,
            U.profile_picture,
            U.student_id, -- Author's student_id
            COUNT(DISTINCT PL.like_id) AS likes_count, -- Count total likes for this post
            MAX(CASE WHEN PL.user_id = :current_user_id THEN 1 ELSE 0 END) AS is_liked_by_current_user, -- Check if current user liked it
            COUNT(DISTINCT C.comment_id) AS comments_count -- Count total comments for this post
        FROM posts AS P
        JOIN users AS U ON P.user_id = U.student_id
        LEFT JOIN post_likes AS PL ON P.post_id = PL.post_id -- LEFT JOIN to get all posts, even those with no likes
        LEFT JOIN comments AS C ON P.post_id = C.post_id -- LEFT JOIN to get all posts, even those with no comments
        GROUP BY P.post_id, P.post_text, P.post_image_url, P.category, P.created_at, U.full_name, U.profile_picture, U.student_id
        ORDER BY P.created_at DESC
        LIMIT 10 -- Fetch a reasonable number of recent posts
    ");
    // Execute the query, passing the current user's student_id for the like check
    $stmt_posts->execute([':current_user_id' => $user_student_id]);
    $posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($posts)) {
        foreach ($posts as $post) {
            // Determine category tag display
            // Replace hyphens for display, then capitalize first letter
            $category_display = htmlspecialchars(ucfirst(str_replace('-', ' & ', $post['category'])));
            
            // Format time for display (e.g., "2 hours ago")
            $time_ago = time_ago_string($post['created_at']);

            // Image path for user avatar: profile_picture is relative to project root, needs '../' from Admin-Dashboard/posts/
            $user_avatar_path = ($post['profile_picture'] ? '../' . $post['profile_picture'] : '../images/uniconnect.png');
            // Fallback for user avatar if needed (though profile.php sets default)
            if (empty($post['profile_picture']) || $post['profile_picture'] === 'images/uniconnect.png') {
                 $user_avatar_path = '../images/uniconnect.png'; // Default provided by the system
            }

            // Image tag for post image (if available)
            $post_image_tag = '';
            if (!empty($post['post_image_url'])) {
                // post_image_url is relative to root, needs '../' from Admin-Dashboard/posts/
                $post_image_tag = '<img src="../' . htmlspecialchars($post['post_image_url']) . '" alt="Post Image" class="post-image" onerror="this.src=\'../images/uniconnect.png\';" />';
            }

            // Determine initial state for the like button based on is_liked_by_current_user
            $like_button_class = ($post['is_liked_by_current_user'] ? 'liked' : '');
            $like_button_icon = ($post['is_liked_by_current_user'] ? 'mdi-thumb-up' : 'mdi mdi-thumb-up-outline');
            $like_button_text = ($post['is_liked_by_current_user'] ? 'Liked' : 'Like');

            $output_html .= '
                <div class="post" data-post-id="' . htmlspecialchars($post['post_id']) . '">
                    <div class="post-header">
                        <div class="user-avatar">
                            <img src="' . $user_avatar_path . '" alt="User Avatar" style="object-fit: cover;"/>
                        </div>
                        <div class="post-meta">
                            <div class="meta-top">
                                <h4 class="user-name">' . htmlspecialchars($post['full_name']) . '</h4>
                                <span style="flex-grow: 1"></span>
                                <span class="category-tag">' . $category_display . '</span>
                            </div>
                            <span class="timestamp">' . $time_ago . '</span>
                        </div>
                    </div>
                    <div class="post-content">
                        <p>' . nl2br(htmlspecialchars($post['post_text'])) . '</p>
                        ' . $post_image_tag . '
                    </div>
                    <div class="post-divider"></div>
                    <div class="post-actions">
                        <button class="action-btn like-btn ' . $like_button_class . '">
                            <i class="' . $like_button_icon . '"></i> ' . $like_button_text . '
                            <span class="likes-count">' . htmlspecialchars($post['likes_count']) . '</span>
                        </button>
                        <button class="action-btn comment-btn">
                            <i class="mdi mdi-comment-outline"></i> Comment
                            <span class="comments-count">' . htmlspecialchars($post['comments_count']) . '</span>
                        </button>
                    </div>

                    <!-- Comment Section (Initially Hidden) -->
                    <div class="comment-section" style="display: none;">
                        <div class="comment-input-area">
                            <img src="' . htmlspecialchars($_SESSION['profile_picture'] ? '../' . $_SESSION['profile_picture'] : '../images/uniconnect.png') . '" alt="Your Avatar" class="comment-avatar" onerror="this.src=\'../images/uniconnect.png\';" style="object-fit: cover;"/>
                            <textarea class="comment-textarea" placeholder="Write a comment..." rows="1"></textarea>
                            <button class="post-comment-btn"><i class="mdi mdi-send"></i></button>
                        </div>
                        <div class="comments-list">
                            <!-- Comments will be loaded here via AJAX -->
                            <div class="no-comments-message" style="text-align: center; color: var(--text-secondary); margin-top: 10px; font-size: 1.4rem;">No comments yet.</div>
                        </div>
                    </div>
                </div>';
        }
    } else {
        // Display placeholder if no posts found
        $output_html = '<p class="no-recent-posts-message" style="text-align: center; color: var(--text-secondary); margin-top: 20px; font-size: 1.6rem;">No recent posts available. Be the first to post!</p>';
    }

} catch (PDOException $e) {
    error_log("Error fetching recent posts: " . $e->getMessage());
    $output_html = '<p class="error-message">Error loading recent posts. Please try again later. (DB Error)</p>';
}

echo $output_html;

// Helper function to convert timestamp to "time ago" string
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
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); // Corrected line
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

exit();
