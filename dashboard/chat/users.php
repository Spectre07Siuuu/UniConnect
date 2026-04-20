<?php
    require_once __DIR__ . "/php/config.php";

    $outgoing_student_id = chatCurrentUserId();

    if (!$outgoing_student_id) {
        error_log("Chat users.php: Session 'user_id' (student_id) not found. Redirecting to login.");
        redirectTo('../../auth/index.php');
    }

    $current_user_row = chatFetchUser($pdo, $outgoing_student_id);
    if (!$current_user_row) {
        error_log("Chat users.php: Logged-in student_id '{$outgoing_student_id}' not found in users table or query returned no rows. Redirecting.");
        redirectTo('../../auth/index.php');
    }

    $display_name = $current_user_row['full_name'];
    $profile_img = $current_user_row['profile_picture'];
    $status_text = $current_user_row['status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Users | UniConnect Chat</title>
  <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"/>
</head>
<body>
  <div class="wrapper">
    <section class="users">
      <header>
        <div class="content">
          <img src="<?php echo htmlspecialchars(chatImagePath($profile_img)); ?>" alt="Profile Picture" onerror="this.src='../../images/uniconnect.png';" style="object-fit: cover;">
          <div class="details">
            <span><?php echo htmlspecialchars($display_name); ?></span>
            <p><?php echo htmlspecialchars($status_text); ?></p>
          </div>
        </div>
        <a href="../../dashboard/index.php" class="go-back-dashboard-btn">Go Back</a>
      </header>
      <div class="search">
        <span class="text">Select an user to start chat</span>
        <input type="text" placeholder="Enter name to search...">
        <button><i class="fas fa-search"></i></button>
      </div>
      <div class="users-list">
        </div>
    </section>
  </div>

  <script src="javascript/users.js"></script>

</body>
</html>
