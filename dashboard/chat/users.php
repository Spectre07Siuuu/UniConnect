<?php
    session_start();
    include_once __DIR__ . "/php/config.php"; // Use absolute path for robustness

    // Get the logged-in user's student_id from the main session
    $outgoing_student_id = $_SESSION['user_id'] ?? null;

    // --- TEMPORARY DEBUG: Check session data in chat users file ---
    error_log("DEBUG: Admin-Dashboard/chat/users.php - Session data on entry: " . print_r($_SESSION, true));
    // --- END TEMPORARY DEBUG ---

    // --- Critical Check: If outgoing_student_id is not set, redirect to login ---
    if (!$outgoing_student_id) {
        error_log("Chat users.php: Session 'user_id' (student_id) not found. Redirecting to login.");
        header("location: ../../auth/index.php"); // Redirect to main login page
        exit();
    }

    // Sanitize the outgoing student_id for database queries to prevent SQL injection
    $outgoing_student_id_sql = mysqli_real_escape_string($conn, $outgoing_student_id);

    // --- TEMPORARY DEBUG: Log SQL query details ---
    error_log("DEBUG: users.php - Outgoing Student ID (sanitized): " . $outgoing_student_id_sql);
    $sql_current_user_query = "SELECT full_name, profile_picture, status FROM users WHERE student_id = '{$outgoing_student_id_sql}'";
    error_log("DEBUG: users.php - Query to fetch current user: " . $sql_current_user_query);
    // --- END TEMPORARY DEBUG ---

    // Fetch the current logged-in user's data from the 'users' table
    $sql_current_user_result = mysqli_query($conn, $sql_current_user_query);

    // --- TEMPORARY DEBUG: Check if mysqli_query itself returned FALSE and output direct error ---
    if ($sql_current_user_result === false) {
        error_log("ERROR: users.php (current user fetch) - mysqli_query failed! MySQL Error: " . mysqli_error($conn) . " Query: " . $sql_current_user_query);
        die("<h3>Database Query Error in Chat Users List:</h3>" .
            "<p>There was a problem fetching your user data.</p>" .
            "<p><strong>Query:</strong> <code>" . htmlspecialchars($sql_current_user_query) . "</code></p>" .
            "<p><strong>MySQL Error:</strong> <code>" . htmlspecialchars(mysqli_error($conn)) . "</code></p>" .
            "<p>Please check your database schema (`users` table for `student_id`, `full_name`, `profile_picture`, `status`) and server logs.</p>");
    }
    // --- End TEMPORARY DEBUG ---

    if(mysqli_num_rows($sql_current_user_result) > 0){
        $current_user_row = mysqli_fetch_assoc($sql_current_user_result);
        $display_name = $current_user_row['full_name'];
        $profile_img = $current_user_row['profile_picture'];
        $status_text = $current_user_row['status'];
    } else {
        error_log("Chat users.php: Logged-in student_id '{$outgoing_student_id}' not found in users table or query returned no rows. Redirecting.");
        header("location: ../../auth/index.php");
        exit();
    }
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
          <img src="../../<?php echo htmlspecialchars($profile_img); ?>" alt="Profile Picture" onerror="this.src='../../images/uniconnect.png';" style="object-fit: cover;">
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
