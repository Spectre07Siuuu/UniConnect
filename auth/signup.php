<?php
require __DIR__ . '/../config/db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data and sanitize
    $full_name   = trim(htmlspecialchars($_POST['full_name']));
    $student_id  = trim(htmlspecialchars($_POST['student_id']));
    $email       = trim(htmlspecialchars($_POST['email']));
    $password    = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];
    $department  = trim(htmlspecialchars($_POST['department']));

    // Validation array to store errors
    $errors = [];

    // Validate full name
    if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        $errors[] = "Full name must be between 2 and 100 characters.";
    }

    // Validate student ID format (assuming it should be alphanumeric)
    if (!preg_match('/^[A-Za-z0-9]+$/', $student_id)) {
        $errors[] = "Student ID should only contain letters and numbers.";
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Basic password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Check if passwords match
    if ($password !== $confirmPass) {
        $errors[] = "Passwords do not match.";
    }

    // Validate department
    $valid_departments = ['CSE', 'BBA', 'EEE', 'BSDS', 'EDS', 'MSJB', 'PHARM', 'BGE'];
    if (!in_array($department, $valid_departments)) {
        $errors[] = "Please select a valid department.";
    }

    // If there are errors, display them and redirect
    if (!empty($errors)) {
        $error_message = implode("\\n", $errors);
        // Using session for error messages to display on index.php
        session_start();
        $_SESSION['login_error'] = $error_message; // Using login_error for general signup errors
        header("Location: index.php");
        exit;
    }

    try {
        // Check if student ID exists (since it's now PK and UNIQUE)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->fetchColumn() > 0) {
            session_start();
            $_SESSION['login_error'] = 'Student ID already exists.';
            header("Location: index.php");
            exit;
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            session_start();
            $_SESSION['login_error'] = 'Email already exists.';
            header("Location: index.php");
            exit;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // --- Initialize profile-specific fields for new user ---
        // These fields are now directly part of the 'users' table in the new schema
        $default_profile_picture = 'images/uniconnect.png'; // Default profile image path for main system
        $default_trimester = NULL; // Default to NULL, user updates in profile
        $default_bio = NULL;     // Default to NULL, user updates in profile
        $default_profile_completion = 0; // Default completion percentage
        $default_reward_claimed = FALSE; // Reward not claimed yet
        $default_coin_balance = 5;       // Default coins
        $default_reputation_points = 0;  // Default reputation
        $default_status = "Offline now"; // Default chat status for new user

        // Prepare and execute insert into the new 'users' table structure
        // Columns list must exactly match the order of '?' placeholders
        $sql = "INSERT INTO users (student_id, full_name, email, password, department,
                                   trimester, profile_picture, bio, profile_completion, reward_claimed,
                                   coin_balance, reputation_points, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            $student_id, $full_name, $email, $hashed_password, $department,
            $default_trimester, $default_profile_picture, $default_bio, $default_profile_completion, $default_reward_claimed,
            $default_coin_balance, $default_reputation_points, $default_status
        ])) {
            // Start session and set success message
            session_start();
            $_SESSION['signup_success'] = true;

            // Redirect to login page
            header("Location: index.php");
            exit;
        } else {
            session_start();
            $_SESSION['login_error'] = 'Error creating account. Please try again.';
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Signup PDO error: " . $e->getCode() . " - " . $e->getMessage());
        session_start();
        // Use a more specific error message for duplicate entry (PK or unique constraint violation)
        if ($e->getCode() == 23000) {
             $_SESSION['login_error'] = 'Student ID or Email already registered. Please login or use a different one.';
        } else {
            $_SESSION['login_error'] = 'An unexpected error occurred during signup. Please try again later.';
        }
        header("Location: index.php");
        exit;
    }
} else {
    // If someone tries to access this page directly, redirect to index
    header("Location: index.php");
    exit;
}
?>
