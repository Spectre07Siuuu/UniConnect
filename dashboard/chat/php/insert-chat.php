<?php
    session_start();
    include_once "config.php"; // Uses the updated chat config.php
    require_once '../../../config/db_connect.php'; // Path to root db_connect.php (for PDO notifications)

    // Get the logged-in user's student_id from the session (outgoing message sender)
    $outgoing_student_id = $_SESSION['user_id'] ?? null; // $_SESSION['user_id'] now stores student_id

    // Get the incoming chat partner's student_id from the AJAX POST request
    $incoming_student_id = mysqli_real_escape_string($conn, $_POST['incoming_id'] ?? '');
    // Get the message content from the AJAX POST request
    $message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');

    // Basic validation: ensure both IDs are present and the message is not empty
    if (!$outgoing_student_id || empty($incoming_student_id) || empty($message)) {
        // In an AJAX context, a simple exit is appropriate if data is invalid
        exit();
    }

    try {
        // Start a PDO transaction as we're involving PDO for notifications
        $pdo->beginTransaction();

        // Use mysqli for the chat message insertion (as per your existing chat system)
        $sql_chat_insert = "INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg)
                            VALUES ('{$incoming_student_id}', '{$outgoing_student_id}', '{$message}')";
        $result_chat_insert = mysqli_query($conn, $sql_chat_insert);

        if ($result_chat_insert === false) {
            // Log the specific MySQLi error if the query fails
            error_log("MySQLi Query Error in insert-chat.php (chat message): " . mysqli_error($conn) . " SQL: " . $sql_chat_insert);
            $pdo->rollBack(); // Rollback PDO transaction as well
            // You might output a specific error message to the client here if desired
            // echo "Error: Failed to send message.";
            exit(); // Exit on failure
        }

        // Insert notification for the recipient, but only if not self-messaging
        if ($outgoing_student_id !== $incoming_student_id) {
            // Get the name of the user who sent the message
            $stmt_sender_name = $pdo->prepare("SELECT full_name FROM users WHERE student_id = ?");
            $stmt_sender_name->execute([$outgoing_student_id]);
            $sender_name = $stmt_sender_name->fetchColumn();

            $message_preview = substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '');

            $notification_message = htmlspecialchars($sender_name) . " sent you a new message: \"" . htmlspecialchars($message_preview) . "\"";
            // Correct link to directly open the chat with the sender
            $notification_link = 'dashboard/chat/chat.php?user_id=' . urlencode($outgoing_student_id);

            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, message, link) VALUES (?, ?, ?, ?, ?)");
            $stmt_notify->execute([$incoming_student_id, $outgoing_student_id, 'new_pm', $notification_message, $notification_link]);
        }

        $pdo->commit(); // Commit the PDO transaction

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on PDO error
        error_log("Database error inserting private message notification: " . $e->getMessage());
        // Handle error appropriately for AJAX response if needed
    } catch (Exception $e) {
        // Catch any other exceptions
        error_log("General error in insert-chat.php: " . $e->getMessage());
    }

    // No specific output is typically needed for successful insertion in this AJAX script,
    // as get-chat.php will re-fetch and display it.
?>
