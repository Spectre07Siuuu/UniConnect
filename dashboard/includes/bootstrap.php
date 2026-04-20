<?php

require_once __DIR__ . '/layout.php';

function bootstrapDashboardPage(): array
{
    require __DIR__ . '/../../config/db_connect.php';

    $userStudentId = $_SESSION['user_id'] ?? null;
    $fullName = $_SESSION['full_name'] ?? 'User';
    $shellData = getDashboardShellData($pdo, $userStudentId);

    return [
        'pdo' => $pdo,
        'user_student_id' => $userStudentId,
        'full_name' => $fullName,
        'coins' => $shellData['coins'],
        'profile_picture' => $shellData['profile_picture'],
        'shell_data' => $shellData,
    ];
}
