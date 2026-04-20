<?php
// Start the session
session_start();

// Include database connection to update status
require __DIR__ . '/../config/db_connect.php';

// Check if student_id is set in session before attempting to update status
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id']; // Use student_id as it's the primary identifier now
    $status = "Offline now";
    try {
        // Update user status in the 'users' table using student_id
        $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE student_id = :student_id");
        $stmt->execute([':status' => $status, ':student_id' => $student_id]);
    } catch (PDOException $e) {
        // Log error, but don't prevent logout, as session destruction is more critical
        error_log("Failed to update user status on logout for student ID " . $student_id . ": " . $e->getMessage());
    }
}

// Unset all session variables specific to the application
$_SESSION = array(); // Clears all session data

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: index.php");
exit;
?>
