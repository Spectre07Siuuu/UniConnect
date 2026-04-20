<?php
// Database connection settings
$host_options = ['localhost', '127.0.0.1', '::1']; // Multiple host options for robustness
$username = 'root'; // Your MySQL username
$password = ''; // Your MySQL password (empty for XAMPP default)
$dbname = 'uniconnect'; // Your unified database name (MUST match your uniconnect.sql)

$pdo = null; // Initialize PDO object
$connected = false;
$last_error = '';

// Try different connection methods
foreach ($host_options as $host) {
    try {
        error_log("Attempting PDO connection to host: " . $host);

        // Attempt to connect to the specified database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("Connected to database '$dbname' successfully using PDO.");

        $connected = true;
        break; // Connection successful, exit the loop
    } catch(PDOException $e) {
        $last_error = $e->getMessage();
        error_log("Connection error to host $host or database $dbname: " . $last_error);
        $pdo = null; // Ensure PDO is null on failure
        // Continue to the next host option
    }
}

if (!$connected) {
    die();
}
?>
