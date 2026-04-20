<?php
// Include authentication check to ensure user is logged in
require_once 'check_auth.php'; // Uses the updated check_auth.php
require_once '../config/db_connect.php'; // Uses the updated db_connect.php

// Get user's student_id from session (now $_SESSION['user_id'] holds student_id)
$user_student_id = $_SESSION['user_id'] ?? null;
// Use full_name directly from session (populated in login.php)
$full_name = $_SESSION['full_name'] ?? 'User';

// Fetch user data including coins, reputation, and profile_picture from the 'users' table
try {
    $stmt = $pdo->prepare("SELECT coin_balance, reputation_points, profile_picture FROM users WHERE student_id = ?");
    $stmt->execute([$user_student_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $coins = $user_data['coin_balance'] ?? 0;
    $reputation = $user_data['reputation_points'] ?? 0;
    // profile_picture now comes directly from the 'users' table. Path is relative to project root.
    // So, from Admin-Dashboard/, it needs '../' prefix.
    $profile_picture = $user_data['profile_picture'] ? '../' . $user_data['profile_picture'] : '../images/uniconnect.png';
} catch (PDOException $e) {
    error_log("Error fetching user data for academic tools header: " . $e->getMessage());
    $coins = 0;
    $reputation = 0;
    $profile_picture = '../images/uniconnect.png'; // Fallback
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academic Tools - UniConnect</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/layout.css" />
    <link rel="stylesheet" href="css/header_sidebar.css" />
    <link rel="stylesheet" href="css/academic_tools.css" />
    <link rel="stylesheet" href="css/calculators.css" />
    <link rel="stylesheet" href="css/modals.css" />
    <link rel="stylesheet" href="css/responsive.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css"
      rel="stylesheet"
    />
  </head>

  <body>
    <input type="hidden" id="currentUserId" value="<?php echo htmlspecialchars($user_student_id); ?>">

    <div class="container">
      <div class="sidebar">
        <nav>
          <div class="menu">
            <a href="#">
              <img
                src="../images/uniconnect.png"
                alt="Uniconnect Logo"
                style="width: 120px; height: auto; margin: 10px 0;"
              />
            </a>
          </div>

          <div class="menu-general">
            <a href="profile.php" class="sidebar-item"
              ><i class="mdi mdi-card-account-details"></i>
              <span>Profile</span></a
            >
            <a href="index.php" class="sidebar-item"
              ><i class="mdi mdi-home"></i> <span>Home</span></a
            >
            <?php /* Removed Study Group link */ ?>
            <a href="notes.php" class="sidebar-item"
              ><i class="mdi mdi-note-text-outline"></i> <span>Notes</span></a
            >
            <a href="academic_tools.php" class="sidebar-item active"
              ><i class="mdi mdi-tools"></i> <span>Academic Tools</span></a
            >
            <a href="../auth/logout.php" class="sidebar-item"
              ><i class="mdi mdi-logout"></i> <span>Logout</span></a
            >
          </div>
          </nav>
      </div>

      <div class="content">
        <div class="header">
          <div class="header-item1">
            <div class="search-bar">
              <h3>Academic Tools</h3>
            </div>
            <div class="notification">
              <div class="coin-reputation">
                <span class="coin-balance">
                  <i class="mdi mdi-currency-usd"></i>
                  <?php echo htmlspecialchars($coins); ?>
                </span>
                </div>
              <a href="chat/users.php" class="nav-icon chat-icon-button" title="Open Chat">
                  <i class="mdi mdi-message-outline"></i>
              </a>
              <div class="notification-bell-container">
                  <i class="mdi mdi-bell-ring-outline nav-icon" id="notificationBell"></i>
                  <span class="notification-badge" id="notificationBadge">0</span>
                  <div class="notifications-dropdown" id="notificationsDropdown">
                      <h4>Notifications <span class="mark-all-read" id="markAllRead">Mark all as read</span></h4>
                      <ul class="notifications-list" id="notificationsList">
                          <li class="no-notifications">No notifications to show.</li>
                      </ul>
                  </div>
              </div>
              <img
                src="<?php echo htmlspecialchars($profile_picture); ?>"
                alt="Profile"
                class="nav-icon profile-img"
                onerror="this.src='../images/uniconnect.png'; this.style.padding='2px';"
              />
            </div>
          </div>
        </div>

        <div class="content-main">
          <div id="academic-tools-display-area" class="academic-tools-grid">
            <div class="tool-block" data-tool="cgpa-calculator">
              <i class="mdi mdi-calculator"></i>
              <h3>CGPA Calculator</h3>
            </div>
            <div class="tool-block" data-tool="tuition-fee-calculator">
              <i class="mdi mdi-cash-multiple"></i>
              <h3>Tuition Fee Calculator</h3>
            </div>
          </div>

          <div class="coming-soon-message">
            <p>More exciting features coming soon!</p>
          </div>
        </div>
        </div>
      </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const displayArea = document.getElementById('academic-tools-display-area');
        const toolBlocks = document.querySelectorAll('.tool-block');
        const comingSoonMessage = document.querySelector('.coming-soon-message');

        toolBlocks.forEach(block => {
            block.addEventListener('click', function() {
                const tool = this.dataset.tool; // Get the data-tool attribute value
                if (tool === 'cgpa-calculator') {
                    loadToolContent('academic_modules/cgpa_calculator.php');
                } else if (tool === 'tuition-fee-calculator') {
                    loadToolContent('academic_modules/tuition_fee_calculator.php');
                } else {
                    alert('This tool is not yet implemented.');
                }
            });
        });

        function loadToolContent(url) {
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.text();
                })
                .then(html => {
                    displayArea.innerHTML = html; // Inject the content
                    // Remove academic-tools-grid class if calculator needs full width or different layout
                    displayArea.classList.remove('academic-tools-grid');

                    // Re-run any scripts included in the loaded HTML
                    // This is important because dynamically loaded HTML's script tags don't execute automatically.
                    const scripts = displayArea.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        Array.from(script.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        newScript.textContent = script.textContent;
                        script.parentNode.replaceChild(newScript, script);
                    });

                    // Hide the "Coming Soon" message after a tool is loaded
                    if (comingSoonMessage) {
                        comingSoonMessage.style.display = 'none';
                    }

                    // Adjust header title
                    const headerTitle = document.querySelector('.search-bar h3');
                    if (headerTitle) {
                        if (url.includes('cgpa')) {
                            headerTitle.textContent = 'CGPA Calculator';
                        } else if (url.includes('tuition')) {
                            headerTitle.textContent = 'Tuition Fee Calculator';
                        } else {
                            headerTitle.textContent = 'Academic Tools';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading academic tool:', error);
                    displayArea.innerHTML = '<p style="color: red;">Failed to load tool. Please try again.</p>';
                });
        }
    });
    </script>
    <script src="javascript/notifications.js"></script>
  </body>
</html>
