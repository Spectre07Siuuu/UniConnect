<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php
require_once '../../auth/includes/auth_helpers.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

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

$user_id = $_SESSION['user_id'];
$note_id = filter_input(INPUT_POST, 'note_id', FILTER_VALIDATE_INT);
$note_poster_id = trim($_POST['note_poster_id'] ?? ''); // The ID of the user who posted the note

if (!$note_id || empty($note_poster_id)) {
    $response['message'] = 'Invalid note or poster ID.';
    echo json_encode($response);
    exit();
}

// Prevent self-download coin deduction
if ($user_id === $note_poster_id) {
    $response['success'] = true;
    $response['message'] = 'You are the note poster. No coin deduction.';
    // Update download count only for own notes if they download
    try {
        $stmt_update_count = $pdo->prepare("UPDATE notes SET download_count = download_count + 1 WHERE note_id = ?");
        $stmt_update_count->execute([$note_id]);
    } catch (PDOException $e) {
        error_log("Error updating download count for own note: " . $e->getMessage());
    }
    // Fetch current coins to send back, no change needed
    $stmt_coins = $pdo->prepare("SELECT coin_balance FROM users WHERE student_id = ?");
    $stmt_coins->execute([$user_id]);
    $response['new_coin_balance'] = $stmt_coins->fetchColumn();
    echo json_encode($response);
    exit();
}


try {
    $pdo->beginTransaction();

    // Check user's current coin balance
    $stmt_coins = $pdo->prepare("SELECT coin_balance FROM users WHERE student_id = ? FOR UPDATE"); // FOR UPDATE to lock row
    $stmt_coins->execute([$user_id]);
    $current_coins = $stmt_coins->fetchColumn();

    $deduction_amount = 2;
    if ($current_coins < $deduction_amount) {
        $pdo->rollBack();
        $response['message'] = 'Insufficient coins to download this note. You need ' . $deduction_amount . ' coins.';
        echo json_encode($response);
        exit();
    }

    // Deduct coins from downloader
    $stmt_deduct_coins = $pdo->prepare("UPDATE users SET coin_balance = coin_balance - ? WHERE student_id = ?");
    $stmt_deduct_coins->execute([$deduction_amount, $user_id]);

    // Increase download count for the note
    $stmt_update_count = $pdo->prepare("UPDATE notes SET download_count = download_count + 1 WHERE note_id = ?");
    $stmt_update_count->execute([$note_id]);

    // Get note title for notification message
    $stmt_note_title = $pdo->prepare("SELECT title FROM notes WHERE note_id = ?");
    $stmt_note_title->execute([$note_id]);
    $note_title = $stmt_note_title->fetchColumn();

    // Insert 'coins_lost' notification for the downloader
    $notification_message_lost_coins = "You spent " . $deduction_amount . " coins to download the note: \"" . $note_title . "\".";
    $notification_link_lost_coins = 'dashboard/notes.php'; // Link to notes page
    $stmt_notify_lost = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt_notify_lost->execute([$user_id, $user_id, 'coins_lost', $notification_message_lost_coins, $notification_link_lost_coins]);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Note downloaded successfully! ' . $deduction_amount . ' coins deducted.';
    // Fetch and return the new coin balance
    $stmt_new_coins = $pdo->prepare("SELECT coin_balance FROM users WHERE student_id = ?");
    $stmt_new_coins->execute([$user_id]);
    $response['new_coin_balance'] = $stmt_new_coins->fetchColumn();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Download Note DB Error for user {$user_id}, note {$note_id}: " . $e->getMessage());
    $response['message'] = 'Database error during download process.';
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Download Note System Error for user {$user_id}, note {$note_id}: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
exit();
