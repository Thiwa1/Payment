<?php
// db_adapter.php
// Tries to use config/database.php, falls back to SQLite for sandbox environment.
require_once 'init_db.php'; // Include schema logic

function getDBConnection() {
    global $pdo; // Check if $pdo exists from config/database.php

    $conn = null;

    // Try including the user's config
    try {
        if (file_exists(__DIR__ . '/config/database.php')) {
            // Capture output to prevent "ERROR" from printing
            ob_start();
            include __DIR__ . '/config/database.php';
            ob_end_clean();

            if (isset($pdo) && $pdo instanceof PDO) {
                $conn = $pdo;
            }
        }
    } catch (Exception $e) {
        // Connection failed, proceed to fallback
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

    // Auto-Run Migration/Setup
    if ($conn) {
        ensure_tables_exist($conn);
    }

    return $conn;
}
?>
