<?php
    session_start();
    include_once "php/config.php"; // Uses the updated chat config.php

    // Get the logged-in user's student_id from the main session for authentication
    $outgoing_student_id = $_SESSION['user_id'] ?? null; // $_SESSION['user_id'] now stores student_id

    // If student_id is not set, redirect to the main login page
    if (!$outgoing_student_id) {
        header("location: ../../auth/index.php");
        exit();
    }

    // Get the incoming user's student_id from the URL parameter
    // This is the student_id of the person the logged-in user wants to chat with
    $incoming_student_id = mysqli_real_escape_string($conn, $_GET['user_id'] ?? '');

    // If no user_id (student_id) is provided in GET, redirect back to the users list
    if (empty($incoming_student_id)) {
        header("location: users.php");
        exit();
    }

    // Fetch the incoming chat partner's data from the 'users' table
    // We need their full_name, profile_picture, and status
    $sql_incoming_user = mysqli_query($conn, "SELECT full_name, profile_picture, status FROM users WHERE student_id = '{$incoming_student_id}'");

    if(mysqli_num_rows($sql_incoming_user) > 0){
        $incoming_user_row = mysqli_fetch_assoc($sql_incoming_user);
        $chat_partner_name = $incoming_user_row['full_name'];
        // Ensure profile picture path is relative to root for display
        $chat_partner_profile_img = $incoming_user_row['profile_picture'];
        $chat_partner_status = $incoming_user_row['status']; // Initial status
    } else {
        // If incoming user not found, redirect back to the users list
        header("location: users.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Chat with <?php echo htmlspecialchars($chat_partner_name); ?> | UniConnect</title>
  <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"/>
</head>
<body>
  <div class="wrapper">
    <section class="chat-area">
      <header>
        <a href="users.php" class="back-icon"><i class="fas fa-arrow-left"></i></a>
        <!-- Ensure profile image path is relative to the root for correct display -->
        <img src="../../<?php echo htmlspecialchars($chat_partner_profile_img); ?>?t=<?php echo time(); ?>" alt="Profile Picture" onerror="this.src='../../images/uniconnect.png';" style="object-fit: cover;">
        <div class="details">
          <span><?php echo htmlspecialchars($chat_partner_name); ?></span>
          <p class="chat-partner-status"><?php echo htmlspecialchars($chat_partner_status); ?></p>
        </div>
      </header>
      <div class="chat-box">
        </div>
      <form action="#" class="typing-area">
        <input type="text" class="incoming_id" name="incoming_id" value="<?php echo htmlspecialchars($incoming_student_id); ?>" hidden>
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
        <button><i class="fab fa-telegram-plane"></i></button>
      </form>
    </section>
  </div>

  <script src="javascript/chat.js"></script>
  <script>
    // JavaScript to dynamically update chat partner's status
    document.addEventListener('DOMContentLoaded', function() {
        const chatPartnerStatusElement = document.querySelector('.chat-area header .details .chat-partner-status');
        const incomingUserId = document.querySelector('.typing-area .incoming_id').value;

        if (chatPartnerStatusElement && incomingUserId) {
            // Function to fetch and update status
            function updateChatPartnerStatus() {
                fetch(`php/get_user_status.php?user_id=${incomingUserId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            chatPartnerStatusElement.textContent = data.status;
                        } else {
                            console.error('Failed to get chat partner status:', data.message);
                            // Optionally, set a default "Unknown" or "Error" status
                            chatPartnerStatusElement.textContent = 'Unknown'; 
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching chat partner status:', error);
                        chatPartnerStatusElement.textContent = 'Disconnected'; // Indicate a network issue
                    });
            }

            // Update status initially and then every 3 seconds
            updateChatPartnerStatus(); 
            setInterval(updateChatPartnerStatus, 3000); // Poll every 3 seconds
        }
    });
  </script>

</body>
</html>
