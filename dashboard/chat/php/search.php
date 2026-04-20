<?php
    require_once 'config.php';

    $outgoing_student_id = chatCurrentUserId();
    $searchTerm = trim($_POST['searchTerm'] ?? '');

    // If student_id or search term is missing, exit
    if (!$outgoing_student_id || empty($searchTerm)) {
        echo 'Please enter a name or Student ID to search.'; // Provide feedback for empty search
        exit();
    }

    $users = chatSearchUsers($pdo, $outgoing_student_id, $searchTerm);
    $output = empty($users)
        ? 'No user found related to your search term.'
        : chatRenderUserList($pdo, $users, $outgoing_student_id);
    echo $output;
?>
