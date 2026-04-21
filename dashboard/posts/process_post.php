<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php
require_once '../../auth/includes/auth_helpers.php';

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken(getCsrfTokenFromRequest())) {
        $response['message'] = 'Invalid or missing security token.';
        echo json_encode($response);
        exit();
    }

    // Get user's student_id from session
    $user_student_id = $_SESSION['user_id'] ?? null;
    if (!$user_student_id) {
        $response['message'] = 'Authentication required.';
        echo json_encode($response);
        exit();
    }

    // Retrieve raw input – escaping is done only at output time
    $post_text = trim($_POST['post_text'] ?? '');
    $category = trim($_POST['category'] ?? 'general');

    // Validate category against allowed ENUM values
    $allowed_categories = ['general', 'academic', 'buy-sell', 'lost-found'];
    if (!in_array($category, $allowed_categories)) {
        $response['message'] = 'Invalid post category selected.';
        echo json_encode($response);
        exit();
    }

    if (empty($post_text)) {
        $response['message'] = 'Post content cannot be empty.';
        echo json_encode($response);
        exit();
    }

    $post_image_url = null;
    $uploadBaseDir = 'images/posts/'; // Directory relative to project root for post images
    $uploadFullPath = __DIR__ . '/../../' . $uploadBaseDir; // Absolute path on server

    // Handle image upload if provided
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['post_image']['tmp_name'];
        $fileName = $_FILES['post_image']['name'];
        $fileSize = $_FILES['post_image']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Basic validation for image
        if ($fileSize > 10 * 1024 * 1024) { // Max 10MB
            $response['message'] = 'Image size must be less than 10MB.';
            echo json_encode($response);
            exit();
        }
        $allowedFileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedFileExtensions)) {
            $response['message'] = 'Invalid image type. Only JPG, JPEG, PNG, GIF are allowed.';
            echo json_encode($response);
            exit();
        }

        // Ensure upload directory exists and is writable
        if (!is_dir($uploadFullPath)) {
            if (!mkdir($uploadFullPath, 0777, true)) {
                $response['message'] = 'Failed to create post image upload directory. Check server permissions.';
                error_log("Failed to create post image upload directory: " . $uploadFullPath);
                echo json_encode($response);
                exit();
            }
        }
        if (!is_writable($uploadFullPath)) {
            $response['message'] = 'Post image upload directory is not writable. Check server permissions.';
            error_log("Post image upload directory not writable: " . $uploadFullPath);
            echo json_encode($response);
            exit();
        }

        $newFileName = uniqid('post_') . '_' . md5($fileName . microtime()) . '.' . $fileExtension; // Unique filename
        $destPath = $uploadFullPath . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $post_image_url = $uploadBaseDir . $newFileName; // Path to store in DB
        } else {
            $last_error = error_get_last();
            error_log("Failed to move uploaded post image for user " . $user_student_id . ": " . ($last_error['message'] ?? 'Unknown error'));
            $response['message'] = 'Error saving image on the server.';
            echo json_encode($response);
            exit();
        }
    }

    // Insert post into database
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, post_text, post_image_url, category) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_student_id, $post_text, $post_image_url, $category])) {
            $response['success'] = true;
            $response['message'] = 'Post created successfully!';
        } else {
            $response['message'] = 'Failed to create post in the database.';
        }
    } catch (PDOException $e) {
        error_log("Database error creating post for user " . $user_student_id . ": " . $e->getMessage());
        $response['message'] = 'Database error creating post.';
    }

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
