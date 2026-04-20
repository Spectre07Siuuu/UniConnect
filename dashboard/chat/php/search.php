<?php
    session_start();
    include_once "config.php"; // Uses the updated chat config.php

    // Get the logged-in user's student_id from the session
    $outgoing_student_id = $_SESSION['user_id'] ?? null; // $_SESSION['user_id'] now stores student_id

    // Get the search term from the AJAX POST request
    $searchTerm = mysqli_real_escape_string($conn, $_POST['searchTerm'] ?? '');

    // If student_id or search term is missing, exit
    if (!$outgoing_student_id || empty($searchTerm)) {
        echo 'Please enter a name or Student ID to search.'; // Provide feedback for empty search
        exit();
    }

    $output = "";
    // SQL query to search for users by full_name OR student_id, excluding the current user.
    $sql = "SELECT student_id, full_name, profile_picture, status FROM users
            WHERE NOT student_id = '{$outgoing_student_id}'
            AND (full_name LIKE '%{$searchTerm}%' OR student_id LIKE '%{$searchTerm}%')
            ORDER BY status DESC, full_name ASC"; // Order by status (online first) then by name

    $query = mysqli_query($conn, $sql);

    if ($query === false) {
        error_log("MySQL Query Error in search.php: " . mysqli_error($conn) . " SQL: " . $sql);
        $output .= 'An error occurred during search. Please try again later.';
    } elseif (mysqli_num_rows($query) > 0) {
        // If users are found, include data.php to format and display them.
        // data.php has already been updated to handle the new schema and pathing.
        include_once "data.php";
    } else {
        $output .= 'No user found related to your search term.';
    }
    echo $output;
?>
