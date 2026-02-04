<?php
require_once 'db_adapter.php';

$message = '';
$status = check_db_status();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_db'])) {
    if (defined('DB_HOST') && defined('DB_NAME')) {
        try {
            $tmp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $tmp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbname = DB_NAME;
            $tmp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");

            // Trigger auto-setup by getting connection
            $conn = getDBConnection();
            ensure_tables_exist($conn);

            $message = "Database '$dbname' created and initialized successfully.";
            $status = check_db_status(); // Refresh status
        } catch (PDOException $e) {
            $message = "Error creating database: " . $e->getMessage();
        }
    } else {
        $message = "Database configuration constants not defined.";
    }
}

include 'header.php';
?>

<h1>System Status</h1>

<?php if ($message): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div style="padding: 20px; border: 1px solid #ccc; max-width: 500px;">
    <h3>Database Connection</h3>

    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
        <!-- Status Light -->
        <div style="width: 20px; height: 20px; border-radius: 50%;
            background-color: <?= ($status['status'] == 'connected') ? '#28a745' : (($status['status'] == 'server_only') ? '#ffc107' : '#dc3545') ?>;">
        </div>

        <!-- Status Message -->
        <div>
            <strong><?= htmlspecialchars(strtoupper($status['type'])) ?></strong>: <?= htmlspecialchars($status['message']) ?>
        </div>
    </div>

    <?php if ($status['status'] == 'server_only'): ?>
        <form method="POST">
            <input type="hidden" name="create_db" value="1">
            <button type="submit" style="background-color: #007bff; color: white;">Create Database Now</button>
        </form>
    <?php endif; ?>

    <?php if ($status['type'] == 'sqlite'): ?>
        <p>Running in fallback mode (SQLite). MySQL configuration is either missing or unreachable.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
