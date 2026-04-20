document.addEventListener("DOMContentLoaded", function () {
    // Helper for HTML escaping (copied from index.php)
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

    // Helper for time ago (copied from index.php)
    function timeAgo(dateString) {
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

    // --- Notification System JS ---
    const notificationBell = document.getElementById('notificationBell');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationsList = document.getElementById('notificationsList');
    const markAllReadBtn = document.getElementById('markAllRead');

    // Get the current user's ID from a meta tag or a global JS variable if possible
    // For now, we'll try to extract it from the PHP context on each page load via a data attribute
    // or assume a global variable `currentUserStudentId` if you set one up.
    // Given current setup, PHP will still be needed to echo it to HTML for JS to read it.
    // A robust solution would involve a data attribute on the body or a div, e.g., <body data-user-id="S123">
    // For now, we'll assume it's directly accessible from the page's PHP via a hidden input or global var.
    // Since this JS is loaded on all pages, `user_student_id` needs to be fetched from a reliable source on *each* page.
    // We'll rely on a hidden input or a data attribute on the body/header in the PHP files for `user_student_id`.
    // Let's assume there's a hidden input <input type="hidden" id="currentUserId" value="PHP_USER_ID_HERE"> in each PHP file.
    const currentUserIdElement = document.getElementById('currentUserId');
    const currentUserStudentId = currentUserIdElement ? currentUserIdElement.value : null;


    // Toggle dropdown visibility
    if (notificationBell) {
        notificationBell.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent document click from immediately closing
            notificationsDropdown.style.display = notificationsDropdown.style.display === 'block' ? 'none' : 'block';
            if (notificationsDropdown.style.display === 'block') {
                // When dropdown opens, load fresh notifications
                fetchNotifications();
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (notificationsDropdown && !notificationsDropdown.contains(event.target) && event.target !== notificationBell) {
            notificationsDropdown.style.display = 'none';
        }
    });

    // Function to fetch and display notifications
    function fetchNotifications() {
        if (!currentUserStudentId) {
            console.error("Current user ID not available for notifications.");
            return;
        }
        fetch('notifications/fetch_notifications.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.unread_count);
                    renderNotifications(data.notifications);
                } else {
                    console.error('Failed to fetch notifications:', data.message);
                    notificationsList.innerHTML = '<li class="no-notifications">Error loading notifications.</li>';
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                notificationsList.innerHTML = '<li class="no-notifications">Network error.</li>';
            });
    }

    // Function to update the badge count
    function updateNotificationBadge(count) {
        if (notificationBadge) {
            if (count > 0) {
                notificationBadge.textContent = count;
                notificationBadge.style.display = 'block';
            } else {
                notificationBadge.style.display = 'none';
            }
        }
    }

    // Function to render notifications in the dropdown list
    function renderNotifications(notifications) {
        if (!notificationsList) return;

        notificationsList.innerHTML = ''; // Clear existing list

        if (notifications.length === 0) {
            notificationsList.innerHTML = '<li class="no-notifications">No notifications to show.</li>';
            return;
        }

        notifications.forEach(notif => {
            const listItem = document.createElement('li');
            listItem.className = `notification-item ${notif.is_read ? '' : 'unread'}`;
            listItem.dataset.notificationId = notif.notification_id;

            let iconClass = 'mdi-bell-outline'; // Default icon
            let senderName = notif.sender_name || 'System'; // Default sender for system notifs

            // Check if sender profile picture is available AND not self-triggered
            if (notif.sender_profile_picture && notif.sender_id !== currentUserStudentId) {
                const senderAvatarUrl = `../${htmlspecialchars(notif.sender_profile_picture)}`;
                iconClass = ''; // No MDI icon if using avatar
                listItem.innerHTML = `
                    <img src="${senderAvatarUrl}" alt="${htmlspecialchars(senderName)}" class="notif-sender-avatar" onerror="this.src='../images/uniconnect.png';" style="object-fit: cover;">
                    <div class="notif-content">
                        <div class="notif-message">${htmlspecialchars(notif.message)}</div>
                        <div class="notif-time">${timeAgo(notif.created_at)}</div>
                    </div>
                `;
            } else { // Use MDI icon
                switch(notif.type) {
                    case 'like_post': iconClass = 'mdi-thumb-up'; break;
                    case 'comment_post': iconClass = 'mdi-comment-text'; break;
                    case 'reply_comment': iconClass = 'mdi-comment-arrow-right'; break;
                    case 'new_pm': iconClass = 'mdi-message'; break;
                    case 'new_note_upload': iconClass = 'mdi-note-plus'; break;
                    case 'note_reviewed': iconClass = 'mdi-star-check'; break;
                    case 'coins_gained': iconClass = 'mdi-plus-circle-multiple-outline'; break;
                    case 'coins_lost': iconClass = 'mdi-minus-circle-multiple-outline'; break;
                    case 'reputation_gained': iconClass = 'mdi-medal'; break;
                    case 'class_reminder': iconClass = 'mdi-bell-alert'; break;
                    case 'system_announcement': iconClass = 'mdi-bullhorn'; break;
                    default: iconClass = 'mdi-bell-outline'; break;
                }

                listItem.innerHTML = `
                    <i class="mdi ${iconClass} notif-icon"></i>
                    <div class="notif-content">
                        <div class="notif-message">${htmlspecialchars(notif.message)}</div>
                        <div class="notif-time">${timeAgo(notif.created_at)}</div>
                    </div>
                `;
            }


            // Handle click on notification item
            listItem.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                markNotificationAsRead(notif.notification_id, false); // Mark this specific one as read
                notificationsDropdown.style.display = 'none'; // Close dropdown

                if (notif.link) {
                    // Handle navigation for specific content
                    if (notif.type === 'comment_post' || notif.type === 'like_post') {
                        // For comments and likes, navigate to index.php with the post ID hash
                        // Ensure the base URL is correct regardless of current page
                        const baseUrl = window.location.origin + window.location.pathname.split('dashboard/')[0] + 'dashboard/index.php';
                        window.location.href = baseUrl + '#' + notif.link.split('#')[1];

                        // Scroll to the post and potentially open comments after a slight delay
                        // This part runs ONLY if we navigate to index.php. If already on index.php, it will immediately try to scroll/open.
                        setTimeout(() => {
                            const postId = notif.link.split('#post-')[1].split('-comments')[0];
                            const targetPost = document.querySelector(`.post[data-post-id="${postId}"]`);
                            if (targetPost) {
                                targetPost.scrollIntoView({ behavior: 'smooth', block: 'start' });

                                // If it's a comment, try to open the comment section
                                if (notif.type === 'comment_post') {
                                    const commentButton = targetPost.querySelector('.comment-btn');
                                    const commentSection = targetPost.querySelector('.comment-section');
                                    if (commentButton && commentSection && commentSection.style.display === 'none') {
                                        commentButton.click(); // Simulate click to open comments
                                    }
                                }
                            }
                        }, 500); // Adjust delay if needed
                    } else if (notif.type === 'new_pm') {
                        // Direct navigation to chat
                       markNotificationAsRead(notif.notification_id, false);
                    } else {
                        // For other links (new_note_upload, coins_gained, coins_lost, reputation_gained, etc.), simple navigation
                        markNotificationAsRead(notif.notification_id, false);
                    }
                }
            });
            notificationsList.appendChild(listItem);
        });
    }

    // Function to mark notifications as read
    function markNotificationAsRead(notifId, markAll = false) {
        if (!currentUserStudentId) {
            console.error("Current user ID not available for marking notifications as read.");
            return;
        }
        const formData = new FormData();
        if (markAll) {
            formData.append('mark_all', true);
        } else {
            formData.append('notification_id', notifId);
        }

        fetch('notifications/mark_as_read.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                fetchNotifications(); // Re-fetch to update badge and list
            } else {
                console.error('Failed to mark notification as read:', data.message);
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    // Event listener for "Mark all as read" button
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent dropdown from closing immediately
            markNotificationAsRead(null, true); // Pass null for ID, true for markAll
        });
    }

    // Initial fetch and set up polling
    fetchNotifications();
    setInterval(fetchNotifications, 5000); // Poll every 5 seconds

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
