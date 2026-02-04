<?php
// db_adapter.php
// Tries to use config/database.php, handles missing DB creation, falls back to SQLite for sandbox.
require_once 'init_db.php'; // Include schema logic

// Load configuration ONCE
if (file_exists(__DIR__ . '/config/database.php')) {
    // Capture output to prevent "ERROR" strings from leaking
    ob_start();
    include_once __DIR__ . '/config/database.php';
    ob_end_clean();
}

function getDBConnection() {
    global $pdo; // Check if $pdo exists from config/database.php

    // If connection already established globally (in config), verify it's valid
    if (isset($pdo) && $pdo instanceof PDO) {
        // We could return it, but ensure_tables_exist needs running at least once?
        // Actually, ensuring tables every time is safe but maybe redundant.
        // Let's assume config established it correctly.
        // However, the previous logic handled auto-creation logic *inside* here.
        // If config/database.php throws exception, $pdo is null.
    }

    $conn = null;

    // Try using the PDO from config if it succeeded
    if (isset($pdo) && $pdo instanceof PDO) {
        $conn = $pdo;
    } else {
        // Config failed or didn't exist. Check if constants are defined (from include_once above)
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            try {
                // Try connecting normally first
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $conn->exec("SET NAMES utf8");
            } catch (PDOException $e) {
                // Connection failed. Check if it's because the database doesn't exist
                try {
                    // Connect to MySQL server without selecting a database
                    $tmp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                    $tmp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $dbname = DB_NAME;
                    $tmp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");

                    // Retry connection
                    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $conn->exec("SET NAMES utf8");

                } catch (PDOException $ex) {
                    // Fallback
                }
            }
        }
    }

    if (!$conn) {
        // Fallback: SQLite
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
?>
