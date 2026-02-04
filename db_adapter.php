<?php
// db_adapter.php
// Tries to use config/database.php, handles missing DB creation, falls back to SQLite for sandbox.
require_once 'init_db.php'; // Include schema logic

// Load configuration logic securely
$config_loaded = false;
$pdo = null;

if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        // Suppress output and errors during inclusion to handle connection failures gracefully
        ob_start();
        include_once __DIR__ . '/config/database.php';
        ob_end_clean();
        $config_loaded = true;
    } catch (Exception $e) {
        // Exception caught from config/database.php
        ob_end_clean(); // Ensure buffer is cleared
    }
}

function getDBConnection() {
    global $pdo; // Check if $pdo exists from config/database.php

    $conn = null;

    // 1. Try using the PDO from config if it succeeded
    if (isset($pdo) && $pdo instanceof PDO) {
        $conn = $pdo;
    } else {
        // 2. Config failed or didn't exist. Check if constants are defined
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            try {
                // Try connecting normally first (in case the global $pdo failed but logic works now?)
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $conn->exec("SET NAMES utf8");
            } catch (PDOException $e) {
                // 3. Connection failed. Check if it's because the database doesn't exist (Code 1049)
                try {
                    // Connect to MySQL server without selecting a database
                    $tmp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                    $tmp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $dbname = DB_NAME;
                    // Attempt to create
                    $tmp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");

                    // Retry connection
                    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $conn->exec("SET NAMES utf8");

                } catch (PDOException $ex) {
                    // Creation failed or generic failure
                }
            }
        }
    }

    // 4. Fallback to SQLite if MySQL is unavailable
    if (!$conn) {
        $db_file = __DIR__ . '/database.sqlite';
        try {
            $conn = new PDO("sqlite:" . $db_file);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Connection failed (Fallback): " . $e->getMessage());
        }
    }

    // Auto-Run Migration/Setup (Create Tables)
    if ($conn) {
        ensure_tables_exist($conn);
    }

    return $conn;
}

function check_db_status() {
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        return ['status' => 'connected', 'type' => 'mysql', 'message' => 'Connected to ' . DB_NAME];
    }

    if (defined('DB_HOST')) {
        // Check if server is reachable but DB missing
        try {
            $test = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            return ['status' => 'server_only', 'type' => 'mysql', 'message' => 'MySQL Server reachable, Database missing'];
        } catch (PDOException $e) {
            return ['status' => 'failed', 'type' => 'none', 'message' => 'Cannot connect to MySQL Server: ' . $e->getMessage()];
        }
    }

    return ['status' => 'sqlite', 'type' => 'sqlite', 'message' => 'Using local SQLite database'];
}
?>
