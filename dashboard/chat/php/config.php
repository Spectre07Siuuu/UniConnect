<?php
require_once __DIR__ . '/../../../auth/includes/auth_helpers.php';
require_once __DIR__ . '/../../../config/db_connect.php';

function chatCurrentUserId(): ?string
{
    ensureSessionStarted();
    return $_SESSION['user_id'] ?? null;
}

function chatFetchUser(PDO $pdo, string $studentId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT student_id, full_name, profile_picture, status FROM users WHERE student_id = ?'
    );
    $stmt->execute([$studentId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function chatFetchConversationUsers(PDO $pdo, string $currentUserId): array
{
    $stmt = $pdo->prepare(
        "SELECT DISTINCT u.student_id, u.full_name, u.profile_picture, u.status
         FROM users u
         JOIN messages m
           ON (
                (u.student_id = m.incoming_msg_id AND m.outgoing_msg_id = :user_id)
             OR (u.student_id = m.outgoing_msg_id AND m.incoming_msg_id = :user_id)
           )
         WHERE u.student_id != :user_id
         ORDER BY u.status DESC, u.full_name ASC"
    );
    $stmt->execute([':user_id' => $currentUserId]);

    return $stmt->fetchAll();
}

function chatSearchUsers(PDO $pdo, string $currentUserId, string $searchTerm): array
{
    $stmt = $pdo->prepare(
        "SELECT student_id, full_name, profile_picture, status
         FROM users
         WHERE student_id != :user_id
           AND (full_name LIKE :search OR student_id LIKE :search)
         ORDER BY status DESC, full_name ASC"
    );
    $stmt->execute([
        ':user_id' => $currentUserId,
        ':search' => '%' . $searchTerm . '%',
    ]);

    return $stmt->fetchAll();
}

function chatFetchMessages(PDO $pdo, string $currentUserId, string $otherUserId): array
{
    $stmt = $pdo->prepare(
        "SELECT m.msg_id, m.incoming_msg_id, m.outgoing_msg_id, m.msg, m.timestamp, u.profile_picture
         FROM messages m
         LEFT JOIN users u ON u.student_id = m.outgoing_msg_id
         WHERE (m.outgoing_msg_id = :current_user AND m.incoming_msg_id = :other_user)
            OR (m.outgoing_msg_id = :other_user AND m.incoming_msg_id = :current_user)
         ORDER BY m.msg_id ASC"
    );
    $stmt->execute([
        ':current_user' => $currentUserId,
        ':other_user' => $otherUserId,
    ]);

    return $stmt->fetchAll();
}

function chatFetchLatestMessage(PDO $pdo, string $currentUserId, string $otherUserId): array
{
    $stmt = $pdo->prepare(
        "SELECT msg, outgoing_msg_id
         FROM messages
         WHERE (incoming_msg_id = :other_user AND outgoing_msg_id = :current_user)
            OR (incoming_msg_id = :current_user AND outgoing_msg_id = :other_user)
         ORDER BY msg_id DESC
         LIMIT 1"
    );
    $stmt->execute([
        ':current_user' => $currentUserId,
        ':other_user' => $otherUserId,
    ]);

    $message = $stmt->fetch();

    return $message ?: ['msg' => 'No message available', 'outgoing_msg_id' => null];
}

function chatImagePath(?string $relativePath, string $prefix = '../../'): string
{
    $path = $relativePath ?: 'images/uniconnect.png';
    return $prefix . ltrim($path, '/');
}

function chatRenderUserList(PDO $pdo, array $users, string $currentUserId): string
{
    if (empty($users)) {
        return '<div class="text">No users are available to chat</div>';
    }

    $output = '';

    foreach ($users as $user) {
        $lastMessage = chatFetchLatestMessage($pdo, $currentUserId, $user['student_id']);
        $messageText = $lastMessage['msg'] ?? 'No message available';
        $messagePreview = strlen($messageText) > 28 ? substr($messageText, 0, 28) . '...' : $messageText;
        $youPrefix = ($lastMessage['outgoing_msg_id'] ?? null) === $currentUserId ? 'You: ' : '';
        $offlineClass = ($user['status'] ?? '') === 'Offline now' ? 'offline' : '';
        $hideCurrentUser = $currentUserId === $user['student_id'] ? 'hide' : '';
        $profileImagePath = chatImagePath($user['profile_picture'] ?? null);

        $output .= '<a href="chat.php?user_id=' . htmlspecialchars($user['student_id']) . '" class="' . htmlspecialchars($hideCurrentUser) . '">
                        <div class="content">
                        <img src="' . htmlspecialchars($profileImagePath) . '" alt="Profile Picture" onerror="this.src=\'../../images/uniconnect.png\';" style="object-fit: cover;">
                        <div class="details">
                            <span>' . htmlspecialchars($user['full_name']) . '</span>
                            <p>' . htmlspecialchars($youPrefix . $messagePreview) . '</p>
                        </div>
                        </div>
                        <div class="status-dot ' . htmlspecialchars($offlineClass) . '"><i class="fas fa-circle"></i></div>
                    </a>';
    }

    return $output;
}
