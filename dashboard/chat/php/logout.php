<?php
// This file is now primarily for redirection to the main system's logout script.
// The main logout.php (in the root directory) handles session destruction and status update.

session_start(); // Start session to ensure session data is available for redirection if needed

// Redirect to the main system's logout script
header("location: ../../auth/logout.php"); // Go up two directories from Admin-Dashboard/chat/php/ to reach root
exit(); // Always exit after a header redirect
?>
