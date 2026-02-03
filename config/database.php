<?php
// config/database.php - Database Configuration

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_NAME', 'pay_bill');

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set charset to UTF-8
    $pdo->exec("SET NAMES utf8");

} catch(PDOException $e) {
    // Suppress die() to allow fallback in db_adapter.php
    // die("ERROR: Could not connect to database. " . $e->getMessage());
    throw $e;
}
?>
