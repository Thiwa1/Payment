<?php
require_once 'db_connect.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
    while ($row = $stmt->fetch()) {
        echo $row['name'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
