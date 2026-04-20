<?php
    session_start();
    // Use an absolute path based on __DIR__ (current directory of this file)
    // This file is in Admin-Dashboard/chat/php/. So, to include config.php in the same folder, it's just its name.
    include_once __DIR__ . "/config.php";

    // Get the logged-in user's student_id from the main session
    // $_SESSION['user_id'] now consistently stores student_id as the primary identifier
    $outgoing_student_id = $_SESSION['user_id'] ?? null;

    // --- Critical Check: If outgoing_student_id is not set, redirect to login ---
    if (!$outgoing_student_id) {
        error_log("Chat php/users.php: Session 'user_id' (student_id) not found. Exiting gracefully for AJAX request.");
        echo '<div class="text">Authentication required. Please <a href="../../auth/index.php">log in</a>.</div>';
        exit();
    }

    // Sanitize the outgoing student_id for database queries to prevent SQL injection
    $outgoing_student_id_sql = mysqli_real_escape_string($conn, $outgoing_student_id);

    // SQL query to fetch users who have had a conversation with the current logged-in user.
    // It selects distinct users from messages table (either incoming or outgoing to current user)
    // and then joins with the users table to get their details.
    $sql = "SELECT DISTINCT U.student_id, U.full_name, U.profile_picture, U.status
            FROM users U
            JOIN messages M ON (U.student_id = M.incoming_msg_id AND M.outgoing_msg_id = '{$outgoing_student_id_sql}')
                           OR (U.student_id = M.outgoing_msg_id AND M.incoming_msg_id = '{$outgoing_student_id_sql}')
            WHERE U.student_id != '{$outgoing_student_id_sql}' -- Exclude current user from the list
            ORDER BY U.status DESC, U.full_name ASC"; // Order by status (online first) then by name

    $query = mysqli_query($conn, $sql);

    if ($query === false) {
        error_log("ERROR: php/users.php - mysqli_query failed! MySQL Error: " . mysqli_error($conn) . " Query: " . $sql);
        echo '<div class="text">Error loading users. Please try again later.</div>';
        exit();
    }
    
    $output = ""; // Initialize output variable
    if (mysqli_num_rows($query) == 0) {
        $output .= '<div class="text">No conversations yet. Use the search bar to find users and start chatting!</div>';
    } else {
        include_once "data.php"; // This script will process the results and generate HTML
    }
    echo $output;
?>
