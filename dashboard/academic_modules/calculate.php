<?php
// This file processes the CGPA calculation.
// It does not interact with the database or user session data directly for its calculation logic.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grades = $_POST['grade'] ?? [];
    $credits = $_POST['credit'] ?? [];
    $current_cgpa = floatval($_POST['current_cgpa'] ?? 0);
    $completed_credits = floatval($_POST['completed_credits'] ?? 0);

    // Basic validation
    if (empty($grades) || empty($credits) || count($grades) !== count($credits)) {
        echo "<span style='color: red;'>Error: Please enter grades and credits for all subjects.</span>";
        exit;
    }

    $new_points = 0;
    $new_credits = 0;
    $has_invalid_input = false;

    for ($i = 0; $i < count($grades); $i++) {
        $grade = floatval($grades[$i]);
        $credit = floatval($credits[$i]);

        // Validate individual inputs
        if ($grade < 0 || $credit <= 0) { // Credit cannot be zero or negative
            $has_invalid_input = true;
            break;
        }

        $new_points += $grade * $credit;
        $new_credits += $credit;
    }

    if ($has_invalid_input) {
        echo "<span style='color: red;'>Error: Grades must be non-negative and Credit Hours must be positive.</span>";
        exit;
    }
    
    if ($new_credits === 0) { // Avoid division by zero for GPA
        echo "<span style='color: red;'>Error: Total new credit hours cannot be zero.</span>";
        exit;
    }

    $gpa = $new_points / $new_credits;

    $total_points = ($current_cgpa * $completed_credits) + $new_points;
    $total_credits = $completed_credits + $new_credits;

    if ($total_credits === 0) { // Avoid division by zero for CGPA
        echo "<span style='color: red;'>Error: Total accumulated credits cannot be zero for CGPA calculation.</span>";
        exit;
    }
    
    $new_cgpa = $total_points / $total_credits;

    echo "<strong>Semester GPA:</strong> <span>" . number_format($gpa, 2) . "</span><br>";
    echo "<strong>Updated CGPA:</strong> <span>" . number_format($new_cgpa, 2) . "</span>";
} else {
    echo "<span style='color: red;'>Invalid request method for calculation.</span>";
}
?>
