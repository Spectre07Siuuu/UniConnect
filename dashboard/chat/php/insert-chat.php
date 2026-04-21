<?php
    require_once 'config.php';
    require_once '../../../auth/includes/auth_helpers.php';

    // Validate CSRF token
    if (!validateCsrfToken(getCsrfTokenFromRequest())) {
        exit(); // Silently reject in the chat AJAX context
    }

    $outgoing_student_id = chatCurrentUserId();
    $incoming_student_id = trim($_POST['incoming_id'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Basic validation: ensure both IDs are present and the message is not empty
    if (!$outgoing_student_id || empty($incoming_student_id) || empty($message)) {
        // In an AJAX context, a simple exit is appropriate if data is invalid
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmtInsertMessage = $pdo->prepare(
            'INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg) VALUES (?, ?, ?)'
        );
        $stmtInsertMessage->execute([$incoming_student_id, $outgoing_student_id, $message]);

        if ($outgoing_student_id !== $incoming_student_id) {
            $stmt_sender_name = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
            $stmt_sender_name->execute([$outgoing_student_id]);
            $sender_name = $stmt_sender_name->fetchColumn();

            $message_preview = substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '');

            $notification_message = $sender_name . " sent you a new message: \"" . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '') . "\"";
            // Correct link to directly open the chat with the sender
            $notification_link = 'dashboard/chat/chat.php?user_id=' . urlencode($outgoing_student_id);

            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
            $stmt_notify->execute([$incoming_student_id, $outgoing_student_id, 'new_pm', $notification_message, $notification_link]);
        }

        $pdo->commit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error inserting private message notification: " . $e->getMessage());
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("General error in insert-chat.php: " . $e->getMessage());
    }
?>
