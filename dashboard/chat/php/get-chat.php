<?php
    require_once 'config.php';

    $outgoing_student_id = chatCurrentUserId();
    $incoming_student_id = trim($_POST['incoming_id'] ?? '');

    // If either ID is missing, exit or redirect (though AJAX should handle errors)
    if (!$outgoing_student_id || empty($incoming_student_id)) {
        // In an AJAX context, a simple exit or error message is appropriate
        echo '<div class="text">Error: User IDs not provided.</div>';
        exit();
    }

    $messages = chatFetchMessages($pdo, $outgoing_student_id, $incoming_student_id);
    $output = "";

    if (!empty($messages)) {
        foreach ($messages as $row) {
            if ($row['outgoing_msg_id'] === $outgoing_student_id) {
                $output .= '<div class="chat outgoing">
                                <div class="details">
                                    <p>' . htmlspecialchars($row['msg']) . '</p>
                                </div>
                            </div>';
            } else {
                $profile_img_path = chatImagePath($row['profile_picture'] ?? null);
                $output .= '<div class="chat incoming">
                                <img src="' . htmlspecialchars($profile_img_path) . '" alt="Profile Picture" onerror="this.src=\'../../images/uniconnect.png\';" style="object-fit: cover;">
                                <div class="details">
                                    <p>' . htmlspecialchars($row['msg']) . '</p>
                                </div>
                            </div>';
            }
        }
    } else {
        $output .= '<div class="text">No messages are available. Once you send a message, they will appear here.</div>';
    }
    echo $output;
?>
