<?php
    require_once __DIR__ . "/config.php";

    $outgoing_student_id = chatCurrentUserId();

    if (!$outgoing_student_id) {
        error_log("Chat php/users.php: Session 'user_id' (student_id) not found. Exiting gracefully for AJAX request.");
        echo '<div class="text">Authentication required. Please <a href="../../auth/index.php">log in</a>.</div>';
        exit();
    }

    $users = chatFetchConversationUsers($pdo, $outgoing_student_id);

    if (empty($users)) {
        $output = '';
        $output .= '<div class="text">No conversations yet. Use the search bar to find users and start chatting!</div>';
    } else {
        $output = chatRenderUserList($pdo, $users, $outgoing_student_id);
    }
    echo $output;
?>
