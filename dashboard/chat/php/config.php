<?php
// Include the main system's database connection setup (which ensures the database and tables exist via PDO)
// This file is located at the root of your project: uniconnect/db_connect.php
// While db_connect.php sets up a PDO connection, this chat system uses mysqli_* functions,
// so we establish a separate mysqli connection here with the same parameters.
require_once __DIR__ . '/../../../config/db_connect.php';

// Define MySQLi connection details, using the same credentials as your PDO connection
$hostname = 'localhost'; // Usually 'localhost'
$username = 'root';      // Your MySQL username
$password = '';          // Your MySQL password
$dbname = 'uniconnect';  // Your unified database name (must match db_connect.php)

// Establish a new mysqli connection specifically for the chat system's scripts
$conn = mysqli_connect($hostname, $username, $password, $dbname);

// Check if the mysqli connection was successful
if (!$conn) {
    // Log the error for debugging purposes (check your Apache/PHP error logs)
    error_log("Chat system mysqli connection error: " . mysqli_connect_error());
    // Display a user-friendly error message and terminate script execution
    die("Database connection failed for chat. Please try again later.");
}

// ... (existing code) ...

if (!$conn) {
    error_log("Chat system mysqli connection error: " . mysqli_connect_error());
    die("Database connection failed for chat. Please try again later.");
} else {
    error_log("DEBUG: Chat mysqli connection to $dbname successful!"); // <-- THIS LINE
}
?>
