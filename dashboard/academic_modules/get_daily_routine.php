<?php
// This script fetches and returns the class routine for a specific date via AJAX.
session_start(); // Start session to access user_id
require_once '../../config/db_connect.php'; // Path to root db_connect.php

header('Content-Type: text/html'); // Ensure browser expects HTML response

// Get logged-in user's student_id
$user_student_id = $_SESSION['user_id'] ?? null;

// Get the requested date from GET parameters
$selected_date_str = $_GET['date'] ?? '';

$output_html = ''; // Initialize output HTML

if (!$user_student_id) {
    echo '<p class="error-message">Authentication required to view routine.</p>';
    exit();
}

// Basic date validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date_str) || !strtotime($selected_date_str)) {
    echo '<p class="error-message">Invalid date provided.</p>';
    exit();
}

// Extract the day of the week from the selected date string
$selected_day_of_week = strtolower(date('l', strtotime($selected_date_str)));

try {
    // 1. Get user's courses (course_name and section) from user_courses_profile
    $stmt_user_courses = $pdo->prepare("SELECT course_name, section FROM user_courses_profile WHERE user_id = :user_id");
    $stmt_user_courses->execute([':user_id' => $user_student_id]);
    $user_courses_profile_entries = $stmt_user_courses->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($user_courses_profile_entries)) {
        // Prepare a list of conditions for the routine query
        $routine_conditions = [];
        foreach ($user_courses_profile_entries as $ucp) {
            $routine_conditions[] = "(C.course_name = " . $pdo->quote($ucp['course_name']) . " AND R.section = " . $pdo->quote($ucp['section']) . ")";
        }
        $routine_filter_clause = implode(' OR ', $routine_conditions);

        // 2. Fetch routine entries for these courses for the requested day
        // Removed R.user_id = :user_id condition as routine is now global.
        $stmt_routine = $pdo->prepare("
            SELECT
                R.start_time,
                R.end_time,
                R.room_number,
                C.course_name,
                R.section
            FROM routine AS R
            JOIN course AS C ON R.course_id = C.course_id
            WHERE R.day_of_week = :day_of_week AND (" . $routine_filter_clause . ")
            ORDER BY R.start_time ASC
        ");
        $stmt_routine->execute([
            ':day_of_week' => $selected_day_of_week
            // user_id parameter is removed from execute()
        ]);
        $routine_entries = $stmt_routine->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($routine_entries)) {
            foreach ($routine_entries as $entry) {
                $subject_class = 'subject-' . strtolower(str_replace(' ', '-', $entry['course_name']));
                $output_html .= '
                    <div class="class-item ' . htmlspecialchars($subject_class) . '">
                      <div class="class-details">
                        <h3>' . htmlspecialchars($entry['course_name']) . '</h3>
                        <p class="class-time">
                          <i class="mdi mdi-clock-outline"></i> ' . date('h:i A', strtotime($entry['start_time'])) . ' - ' . date('h:i A', strtotime($entry['end_time'])) . '
                        </p>
                        <div class="class-location">
                          <span class="section-tag">Section ' . htmlspecialchars($entry['section']) . '</span>
                          <span class="room-tag">Room ' . htmlspecialchars($entry['room_number']) . '</span>
                        </div>
                      </div>
                    </div>';
            }
        } else {
            $output_html = '<p class="no-routine-message">No classes scheduled for ' . htmlspecialchars(ucfirst($selected_day_of_week)) . ', ' . date('F j, Y', strtotime($selected_date_str)) . '.</p>';
        }
    } else {
        $output_html = '<p class="no-routine-message">Please add your courses in the <a href="profile.php">Profile</a> section and ensure routine entries exist for them to see your schedule.</p>';
    }
} catch (PDOException $e) {
    error_log("Error fetching daily routine for user " . $user_student_id . " for date $selected_date_str: " . $e->getMessage());
    $output_html = '<p class="error-message">Error loading routine for selected date. Please try again later. (DB Error)</p>';
}

echo $output_html;
exit();
?>
