<?php
// db_adapter.php
// Tries to use config/database.php, falls back to SQLite for sandbox environment.

function getDBConnection() {
    global $pdo; // Check if $pdo exists from config/database.php

    // Try including the user's config
    try {
        if (file_exists(__DIR__ . '/config/database.php')) {
            // Capture output to prevent "ERROR" from printing if we are catching the exception inside (I modified config/database.php to throw instead of die, but just in case)
            ob_start();
            include __DIR__ . '/config/database.php';
            ob_end_clean();

            if (isset($pdo) && $pdo instanceof PDO) {
                return $pdo;
            }
        }
    } catch (Exception $e) {
        // Connection failed, proceed to fallback
    }

    // Fallback: SQLite
    $db_file = __DIR__ . '/database.sqlite';
    try {
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed (Fallback): " . $e->getMessage());
    }
}
?>
