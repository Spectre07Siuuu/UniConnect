<?php
require_once __DIR__ . '/../auth/includes/auth_helpers.php';

ensureSessionStarted();

// Check if user is logged in by verifying $_SESSION['user_id']
// $_SESSION['user_id'] now stores the student_id (the primary key in the new schema)
if (!isset($_SESSION['user_id'])) {
    redirectTo('../auth/index.php');
}

// Additionally, check if user is active (from friend's check_session.php logic)
if (!isset($_SESSION['is_active']) || $_SESSION['is_active'] !== true) {
    redirectTo('../auth/index.php');
}
?>
