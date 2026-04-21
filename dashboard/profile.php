<?php
require_once 'check_auth.php';
require_once __DIR__ . '/includes/bootstrap.php';

extract(bootstrapDashboardPage(), EXTR_SKIP);

$updateSuccess = $updateError = $uploadError = null; // Initialize messages for display

// --- Handle Profile Update Submission (AJAX and full form) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken(getCsrfTokenFromRequest())) {
        if (isset($_POST['action'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing security token.']);
        } else {
            $updateError = 'Invalid or missing security token.';
        }
        exit;
    }

    // Base upload directory for profile pictures (relative to root)
    $uploadBaseDir = 'images/profile_pictures/';
    // Full server path for profile picture uploads
    // CORRECTED PATH: Go up one level (from Admin-Dashboard/) to uniconnect/ root, then into images/profile_pictures/
    $uploadFullPath = __DIR__ . '/../' . $uploadBaseDir;

    // Ensure upload directory exists and is writable
    if (!is_dir($uploadFullPath)) {
        if (!mkdir($uploadFullPath, 0777, true)) { // Attempt to create if not exists
            error_log("Failed to create upload directory: " . $uploadFullPath);
            if (isset($_POST['action'])) { // If it's an AJAX request
                echo json_encode(['success' => false, 'message' => 'Failed to create upload directory. Check server permissions.']);
                exit;
            } else { // Full form submission
                $uploadError = 'Failed to create upload directory. Check server permissions.';
            }
        }
    }
    if (!is_writable($uploadFullPath)) {
        error_log("Upload directory not writable: " . $uploadFullPath);
        if (isset($_POST['action'])) { // If it's an AJAX request
            echo json_encode(['success' => false, 'message' => 'Upload directory is not writable. Check server permissions.']);
            exit;
        } else { // Full form submission
            $uploadError = 'Upload directory is not writable. Check server permissions.';
        }
    }


    // Handle AJAX request for adding/removing skill
    if (isset($_POST['action']) && ($_POST['action'] === 'add_skill' || $_POST['action'] === 'remove_skill')) {
        $skill_name = trim($_POST['skill_name'] ?? '');

        if ($user_student_id && !empty($skill_name)) {
            try {
                if ($_POST['action'] === 'add_skill') {
                    // Check if skill already exists for this user
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE user_id = :user_id AND skill_name = :skill_name");
                    $stmt_check->execute([':user_id' => $user_student_id, ':skill_name' => $skill_name]);
                    if ($stmt_check->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => 'Skill already exists.']);
                        exit;
                    }

                    $stmt_insert = $pdo->prepare("INSERT INTO skills (user_id, skill_name) VALUES (:user_id, :skill_name)");
                    if ($stmt_insert->execute([':user_id' => $user_student_id, ':skill_name' => $skill_name])) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error adding skill.']);
                    }
                } else if ($_POST['action'] === 'remove_skill') {
                    $stmt_delete = $pdo->prepare("DELETE FROM skills WHERE user_id = :user_id AND skill_name = :skill_name");
                    if ($stmt_delete->execute([':user_id' => $user_student_id, ':skill_name' => $skill_name])) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error removing skill.']);
                    }
                }
            } catch (PDOException $e) {
                error_log("Error in skill action for student ID " . $user_student_id . ": " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid skill name or user ID.']);
        }
        exit;
    }

    // Handle AJAX request for adding/removing course (to user_courses_profile table)
    if (isset($_POST['action']) && ($_POST['action'] === 'add_course' || $_POST['action'] === 'remove_course')) {
        $course_name = trim($_POST['course_name'] ?? '');
        $section = trim($_POST['section'] ?? '');

        if ($user_student_id && !empty($course_name) && !empty($section)) {
            try {
                if ($_POST['action'] === 'add_course') {
                    // Check if course already exists for this user and section
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM user_courses_profile WHERE user_id = :user_id AND course_name = :course_name AND section = :section");
                    $stmt_check->execute([':user_id' => $user_student_id, ':course_name' => $course_name, ':section' => $section]);
                    if ($stmt_check->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => 'This course with the selected section is already added.']);
                        exit;
                    }

                    $stmt_insert = $pdo->prepare("INSERT INTO user_courses_profile (user_id, course_name, section) VALUES (:user_id, :course_name, :section)");
                    if ($stmt_insert->execute([':user_id' => $user_student_id, ':course_name' => $course_name, ':section' => $section])) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error adding course.']);
                    }
                } else if ($_POST['action'] === 'remove_course') {
                    $stmt_delete = $pdo->prepare("DELETE FROM user_courses_profile WHERE user_id = :user_id AND course_name = :course_name AND section = :section");
                    if ($stmt_delete->execute([':user_id' => $user_student_id, ':course_name' => $course_name, ':section' => $section])) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error removing course.']);
                    }
                }
            } catch (PDOException $e) {
                error_log("Error in course action for student ID " . $user_student_id . ": " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid course data or user ID.']);
        }
        exit;
    }

    // Handle AJAX request for consolidated save (trimester, bio, profile picture, all skills, all courses)
    if (isset($_POST['action']) && $_POST['action'] === 'save_all') {
        $trimester = trim($_POST['trimester'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $skills = json_decode($_POST['skills'] ?? '[]', true);
        $courses = json_decode($_POST['courses'] ?? '[]', true);
        $profilePicturePath = null;

        try {
            $pdo->beginTransaction();

            // --- Handle Profile Picture Upload within Save All ---
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
                $fileName = $_FILES['profile_picture']['name'];
                $fileSize = $_FILES['profile_picture']['size'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($fileSize > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB.']);
                    exit;
                }

                $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $destPath = $uploadFullPath . $newFileName; // Use full server path for moving file
                    $profilePicturePath = $uploadBaseDir . $newFileName; // Store relative path in DB

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Successfully moved, path is set for DB update
                        // IMPORTANT: After a successful upload, you might want to delete the old profile picture file
                        // to prevent clutter, unless it's the default 'uniconnect.png'.
                        // Fetch current profile picture from DB
                        $stmt_old_pic = $pdo->prepare("SELECT profile_picture FROM users WHERE student_id = :student_id");
                        $stmt_old_pic->execute([':student_id' => $user_student_id]);
                        $old_profile_picture_db_path = $stmt_old_pic->fetchColumn();

                        if ($old_profile_picture_db_path && $old_profile_picture_db_path !== 'images/uniconnect.png' && file_exists(__DIR__ . '/../' . $old_profile_picture_db_path)) {
                            unlink(__DIR__ . '/../' . $old_profile_picture_db_path); // Delete old file
                        }

                    } else {
                        $last_error = error_get_last();
                        error_log("Failed to move uploaded file in save_all for student ID " . $user_student_id . ". Error: " . ($last_error ? $last_error['message'] : 'Unknown'));
                        echo json_encode(['success' => false, 'message' => 'Error saving the uploaded file on the server.']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, GIF, PNG, and JPEG are allowed.']);
                    exit;
                }
            } else if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Handle specific PHP file upload errors if a file was attempted but failed
                $error_code = $_FILES['profile_picture']['error'];
                $php_errors = array(
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                );
                $error_message = $php_errors[$error_code] ?? 'Unknown file upload error.';
                error_log("File upload error in save_all for student ID " . $user_student_id . ". Error code: " . $error_code . ": " . $error_message);
                echo json_encode(['success' => false, 'message' => 'File upload error: ' . $error_message]);
                exit;
            }
            // --- End of Profile Picture Upload within Save All ---

            // Update users table (trimester, bio, profile_picture) using student_id
            $sql_user = "UPDATE users SET trimester = :trimester, bio = :bio";
            $params_user = [':trimester' => $trimester, ':bio' => $bio];

            if (isset($profilePicturePath)) {
                $sql_user .= ", profile_picture = :profile_picture";
                $params_user[':profile_picture'] = $profilePicturePath;
            }

            $sql_user .= " WHERE student_id = :student_id"; // Use student_id here
            $params_user[':student_id'] = $user_student_id; // Use student_id here

            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute($params_user);

            // Update skills table: Delete existing and insert new
            // user_id in skills table is now VARCHAR(50) referring to student_id
            $stmt_delete_skills = $pdo->prepare("DELETE FROM skills WHERE user_id = :user_id");
            $stmt_delete_skills->execute([':user_id' => $user_student_id]);

            if (!empty($skills)) {
                $sql_insert_skills = "INSERT INTO skills (user_id, skill_name) VALUES (:user_id, :skill_name)";
                $stmt_insert_skills = $pdo->prepare($sql_insert_skills);
                foreach ($skills as $skill) {
                    $sanitized_skill = trim($skill);
                    if (!empty($sanitized_skill)) {
                        $stmt_insert_skills->execute([':user_id' => $user_student_id, ':skill_name' => $sanitized_skill]);
                    }
                }
            }

            // Update user_courses_profile table: Delete existing and insert new
            // user_id in user_courses_profile table is now VARCHAR(50) referring to student_id
            $stmt_delete_courses = $pdo->prepare("DELETE FROM user_courses_profile WHERE user_id = :user_id");
            $stmt_delete_courses->execute([':user_id' => $user_student_id]);

            if (!empty($courses)) {
                $sql_insert_courses = "INSERT INTO user_courses_profile (user_id, course_name, section) VALUES (:user_id, :course_name, :section)";
                $stmt_insert_courses = $pdo->prepare($sql_insert_courses);
                foreach ($courses as $course) {
                     if (isset($course['course_name']) && isset($course['section'])) {
                        $sanitized_course_name = trim($course['course_name']);
                        $sanitized_section = trim($course['section']);
                         if (!empty($sanitized_course_name) && !empty($sanitized_section)) {
                            $stmt_insert_courses->execute([':user_id' => $user_student_id, ':course_name' => $sanitized_course_name, ':section' => $sanitized_section]);
                         }
                     }
                }
            }

            $pdo->commit();

            // Re-fetch profile_picture path to return in JSON response
            $stmt_user_re = $pdo->prepare("SELECT profile_picture FROM users WHERE student_id = :student_id"); // Use student_id here
            $stmt_user_re->execute([':student_id' => $user_student_id]);
            $user_data_re = $stmt_user_re->fetch(PDO::FETCH_ASSOC);
            $updated_profile_picture_path = $user_data_re['profile_picture'] ?? null;

            // Return success with the updated profile picture path (for cache busting)
            echo json_encode(['success' => true, 'profile_picture' => $updated_profile_picture_path]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Consolidated save database error for student ID " . $user_student_id . ": " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error during save.']);
        } catch (Exception $e) {
             $pdo->rollBack();
             error_log("Consolidated save general error for student ID " . $user_student_id . ": " . $e->getMessage());
             echo json_encode(['success' => false, 'message' => 'An unexpected error occurred during save.']);
        }
        exit;
    }
}

// Check if it's an AJAX request to fetch available courses
if (isset($_GET['action']) && $_GET['action'] === 'fetch_courses') {
    try {
        // MODIFIED: Select both course_name and course_code for search functionality
        $stmt = $pdo->prepare("SELECT course_name, course_code FROM available_courses ORDER BY course_name");
        $stmt->execute();
        // Fetch as associative array to get both columns
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'courses' => $courses]);
    } catch (PDOException $e) {
        error_log("Error fetching available courses: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

try {
    // --- Fetch User Data for Display and Completion Calculation ---
    $user_data = [];
    $skills_data = [];
    $courses_data = [];

    // Fetch user data from 'users' table using 'student_id'
    $stmt_user = $pdo->prepare("SELECT student_id, full_name, department, trimester, profile_picture, bio, profile_completion, coin_balance, reputation_points, reward_claimed FROM users WHERE student_id = :student_id");
    $stmt_user->execute([':student_id' => $user_student_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        throw new Exception("User not found for student ID: " . $user_student_id);
    }

    // Fetch skills from the 'skills' table using user_id (which is student_id)
    $stmt_skills = $pdo->prepare("SELECT skill_name FROM skills WHERE user_id = :user_id ORDER BY skill_name");
    $stmt_skills->execute([':user_id' => $user_student_id]);
    $skills_data = $stmt_skills->fetchAll(PDO::FETCH_COLUMN);

    // Fetch courses from the 'user_courses_profile' table using user_id (which is student_id)
    $stmt_courses = $pdo->prepare("SELECT course_name, section FROM user_courses_profile WHERE user_id = :user_id ORDER BY course_name, section");
    $stmt_courses->execute([':user_id' => $user_student_id]);
    $courses_data = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

    // Assign fetched data to variables
    $full_name = $user_data['full_name'] ?? ''; // Now directly from user_data
    $student_id = $user_data['student_id'] ?? '';
    $department = $user_data['department'] ?? '';
    $trimester = $user_data['trimester'] ?? '';
    // Adjust path for profile picture for display: needs to be relative from Admin-Dashboard/
    // Path stored in DB is relative to root, e.g., 'images/profile_pictures/abc.jpg'
    // ADDED CACHE BUSTING: Append a query string with the current timestamp
    $profile_picture = $user_data['profile_picture'] ? '../' . $user_data['profile_picture'] . '?t=' . time() : '../images/uniconnect.png?t=' . time();
    $bio = $user_data['bio'] ?? '';
    $profile_completion = $user_data['profile_completion'] ?? 0;
    $coins = $user_data['coin_balance'] ?? 0;
    $reputation = $user_data['reputation_points'] ?? 0;
    $reward_claimed = $user_data['reward_claimed'] ?? FALSE;

    // --- Calculate Profile Completion ---
    $completion_criteria = [
        'trimester' => !empty($trimester),
        'bio' => !empty($bio),
        // Check if profile_picture is not the default fallback or empty
        'profile_picture' => !empty($user_data['profile_picture']) && $user_data['profile_picture'] !== 'images/uniconnect.png',
        'skills' => !empty($skills_data),
        'courses' => !empty($courses_data)
    ];

    $completed_fields = 0;
    foreach ($completion_criteria as $criterion => $is_completed) {
        if ($is_completed) {
            $completed_fields++;
        }
    }
    $total_fields = count($completion_criteria);
    $calculated_completion = ($total_fields > 0) ? round(($completed_fields / $total_fields) * 100) : 0;

    // --- Update Profile Completion and Check for Reward ---
    // Only update if calculated completion is different from stored, and not on initial load if already 100%
    if ($calculated_completion !== (int)$profile_completion) {
        try {
            $pdo->beginTransaction();

            // Update profile completion
            $stmt_update_completion = $pdo->prepare("UPDATE users SET profile_completion = :completion WHERE student_id = :student_id"); // Use student_id
            $stmt_update_completion->execute([':completion' => $calculated_completion, ':student_id' => $user_student_id]);
            $profile_completion = $calculated_completion; // Update local variable

            // Check for 100% completion reward only if it just reached 100% and not claimed before
            if ($profile_completion === 100 && !$reward_claimed) {
                $reward_coins = 5;
                $stmt_give_reward = $pdo->prepare("UPDATE users SET coin_balance = coin_balance + :reward_coins, reward_claimed = TRUE WHERE student_id = :student_id"); // Use student_id
                if ($stmt_give_reward->execute([':reward_coins' => $reward_coins, ':student_id' => $user_student_id])) {
                    $coins += $reward_coins; // Update local variable
                    $reward_claimed = TRUE; // Update local variable
                    $updateSuccess = ($updateSuccess ?? "") . " Congratulations! You earned {$reward_coins} coins for completing your profile!";
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Profile completion update error for student ID " . $user_student_id . ": " . $e->getMessage());
            $updateError = "An error occurred while updating your profile completion.";
        }
    }

    // Get completion status for each section (for UI display)
    $completion_status = [
        'trimester' => [
            'completed' => $completion_criteria['trimester'],
            'label' => 'Trimester',
            'icon' => 'mdi-calendar'
        ],
        'bio' => [
            'completed' => $completion_criteria['bio'],
            'label' => 'Bio',
            'icon' => 'mdi-account-details'
        ],
        'profile_picture' => [
            'completed' => $completion_criteria['profile_picture'],
            'label' => 'Profile Picture',
            'icon' => 'mdi-camera'
        ],
        'skills' => [
            'completed' => $completion_criteria['skills'],
            'label' => 'Skills',
            'icon' => 'mdi-star'
        ],
        'courses' => [
            'completed' => $completion_criteria['courses'],
            'label' => 'Courses',
            'icon' => 'mdi-book'
        ]
    ];

} catch (Exception $e) {
    error_log("Profile data fetch error for student ID " . $user_student_id . ": " . $e->getMessage());
    $updateError = "An error occurred while loading your profile data.";
}

// --- HTML Structure ---
?>
<?php renderDashboardHead('Profile - UniConnect', [
    'css/profile.css',
    'css/modals.css',
    'css/academic_tools.css',
    'css/calculators.css',
    'css/responsive.css',
]); ?>
<?php renderDashboardShellStart('profile', 'Profile', $coins, $profile_picture, $user_student_id); ?>
<?php require __DIR__ . '/views/profile_content.php'; ?>
<?php renderDashboardShellEnd(['javascript/profile.js']); ?>
