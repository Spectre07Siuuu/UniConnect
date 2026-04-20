<?php
// Start session first
session_start();
require __DIR__ . '/../config/db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim(htmlspecialchars($_POST['student_id']));
    $password = $_POST['password'];

    // Basic validation
    if (empty($student_id) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: index.php");
        exit;
    }

    try {
        // Find the user using 'student_id' (now the primary key)
        $stmt = $pdo->prepare("SELECT student_id, full_name, password, department, profile_picture, status FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set main system session variables
            // $_SESSION['user_id'] now stores student_id, which is the primary key
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['student_id'] = $user['student_id']; // Keep for clarity if needed elsewhere, but user_id is the canonical ID
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['is_active'] = true; // Friend's added session flag

            // Profile picture and status directly from the consolidated 'users' table
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['status'] = $user['status']; // Current status from DB

            // Update user status to "Active now" in the database upon successful login
            $status_update = "Active now";
            $stmt_update_status = $pdo->prepare("UPDATE users SET status = :status WHERE student_id = :student_id");
            $stmt_update_status->execute([':status' => $status_update, ':student_id' => $user['student_id']]);
            $_SESSION['status'] = $status_update; // Update session with new status

            // Redirect directly to Admin-Dashboard index
            header("Location: ../dashboard/index.php");
            exit;
        } else {
            // Invalid credentials - set error and redirect back to index
            $_SESSION['login_error'] = "Invalid student ID or password.";
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        // Database error - log it and show generic error
        error_log("Login PDO Error: " . $e->getCode() . " - " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login. Please try again later."; // Generic error for user
        header("Location: index.php");
        exit;
    }
} else {
    // If someone tries to access this page directly, redirect to index
    header("Location: index.php");
    exit;
}
?>
