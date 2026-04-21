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

$reviewer_id = $_SESSION['user_id'];
$note_id = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);
$rating_category = trim($_POST['rating'] ?? '');
$review_text = trim($_POST['review_text'] ?? '');

if (!$note_id || empty($rating_category)) {
    $response['message'] = 'Note ID and rating are required.';
    echo json_encode($response);
    exit();
}

// Validate rating category
$allowed_ratings = ['very_helpful', 'average', 'not_helpful'];
if (!in_array($rating_category, $allowed_ratings)) {
    $response['message'] = 'Invalid rating category.';
    echo json_encode($response);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Check if the reviewer has already reviewed this note
    $stmt_check_review = $pdo->prepare("SELECT COUNT(*) FROM note_reviews WHERE note_id = :note_id AND reviewer_id = :reviewer_id");
    $stmt_check_review->execute([':note_id' => $note_id, ':reviewer_id' => $reviewer_id]);
    if ($stmt_check_review->fetchColumn() > 0) {
        $pdo->rollBack();
        $response['message'] = 'You have already reviewed this note.';
        echo json_encode($response);
        exit();
    }

    // 2. Get the note poster's user_id, current reputation, and note title
    $stmt_note_poster = $pdo->prepare("SELECT n.user_id, n.title, u.reputation_points FROM notes n JOIN users u ON n.user_id = u.student_id WHERE n.note_id = :note_id FOR UPDATE");
    $stmt_note_poster->execute([':note_id' => $note_id]);
    $note_poster_data = $stmt_note_poster->fetch(PDO::FETCH_ASSOC);

    if (!$note_poster_data) {
        $pdo->rollBack();
        $response['message'] = 'Note or note poster not found.';
        echo json_encode($response);
        exit();
    }
    $note_poster_id = $note_poster_data['user_id'];
    $note_title = $note_poster_data['title'];

    // Prevent self-review (user cannot review their own note)
    if ($reviewer_id === $note_poster_id) {
        $pdo->rollBack();
        $response['message'] = 'You cannot review your own note.';
        echo json_encode($response);
        exit();
    }

    // Determine points to award based on rating
    $points_to_award = 0;
    switch ($rating_category) {
        case 'very_helpful':
            $points_to_award = 3;
            break;
        case 'average':
            $points_to_award = 1;
            break;
        case 'not_helpful':
            $points_to_award = 0;
            break;
    }

    // 3. Insert the review
    $stmt_insert_review = $pdo->prepare("INSERT INTO note_reviews (note_id, reviewer_id, rating_category, review_text) VALUES (?, ?, ?, ?)");
    $stmt_insert_review->execute([$note_id, $reviewer_id, $rating_category, $review_text]);

    // 4. Update the note poster's reputation points
    if ($points_to_award > 0) {
        $stmt_update_reputation = $pdo->prepare("UPDATE users SET reputation_points = reputation_points + ? WHERE student_id = ?");
        $stmt_update_reputation->execute([$points_to_award, $note_poster_id]);

        // Create notification for reputation gained
        $stmt_reviewer_name = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
        $stmt_reviewer_name->execute([$reviewer_id]);
        $reviewer_name = $stmt_reviewer_name->fetchColumn();

        $notification_message_reputation = "You gained " . $points_to_award . " reputation points! Your note \"" . $note_title . "\" was reviewed by " . $reviewer_name . ".";
        $notification_link_reputation = 'dashboard/profile.php'; // Link to their profile (where reputation is visible)

        $stmt_notify_reputation = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
        $stmt_notify_reputation->execute([$note_poster_id, $reviewer_id, 'reputation_gained', $notification_message_reputation, $notification_link_reputation]);
    }

    // Create notification for the note poster about the review itself
    $stmt_reviewer_name_for_review = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
    $stmt_reviewer_name_for_review->execute([$reviewer_id]);
    $reviewer_name_for_review = $stmt_reviewer_name_for_review->fetchColumn();

    $review_type_message = str_replace('_', ' ', $rating_category); // e.g., "very helpful", "not helpful"
    $notification_message_review = $reviewer_name_for_review . " reviewed your note \"" . $note_title . "\" as " . $review_type_message . ".";
    $notification_link_review = 'dashboard/notes.php'; // Link to the notes page

    $stmt_notify_review = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt_notify_review->execute([$note_poster_id, $reviewer_id, 'note_reviewed', $notification_message_review, $notification_link_review]);


    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Review submitted successfully! ' . $points_to_award . ' reputation points awarded to the note poster.';

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Note Review DB Error: " . $e->getMessage());
    $response['message'] = 'Database error during review submission.';
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Note Review System Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
exit();
