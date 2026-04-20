<?php
// Include authentication check to ensure user is logged in
require_once 'check_auth.php';
// Include database connection to fetch user info for header (coins, reputation, profile_picture)
require_once '../config/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

// Get user's student_id from session (now $_SESSION['user_id'] holds student_id)
$user_student_id = $_SESSION['user_id'] ?? null;
// Use full_name directly from session (populated in login.php)
$full_name = $_SESSION['full_name'] ?? 'User';

// Get current date for the dynamic calendar
$current_day_name = date('l'); // Full day name, e.g., "Wednesday"
$current_date_formatted = date('d F, Y'); // Day, Full Month Name, Year, e.g., "25 June, 2025"

// Function to generate a monthly calendar HTML
function generate_calendar($month, $year) {
    $date_string = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $timestamp = strtotime($date_string);
    $num_days = date('t', $timestamp); // Number of days in the month
    $first_day_of_week = date('N', $timestamp); // 1 (for Monday) through 7 (for Sunday)

    // Get today's date components for highlighting
    $today_day = date('j');
    $today_month = date('n');
    $today_year = date('Y');

    $calendar = '<div class="dynamic-calendar">';
    $calendar .= '<h4>' . date('F Y', $timestamp) . '</h4>'; // Month Year
    $calendar .= '<div class="calendar-header">';
    $calendar .= '<div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>';
    $calendar .= '</div><div class="calendar-days">';

    // Fill leading empty days
    for ($i = 1; $i < $first_day_of_week; $i++) {
        $calendar .= '<div></div>';
    }

    // Fill days of the month
    for ($day = 1; $day <= $num_days; $day++) {
        $full_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT); // FormatYYYY-MM-DD
        $class = 'calendar-day-selectable'; // Add selectable class

        if ($day == $today_day && $month == $today_month && $year == $today_year) {
            $class .= ' today selected'; // Highlight today's date and mark it as initially selected
        }
        $calendar .= '<div class="' . $class . '" data-date="' . htmlspecialchars($full_date) . '">' . $day . '</div>';
    }

    $calendar .= '</div></div>';
    return $calendar;
}

// Get current month and year for calendar
$current_month = date('n');
$current_year = date('Y');
$monthly_calendar_html = generate_calendar($current_month, $current_year);


$shell_data = getDashboardShellData($pdo, $user_student_id);
$coins = $shell_data['coins'];
$profile_picture = $shell_data['profile_picture'];

// Get current day of the week (e.g., 'monday', 'tuesday') for routine lookup
$current_day_of_week_routine = strtolower(date('l'));

$class_routine_html = ''; // Initialize HTML output for the routine

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

        // 2. Fetch routine entries for these courses for the current day
        // Routine is now global, so no R.user_id filter.
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
            ':day_of_week' => $current_day_of_week_routine
        ]);
        $routine_entries = $stmt_routine->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($routine_entries)) {
            foreach ($routine_entries as $entry) {
                $subject_class = 'subject-' . strtolower(str_replace(' ', '-', $entry['course_name']));

                $class_routine_html .= '
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
            $class_routine_html = '<p class="no-routine-message">No classes scheduled for ' . htmlspecialchars($current_day_of_week_routine) . '.</p>';
        }
    } else {
        $class_routine_html = '<p class="no-routine-message">Please add your courses in the <a href="profile.php">Profile</a> section to see your routine.</p>';
    }
} catch (PDOException $e) {
    error_log("Error fetching class routine for user " . $user_student_id . ": " . $e->getMessage());
    $class_routine_html = '<p class="error-message">Error loading routine. Please try again later. (DB Error)</p>';
}

