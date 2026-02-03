<?php
// db_adapter.php
// Tries to use config/database.php, handles missing DB creation, falls back to SQLite for sandbox.
require_once 'init_db.php'; // Include schema logic

function getDBConnection() {
    global $pdo; // Check if $pdo exists from config/database.php

    $conn = null;

    // Try including the user's config
    try {
        if (file_exists(__DIR__ . '/config/database.php')) {
            // Capture output to prevent "ERROR" strings from leaking
            ob_start();
            include __DIR__ . '/config/database.php';
            ob_end_clean();

            if (isset($pdo) && $pdo instanceof PDO) {
                $conn = $pdo;
            }
        }
    } catch (PDOException $e) {
        // Connection failed. Check if it's because the database doesn't exist (Code 1049)
        // or generically try to create it if constants are defined.
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            try {
                // Connect to MySQL server without selecting a database
                $tmp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                $tmp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Create the database
                $dbname = DB_NAME;
                // Escape dbname specifically for SQL identifier if needed, though usually it's a simple string.
                // Using backticks for safety.
                $tmp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");

                // Retry connection to the specific database
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $conn->exec("SET NAMES utf8");

            } catch (PDOException $ex) {
                // Creation failed or still can't connect (e.g., wrong password)
                // Fall through to SQLite fallback
            }
        }
    } catch (Exception $e) {
        // General error
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
