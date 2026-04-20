<?php
// Start session at the beginning of the file
session_start();

// Include the database connection
// This db_connect.php (the new one from Step 2.1) will ensure tables are set up.
require __DIR__ . '/../config/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uniconnect - Login & Signup</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1A73E8;
            --black: #000000;
            --white: #DFECF2;
            --transition: 0.5s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border;
            font-family: 'Montserrat', sans-serif;
        }

        body, html {
            height: 100%;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
        }

    .container {
    background: var(--white);
    width: 900px;
    min-height: 550px; /* Changed from fixed 'height' to 'min-height' */
    height: auto; /* Let the height adapt to content */
    border-radius: 20px;
    box-shadow: 0px 0px 30px rgba(0,0,0,0.2);
    display: flex;
    overflow: visible; /* Changed from 'hidden' to 'visible' to prevent cropping */
    position: relative;
}

/* Also, ensure this universal box-sizing rule is correct (it was 'border' previously) */
/* This is fundamental and should be at the very top of your CSS */
*, *::before, *::after {
    box-sizing: border-box;
}

/* If the form-area or form-section also have fixed heights or 'hidden' overflows,
   you might need to adjust them as well, but starting with .container is key. */
/* For example, if forms are still cropped inside, check these: */
.form-section {
    /* ... other existing properties ... */
    height: auto; /* Ensure form sections can also grow */
    overflow: visible; /* Allow content to flow out if needed */
    /* If content is still centered and pushing off screen, consider: */
    /* justify-content: flex-start; */ /* Aligns content to the top instead of center */
}

        .left-panel {
            background: var(--black);
            width: 40%;
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
        }

        .left-panel img {
            width: 80px;
            margin-bottom: 20px;
            animation: fadeSlide 1s forwards;
        }

        .left-panel h1 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 400;
        }

        .left-panel h1 span {
            color: var(--primary);
            font-weight: 800;
        }

        @keyframes fadeSlide {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-area {
            width: 60%;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .forms-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            position: relative;
        }

        .form-section {
            width: 100%;
            height: 100%;
            padding: 20px;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .form-section.active {
            display: flex;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--black);
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 300px;
        }

        input, select {
            padding: 12px 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }

        select {
            background-color: white;
            color: #333;
        }

        button {
            background: var(--black);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background var(--transition);
            position: relative;
            overflow: hidden;
        }

        button.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 3px solid white;
            border-top: 3px solid transparent;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        button:hover {
            background: var(--primary);
        }

        .toggle-link {
            margin-top: 10px;
            font-size: 14px;
            text-align: center;
        }

        .toggle-link a {
            color: var(--primary);
            text-decoration: none;
            cursor: pointer;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="left-panel">
        <img src="../images/uniconnect.png" alt="Uniconnect Logo" style="width: 120px; height: auto; margin: 10px 0;">
        <h1>Welcome to <span>Uniconnect</span></h1>
    </div>

    <div class="form-area">
        <div class="forms-wrapper">
            <div class="form-section active" id="login-section">
                <h2>Login</h2>
                <?php
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                    unset($_SESSION['login_error']);
                }
                if (isset($_SESSION['signup_success'])) {
                    echo '<div class="success-message">Signup successful! Please login.</div>';
                    unset($_SESSION['signup_success']);
                }
                // Optional debug info, can be removed in production
                if (isset($_SESSION['debug_info'])) {
                    echo '<div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">';
                    echo $_SESSION['debug_info'];
                    echo '</div>';
                    unset($_SESSION['debug_info']);
                }
                ?>
                <form id="login-form" action="login.php" method="POST">
                    <input type="text" name="student_id" placeholder="Student ID" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" id="login-button">Login</button>
                    <div class="toggle-link">Don't have an account? <a onclick="showSignup()">Sign Up</a></div>
                </form>
            </div>

            <div class="form-section" id="signup-section">
                <h2>Sign Up</h2>
                <form id="signup-form" action="signup.php" method="POST">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="text" name="student_id" placeholder="Student ID" required>
                    <input type="email" name="email" placeholder="University Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <select name="department" required>
                        <option value="">Select Department</option>
                        <option>CSE</option>
                        <option>BBA</option>
                        <option>EEE</option>
                        <option>BSDS</option>
                        <option>EDS</option>
                        <option>MSJB</option>
                        <option>PHARM</option>
                        <option>BGE</option>
                    </select>
                    <button type="submit" id="signup-button">Sign Up</button>
                    <div class="toggle-link">Already have an account? <a onclick="showLogin()">Login</a></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function showSignup() {
        document.getElementById('login-section').classList.remove('active');
        document.getElementById('signup-section').classList.add('active');
    }

    function showLogin() {
        document.getElementById('signup-section').classList.remove('active');
        document.getElementById('login-section').classList.add('active');
    }

    // Add loading state to buttons
    document.getElementById('login-form').addEventListener('submit', function() {
        document.getElementById('login-button').classList.add('loading');
    });

    document.getElementById('signup-form').addEventListener('submit', function() {
        document.getElementById('signup-button').classList.add('loading');
    });

    // The DOMContentLoaded listener block at the end of friend's index.php was for routine buttons
    // which are not part of the login/signup page. It has been removed here to keep it clean.
    // If you need that specific functionality on this index.php, let me know.
</script>

</body>
</html>
