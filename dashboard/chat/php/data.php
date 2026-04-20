<?php
    // This script is included by users.php and search.php.
    // $conn (mysqli connection) and $outgoing_student_id are expected to be available in this scope.
    // $query (mysqli result set of users) is also expected.

    // Ensure $outgoing_student_id is sanitized if not already in calling script.
    // Assuming it's sanitized in users.php and search.php before calling data.php

    $output = ""; // Initialize output variable

    if (mysqli_num_rows($query) > 0) { // Check if there are any users in the result set
        while($row = mysqli_fetch_assoc($query)){
            // Get the last message between the outgoing user and the current user in the loop
            $sql2 = "SELECT msg, outgoing_msg_id FROM messages
                     WHERE (incoming_msg_id = '{$row['student_id']}' AND outgoing_msg_id = '{$outgoing_student_id}')
                        OR (incoming_msg_id = '{$outgoing_student_id}' AND outgoing_msg_id = '{$row['student_id']}')
                     ORDER BY msg_id DESC LIMIT 1";
            $query2 = mysqli_query($conn, $sql2);

            $result = "";
            $last_msg_sender_id = null;
            if ($query2 && mysqli_num_rows($query2) > 0) {
                $row2 = mysqli_fetch_assoc($query2);
                $result = $row2['msg'];
                $last_msg_sender_id = $row2['outgoing_msg_id'];
            } else {
                $result = "No message available"; // Should ideally not hit this if fetched from messages
            }

            // Truncate message if too long
            (strlen($result) > 28) ? $msg =  substr($result, 0, 28) . '...' : $msg = $result;

            // Determine "You:" prefix for last message
            ($outgoing_student_id == $last_msg_sender_id) ? $you = "You: " : $you = "";

            // Determine online/offline status class
            ($row['status'] == "Offline now") ? $offline = "offline" : $offline = "";

            // Determine if the current user (outgoing_student_id) is the one being listed (hide self from list)
            // This condition is primarily for search.php, where it might find the current user.
            // For users.php (conversation list), the main query already excludes the current user.
            ($outgoing_student_id == $row['student_id']) ? $hid_me = "hide" : $hid_me = "";

            // Build the output HTML for each user
            // Use full_name for display, profile_picture for image source.
            // Image path: profile_picture is stored relative to root (e.g., 'images/profile_pictures/foo.jpg').
            // data.php is in Admin-Dashboard/chat/php/, so it needs to go up two directories (../../) to reach the root.
            $profile_image_path = '../../' . htmlspecialchars($row['profile_picture']);
            
            $output .= '<a href="chat.php?user_id='. htmlspecialchars($row['student_id']) .'" class="'. htmlspecialchars($hid_me) .'">
                        <div class="content">
                        <img src="'. $profile_image_path .'" alt="Profile Picture" onerror="this.src=\'../../images/uniconnect.png\';" style="object-fit: cover;">
                        <div class="details">
                            <span>'. htmlspecialchars($row['full_name']) .'</span>
                            <p>'. htmlspecialchars($you) . htmlspecialchars($msg) .'</p>
                        </div>
                        </div>
                        <div class="status-dot '. htmlspecialchars($offline) .'"><i class="fas fa-circle"></i></div>
                    </a>';
        }
    } else {
        // This block might be hit if search.php finds no users.
        // For users.php, the "No conversations yet" message is handled by users.php itself.
        $output .= '<div class="text">No users are available to chat</div>';
    }
?>
