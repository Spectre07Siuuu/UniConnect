<?php
// Start the session
session_start();

// Check if user is logged in by verifying $_SESSION['user_id']
// $_SESSION['user_id'] now stores the student_id (the primary key in the new schema)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../auth/index.php"); // Redirect to the main login page (root index.php)
    exit;
}

// Additionally, check if user is active (from friend's check_session.php logic)
if (!isset($_SESSION['is_active']) || $_SESSION['is_active'] !== true) {
    // Redirect to login page if user session is not marked as active
    // This could happen if session was manually tampered with or not fully initialized
    header("Location: ../auth/index.php"); // Redirect to the main login page
    exit;
}
?>
