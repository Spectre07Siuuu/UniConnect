<?php

function getDashboardShellData(PDO $pdo, ?string $userStudentId): array
{
    $shell = [
        'coins' => 0,
        'reputation' => 0,
        'profile_picture' => '../images/uniconnect.png',
    ];

    if (!$userStudentId) {
        return $shell;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT coin_balance, reputation_points, profile_picture FROM users WHERE student_id = ?"
        );
        $stmt->execute([$userStudentId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $shell['coins'] = $userData['coin_balance'] ?? 0;
            $shell['reputation'] = $userData['reputation_points'] ?? 0;
            $shell['profile_picture'] = !empty($userData['profile_picture'])
                ? '../' . $userData['profile_picture']
                : '../images/uniconnect.png';
        }
    } catch (PDOException $e) {
        error_log('Dashboard shell data error: ' . $e->getMessage());
    }

    return $shell;
}

function renderDashboardHead(string $title, array $pageStyles = []): void
{
    $baseStyles = [
        'css/global.css',
        'css/layout.css',
        'css/header_sidebar.css',
    ];
    $styles = array_values(array_unique(array_merge($baseStyles, $pageStyles)));
    ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($title); ?></title>
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($style); ?>" />
<?php endforeach; ?>
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
<?php
}

function renderDashboardSidebar(string $activePage): void
{
    $navItems = [
        'profile' => ['href' => 'profile.php', 'icon' => 'mdi-card-account-details', 'label' => 'Profile'],
        'home' => ['href' => 'index.php', 'icon' => 'mdi-home', 'label' => 'Home'],
        'notes' => ['href' => 'notes.php', 'icon' => 'mdi-note-text-outline', 'label' => 'Notes'],
        'academic_tools' => ['href' => 'academic_tools.php', 'icon' => 'mdi-tools', 'label' => 'Academic Tools'],
    ];
    ?>
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
<?php foreach ($navItems as $key => $item): ?>
            <a
              href="<?php echo htmlspecialchars($item['href']); ?>"
              class="sidebar-item<?php echo $activePage === $key ? ' active' : ''; ?>"
            >
              <i class="mdi <?php echo htmlspecialchars($item['icon']); ?>"></i>
              <span><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
<?php endforeach; ?>
            <a href="../auth/logout.php" class="sidebar-item">
              <i class="mdi mdi-logout"></i>
              <span>Logout</span>
            </a>
          </div>
        </nav>
      </div>
<?php
}

function renderDashboardHeader(string $titleHtml, int $coins, string $profilePicture): void
{
    ?>
        <div class="header">
          <div class="header-item1">
            <div class="search-bar">
              <h3><?php echo $titleHtml; ?></h3>
            </div>
            <div class="notification">
              <div class="coin-reputation">
                <span class="coin-balance">
                  <i class="mdi mdi-currency-usd"></i>
                  <?php echo htmlspecialchars((string) $coins); ?>
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
                src="<?php echo htmlspecialchars($profilePicture); ?>"
                alt="Profile"
                class="nav-icon profile-img"
                onerror="this.src='../images/uniconnect.png'; this.style.padding='2px';"
              />
            </div>
          </div>
        </div>
<?php
}

function renderDashboardShellStart(
    string $activePage,
    string $titleHtml,
    int $coins,
    string $profilePicture,
    ?string $userStudentId
): void {
    ?>
  <body>
    <input type="hidden" id="currentUserId" value="<?php echo htmlspecialchars((string) $userStudentId); ?>">

    <div class="container">
<?php renderDashboardSidebar($activePage); ?>

      <div class="content">
<?php renderDashboardHeader($titleHtml, $coins, $profilePicture); ?>
<?php
}

function renderDashboardShellEnd(array $pageScripts = []): void
{
    $scripts = array_values(array_unique(array_merge($pageScripts, ['javascript/notifications.js'])));
    ?>
      </div>
    </div>
<?php foreach ($scripts as $script): ?>
    <script src="<?php echo htmlspecialchars($script); ?>"></script>
<?php endforeach; ?>
  </body>
</html>
<?php
}
