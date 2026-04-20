<?php
session_start();
require_once '../../config/db_connect.php'; // Path to root db_connect.php

header('Content-Type: text/html'); // Respond with HTML

$output_html = '';
$user_student_id = $_SESSION['user_id'] ?? null;

// Get filter parameters
$filter_subject = $_GET['subject'] ?? '';
$filter_exam_type = $_GET['exam_type'] ?? '';

// Helper function to convert timestamp to "time ago" string
function time_ago_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

try {
    $sql = "
        SELECT 
            N.note_id, 
            N.user_id AS note_poster_id,
            N.title, 
            N.description, 
            N.subject, 
            N.exam_type, 
            N.file_path, 
            N.download_count, 
            N.created_at,
            U.full_name, 
            U.profile_picture
        FROM notes AS N
        JOIN users AS U ON N.user_id = U.student_id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($filter_subject)) {
        $sql .= " AND N.subject = :subject";
        $params[':subject'] = $filter_subject;
    }
    if (!empty($filter_exam_type)) {
        $sql .= " AND N.exam_type = :exam_type";
        $params[':exam_type'] = $filter_exam_type;
    }

    $sql .= " ORDER BY N.created_at DESC";

    $stmt_notes = $pdo->prepare($sql);
    $stmt_notes->execute($params);
    $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($notes)) {
        foreach ($notes as $note) {
            $user_avatar_path = $note['profile_picture'] ? '../' . $note['profile_picture'] : '';
            $time_ago = time_ago_string($note['created_at']);
            $file_extension = pathinfo($note['file_path'], PATHINFO_EXTENSION);

            $file_icon = 'mdi-file-document-outline'; // Default icon
            switch (strtolower($file_extension)) {
                case 'pdf': $file_icon = 'mdi-file-pdf-box'; break;
                case 'doc':
                case 'docx': $file_icon = 'mdi-file-word-outline'; break;
                case 'ppt':
                case 'pptx': $file_icon = 'mdi-file-powerpoint-outline'; break;
                case 'txt': $file_icon = 'mdi-file-document-outline'; break;
                case 'zip':
                case 'rar': $file_icon = 'mdi-folder-zip-outline'; break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif': $file_icon = 'mdi-image-outline'; break;
            }

            $output_html .= '
                <div class="note-post-card">
                    <div class="post-header">
                        <div class="user-avatar">
                            <img src="' . htmlspecialchars($user_avatar_path) . '" alt="User Avatar" style="width: 4rem; height: 4rem; object-fit: cover; border-radius: 50%;" onerror="this.style.display=\'none\'"/>
                        </div>
                        <div class="post-meta">
                            <div class="meta-top">
                                <h4 class="user-name">' . htmlspecialchars($note['full_name']) . '</h4>
                                <span class="timestamp">' . $time_ago . '</span>
                            </div>
                            <div class="meta-tags">
                                <span class="subject-tag">' . htmlspecialchars($note['subject']) . '</span>
                                <span class="note-type-tag">' . htmlspecialchars(ucfirst($note['exam_type'])) . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="note-content">
                        <h3 class="note-title">' . htmlspecialchars($note['title']) . '</h3>
                        <p class="note-description">' . (empty($note['description']) ? 'No description provided.' : nl2br(htmlspecialchars($note['description']))) . '</p>
                        <div class="note-file-info">
                            <i class="mdi ' . $file_icon . '" style="margin-right: 8px;"></i>
                            <span class="file-name">' . htmlspecialchars(basename($note['file_path'])) . '</span>
                            <span class="download-count">(' . htmlspecialchars($note['download_count']) . ' downloads)</span>
                        </div>
                    </div>
                    <div class="note-actions">
                        <button class="action-btn message-note-btn" data-note-poster-id="' . htmlspecialchars($note['note_poster_id']) . '">
                            <i class="mdi mdi-message-outline"></i> Message
                        </button>
                        <button class="action-btn download-btn" data-note-id="' . htmlspecialchars($note['note_id']) . '" data-file-path="' . htmlspecialchars($note['file_path']) . '" data-note-poster-id="' . htmlspecialchars($note['note_poster_id']) . '">
                            <i class="mdi mdi-download"></i> Download
                        </button>
                        <button class="action-btn review-note-btn" data-note-id="' . htmlspecialchars($note['note_id']) . '" data-note-poster-id="' . htmlspecialchars($note['note_poster_id']) . '">
                            <i class="mdi mdi-star-outline"></i> Review
                        </button>
                    </div>
                </div>';
        }
    } else {
        $output_html = '<p class="no-notes-message">No notes available matching your criteria.</p>';
    }

} catch (PDOException $e) {
    error_log("Error fetching notes: " . $e->getMessage());
    $output_html = '<p class="message-error">Error loading notes. Please try again later. (DB Error)</p>';
}

echo $output_html;
exit();
