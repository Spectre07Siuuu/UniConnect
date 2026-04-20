<?php
require_once __DIR__ . '/env.php';

loadProjectEnv(dirname(__DIR__) . '/.env');

$host_options = array_values(array_filter(array_map(
    'trim',
    explode(',', envValue('UNICONNECT_DB_HOSTS', 'localhost,127.0.0.1,::1'))
)));
$username = envValue('UNICONNECT_DB_USER', 'root');
$password = envValue('UNICONNECT_DB_PASS', '');
$dbname = envValue('UNICONNECT_DB_NAME', 'uniconnect');
$charset = envValue('UNICONNECT_DB_CHARSET', 'utf8mb4');

$pdo = null;
$connected = false;
$last_error = '';

foreach ($host_options as $host) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $connected = true;
        break;
    } catch (PDOException $e) {
        $last_error = $e->getMessage();
        error_log("Connection error to host $host or database $dbname: " . $last_error);
        $pdo = null;
    }
}

if (!$connected) {
    http_response_code(500);
    die('Database connection failed.');
}
?>