?>
<?php renderDashboardHead('Dashboard', [
    'css/dashboard_widgets.css',
    'css/posts.css',
    'css/profile.css',
    'css/modals.css',
    'css/academic_tools.css',
    'css/calculators.css',
    'css/responsive.css',
]); ?>
<?php renderDashboardShellStart('home', 'Hello, <strong>' . htmlspecialchars($full_name) . '</strong>', $coins, $profile_picture, $user_student_id); ?>

        <div class="content-main">
          <div class="content-left">
            <div class="create-post">
              <form id="create-post-form" method="POST" enctype="multipart/form-data">
                <div class="post-input">
                  <div class="post-input-content">
                    <textarea
                      id="post-text-content" name="post_text"
                      placeholder="What's on your mind?"
                      aria-label="Create a post" required
                    ></textarea>
                    <div class="post-options">
                      <select id="post-category" name="category" aria-label="Select post category" required>
                        <option value="general" selected>General</option>
                        <option value="academic">Academic</option>
                        <option value="buy-sell">Buy & Sell</option>
                        <option value="lost-found">Lost & Found</option>
                      </select>
                      <label class="photo-upload new-photo-upload-btn">
                        <i class="mdi mdi-image"></i>
                        <span>Photo</span>
                        <input
                          type="file" id="post-image-upload" name="post_image"
                          accept="image/*"
                          aria-label="Upload photo"
                        />
                      </label>
                      <button type="submit" id="post-submit-button" class="post-button">Post</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>

            <div class="posts-feed">
              <h3>Recent Posts</h3>
              <p class="no-recent-posts-message" style="text-align: center; color: var(--text-secondary); margin-top: 20px; font-size: 1.6rem;">No recent posts available. Be the first to post!</p>
            </div>
          </div>

          <div class="right-side-container">
            <div class="dynamic-monthly-calendar-block">
                <?php echo $monthly_calendar_html; ?>
            </div>

            <div class="class-routine">
              <h3>Your Class Routine</h3>
              <div class="class-list">
                <?php echo $class_routine_html; ?>
              </div>
            </div>
          </div>
        </div>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // --- Like button interactions (re-attached dynamically) ---
        function handleLikeButtonClick(event) {
          event.preventDefault();

          const button = event.currentTarget;
          const postElement = button.closest('.post');
          const postId = postElement ? postElement.dataset.postId : null;

          const likesCountSpan = button.querySelector('.likes-count');

          if (!postId) {
            console.error('Post ID not found for like button.');
            return;
          }

          let action;
          if (button.classList.contains("liked")) {
            action = 'unlike';
          } else {
            action = 'like';
          }

          const originalButtonHtml = button.innerHTML;
          button.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i>';
          button.disabled = true;

          const formData = new FormData();
          formData.append('post_id', postId);
          formData.append('action', action);

          fetch('posts/process_like.php', {
              method: 'POST',
              body: formData
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                if (action === 'like') {
                  button.classList.add("liked");
                  button.innerHTML = '<i class="mdi mdi-thumb-up"></i> Liked <span class="likes-count">' + data.likes_count + '</span>';
                } else {
                  button.classList.remove("liked");
                  button.innerHTML = '<i class="mdi mdi-thumb-up-outline"></i> Like <span class="likes-count">' + data.likes_count + '</span>';
                }
                // Also fetch notifications if like was successful and possibly caused one
                // fetchNotifications(); // This is handled by notifications.js now
              } else {
                alert('Error: ' + (data.message || 'Could not process like/unlike.'));
                button.innerHTML = originalButtonHtml;
              }
            })
            .catch(error => {
              console.error('Error processing like/unlike:', error);
              alert('An error occurred while processing your request.');
              button.innerHTML = originalButtonHtml;
            })
            .finally(() => {
              button.disabled = false;
            });
        }

        // --- Comment button interactions ---
        function handleCommentButtonClick(event) {
          event.preventDefault();
          const button = event.currentTarget;
          const postElement = button.closest('.post');
          const postId = postElement ? postElement.dataset.postId : null;
          const commentSection = postElement.querySelector('.comment-section');
          const commentsListDiv = postElement.querySelector('.comments-list');

          if (!postId || !commentSection || !commentsListDiv) {
            console.error('Missing elements for comment functionality.');
            return;
          }

          // Toggle visibility
          if (commentSection.style.display === 'none') {
            commentSection.style.display = 'block';
            loadComments(postId, commentsListDiv);
          } else {
            commentSection.style.display = 'none';
          }
        }

        function loadComments(postId, commentsListDiv) {
          commentsListDiv.innerHTML = '<div class="comments-loading" style="text-align: center; padding: 10px; color: var(--text-secondary);"><i class="mdi mdi-loading mdi-spin"></i> Loading comments...</div>';

          fetch(`posts/fetch_comments.php?post_id=${postId}`)
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
              }
              return response.text();
            })
            .then(html => {
              commentsListDiv.innerHTML = html;
              if (commentsListDiv.innerHTML.trim() === '') {
                 commentsListDiv.innerHTML = '<div class="no-comments-message" style="text-align: center; color: var(--text-secondary); margin-top: 10px; font-size: 1.4rem;">No comments yet.</div>';
              }
              // Re-attach auto-resize for new comment textareas if they are dynamically created
              attachTextareaAutoResize(commentsListDiv.querySelectorAll('.comment-textarea'));
            })
            .catch(error => {
              console.error('Error fetching comments:', error);
              commentsListDiv.innerHTML = '<p class="error-message" style="color: red; text-align: center; margin-top: 10px; font-size: 1.4rem;">Failed to load comments.</p>';
            });
        }

        function postNewComment(event) {
          const button = event.currentTarget;
          const postElement = button.closest('.post');
          const postId = postElement ? postElement.dataset.postId : null;
          const commentTextarea = postElement.querySelector('.comment-textarea');
          const commentsListDiv = postElement.querySelector('.comments-list');
          const commentCountSpan = postElement.querySelector('.comment-btn .comments-count');


          const commentText = commentTextarea ? commentTextarea.value.trim() : '';

          if (!postId || !commentText) {
            alert('Comment cannot be empty.');
            return;
          }

          const originalButtonHtml = button.innerHTML;
          button.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i>';
          button.disabled = true;
          commentTextarea.disabled = true;

          const formData = new FormData();
          formData.append('post_id', postId);
          formData.append('comment_text', commentText);

          fetch('posts/process_comment.php', {
              method: 'POST',
              body: formData
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                commentTextarea.value = '';
                // Reset textarea height after clearing
                commentTextarea.style.height = 'auto';
                loadComments(postId, commentsListDiv);
                if (commentCountSpan && data.comments_count !== undefined) {
                    commentCountSpan.textContent = data.comments_count;
                }
                // fetchNotifications(); // This is handled by notifications.js now
              } else {
                alert('Error posting comment: ' + (data.message || 'Unknown error'));
              }
            })
            .catch(error => {
              console.error('Error posting comment:', error);
              alert('An error occurred while posting your comment.');
            })
            .finally(() => {
              button.innerHTML = originalButtonHtml;
              button.disabled = false;
              commentTextarea.disabled = false;
            });
        }

        // Universal textarea auto-resize function
        function attachTextareaAutoResize(textareas) {
           textareas.forEach(textarea => {
               textarea.style.height = 'auto';
               textarea.style.height = (textarea.scrollHeight) + 'px';
               textarea.addEventListener('input', function() {
                   this.style.height = 'auto';
                   this.style.height = (this.scrollHeight) + 'px';
               });
           });
        }

        // Function to attach all event listeners for dynamically loaded posts
        function attachPostEventListeners() {
          const likeButtons = document.querySelectorAll(".action-btn.like-btn");
          likeButtons.forEach((button) => {
            button.removeEventListener('click', handleLikeButtonClick);
            button.addEventListener('click', handleLikeButtonClick);
          });

          const commentButtons = document.querySelectorAll(".action-btn.comment-btn");
          commentButtons.forEach((button) => {
            button.removeEventListener('click', handleCommentButtonClick);
            button.addEventListener('click', handleCommentButtonClick);
          });

          const postCommentButtons = document.querySelectorAll('.post-comment-btn');
          postCommentButtons.forEach(button => {
            button.removeEventListener('click', postNewComment);
            button.addEventListener('click', postNewComment);
          });

           // Auto-resize comment textareas for newly loaded content
           attachTextareaAutoResize(document.querySelectorAll('.comment-section .comment-textarea'));
        }


        // --- Post Creation Form Submission ---
        const createPostForm = document.getElementById('create-post-form');
        const postTextInput = document.getElementById('post-text-content');
        const postCategorySelect = document.getElementById('post-category');
        const postImageUpload = document.getElementById('post-image-upload');
        const postSubmitButton = document.getElementById('post-submit-button');
        const postsFeedDiv = document.querySelector('.posts-feed');

        if (createPostForm && postTextInput && postCategorySelect && postSubmitButton && postsFeedDiv) {
            createPostForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                const originalButtonText = postSubmitButton.innerHTML;
                postSubmitButton.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Posting...';
                postSubmitButton.disabled = true;

                fetch('posts/process_post.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        postTextInput.value = '';
                        postCategorySelect.value = 'general';
                        postImageUpload.value = '';

                        loadRecentPosts();

                    } else {
                        alert('Error creating post: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error submitting post:', error);
                    alert('An error occurred while submitting your post.');
                })
                .finally(() => {
                    postSubmitButton.innerHTML = originalButtonText;
                    postSubmitButton.disabled = false;
                });
            });
        }

        // Helper for HTML escaping (moved to notifications.js for notifications system, but kept here for posts related JS)
        function htmlspecialchars(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Helper for time ago (moved to notifications.js for notifications system, but kept here for posts related JS)
        function timeAgo(dateString) { // Kept a local copy for posts display
            const date = new Date(dateString); // Convert string to Date object
            const seconds = Math.floor((new Date() - date) / 1000);
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            return Math.floor(seconds) + " seconds ago";
        }


        const calendarDays = document.querySelectorAll('.dynamic-calendar .calendar-days div[data-date]');
        const classListDiv = document.querySelector('.class-routine .class-list');
        const classRoutineHeader = document.querySelector('.class-routine h3');

        if (calendarDays.length > 0 && classListDiv && classRoutineHeader) {
            calendarDays.forEach(dayElement => {
                dayElement.addEventListener('click', function() {
                    calendarDays.forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');

                    const selectedDate = this.dataset.date;
                    fetchRoutineForDate(selectedDate);
                });
            });

            const todayElement = document.querySelector('.dynamic-calendar .calendar-days div.today');
            if (todayElement && !todayElement.classList.contains('selected')) {
                todayElement.click();
            } else if (todayElement && todayElement.classList.contains('selected')) {
                fetchRoutineForDate(todayElement.dataset.date);
            }
        }

        function fetchRoutineForDate(dateString) {
            classListDiv.innerHTML = '<div class="routine-loading"><i class="mdi mdi-loading mdi-spin"></i> Loading routine...</div>';

            const dateObj = new Date(dateString);
            const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const formattedDate = dateObj.toLocaleDateString('en-US', options);
            classRoutineHeader.textContent = `Routine for ${formattedDate}`;

            fetch(`academic_modules/get_daily_routine.php?date=${dateString}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.text();
                })
                .then(html => {
                    classListDiv.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching daily routine:', error);
                    classListDiv.innerHTML = '<p class="error-message">Failed to load routine for this date.</p>';
                });
        }

        function loadRecentPosts() {
            if (postsFeedDiv) {
                postsFeedDiv.innerHTML = `
                    <h3>Recent Posts</h3>
                    <div class="posts-loading" style="text-align: center; padding: 20px; color: var(--text-secondary);">
                        <i class="mdi mdi-loading mdi-spin"></i> Loading posts...
                    </div>
                `;
            }
            fetch('posts/fetch_posts.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.text();
                })
                .then(html => {
                    if (postsFeedDiv) {
                        postsFeedDiv.innerHTML = html;
                        attachPostEventListeners();
                    }
                })
                .catch(error => {
                    console.error('Error fetching posts:', error);
                    if (postsFeedDiv) {
                        postsFeedDiv.innerHTML = `
                            <h3>Recent Posts</h3>
                            <p class="error-message" style="color: red; text-align: center; margin-top: 20px; font-size: 1.6rem;">Failed to load posts.</p>
                        `;
                    }
                });
        }

        // Initial load of posts
        loadRecentPosts();

        // Handle URL fragment for direct post/comment access on page load
        // This is for when the page is loaded directly with a hash in the URL
        if (window.location.hash) {
            const hash = window.location.hash;
            // Check if hash points to a post or a post with comments
            if (hash.startsWith('#post-')) {
                const postId = hash.split('#post-')[1].split('-comments')[0]; // Extract post ID
                const targetPost = document.querySelector(`.post[data-post-id="${postId}"]`);
                if (targetPost) {
                    // Scroll to the post
                    targetPost.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    // If the hash includes '-comments', open the comment section
                    if (hash.includes('-comments')) {
                        const commentButton = targetPost.querySelector('.comment-btn');
                        const commentSection = targetPost.querySelector('.comment-section');
                        if (commentButton && commentSection && commentSection.style.display === 'none') {
                            // Use setTimeout to ensure DOM is ready and scroll has occurred
                            setTimeout(() => {
                                commentButton.click(); // Simulate click to open comments
                            }, 300); // Small delay
                        }
                    }
                }
            }
        }
      });
    </script>
<?php renderDashboardShellEnd(); ?>
