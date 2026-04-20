<?php
    session_start();
    include_once "config.php"; // Uses the updated chat config.php

    // Get the logged-in user's student_id from the session (outgoing message sender)
    $outgoing_student_id = $_SESSION['user_id'] ?? null; // $_SESSION['user_id'] now stores student_id

    // Get the incoming chat partner's student_id from the AJAX POST request
    $incoming_student_id = mysqli_real_escape_string($conn, $_POST['incoming_id'] ?? '');

    // If either ID is missing, exit or redirect (though AJAX should handle errors)
    if (!$outgoing_student_id || empty($incoming_student_id)) {
        // In an AJAX context, a simple exit or error message is appropriate
        echo '<div class="text">Error: User IDs not provided.</div>';
        exit();
    }

    $output = "";
    // SQL query to fetch messages.
    // LEFT JOIN with 'users' table to get the profile_picture of the sender for incoming messages.
    // Messages table's incoming_msg_id and outgoing_msg_id now store student_id (VARCHAR).
    $sql = "SELECT messages.*, users.profile_picture
            FROM messages
            LEFT JOIN users ON users.student_id = messages.outgoing_msg_id
            WHERE (outgoing_msg_id = '{$outgoing_student_id}' AND incoming_msg_id = '{$incoming_student_id}')
            OR (outgoing_msg_id = '{$incoming_student_id}' AND incoming_msg_id = '{$outgoing_student_id}')
            ORDER BY msg_id ASC"; // Order by msg_id to ensure chronological order

    $query = mysqli_query($conn, $sql);

    if ($query === false) {
        error_log("MySQL Query Error in get-chat.php: " . mysqli_error($conn) . " SQL: " . $sql);
        $output .= '<div class="text">An error occurred while fetching messages.</div>';
    } elseif (mysqli_num_rows($query) > 0) {
        while($row = mysqli_fetch_assoc($query)){
            // Check if the message is outgoing (sent by current logged-in user) or incoming
            if($row['outgoing_msg_id'] === $outgoing_student_id){
                // Outgoing message (no profile picture displayed for sender's own messages)
                $output .= '<div class="chat outgoing">
                                <div class="details">
                                    <p>'. htmlspecialchars($row['msg']) .'</p>
                                </div>
                            </div>';
            }else{
                // Incoming message (display sender's profile picture)
                // profile_picture is stored relative to project root (e.g., 'images/profile_pictures/foo.jpg').
                // This file is in Admin-Dashboard/chat/php/, so it needs '../../' to go up two directories to reach the root.
                // Corrected onerror path to point to the root images/uniconnect.png
                $profile_img_path = '../../' . ($row['profile_picture'] ?: 'images/uniconnect.png'); // Fallback image if profile_picture is missing or empty
                $output .= '<div class="chat incoming">
                                <img src="'. htmlspecialchars($profile_img_path) .'" alt="Profile Picture" onerror="this.src=\'../../images/uniconnect.png\';" style="object-fit: cover;">
                                <div class="details">
                                    <p>'. htmlspecialchars($row['msg']) .'</p>
                                </div>
                            </div>';
            }
        }
    } else {
        $output .= '<div class="text">No messages are available. Once you send a message, they will appear here.</div>';
    }
    echo $output;
?>
