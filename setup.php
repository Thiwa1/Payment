<?php
require_once 'db_adapter.php';

$message = '';
$status = check_db_status();

// Handle Create DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_db'])) {
    if (defined('DB_HOST') && defined('DB_NAME')) {
        try {
            $tmp_pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $tmp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbname = DB_NAME;
            $tmp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");

            $conn = getDBConnection();
            ensure_tables_exist($conn);

            $message = "Database '$dbname' created and initialized successfully.";
            $status = check_db_status();
        } catch (PDOException $e) {
            $message = "Error creating database: " . $e->getMessage();
        }
    } else {
        $message = "Database configuration constants not defined.";
    }
}

// Handle Update Config
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $host = $_POST['db_host'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];
    $name = $_POST['db_name'];

    // Test Connection
    try {
        $test_conn = new PDO("mysql:host=$host", $user, $pass);
        $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Write Config File
        $config_content = "<?php\n" .
                          "// config/database.php - Database Configuration\n\n" .
                          "define('DB_HOST', '" . addslashes($host) . "');\n" .
                          "define('DB_USER', '" . addslashes($user) . "');\n" .
                          "define('DB_PASS', '" . addslashes($pass) . "');\n" .
                          "define('DB_NAME', '" . addslashes($name) . "');\n\n" .
                          "try {\n" .
                          "    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);\n" .
                          "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n" .
                          "    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n" .
                          "    \$pdo->exec(\"SET NAMES utf8\");\n" .
                          "} catch(PDOException \$e) {\n" .
                          "    throw \$e;\n" .
                          "}\n" .
                          "?>";

        if (file_put_contents(__DIR__ . '/config/database.php', $config_content)) {
            $message = "Configuration updated. Please refresh.";
            // Reload page to pick up new config? PHP constants generally can't be redefined in same request easily.
            echo "<meta http-equiv='refresh' content='0'>";
            exit;
        } else {
            $message = "Failed to write config file. Check permissions.";
        }

    } catch (PDOException $e) {
        $message = "Connection failed: " . $e->getMessage();
    }
}

include 'header.php';
?>

<h1>System Status</h1>

<?php if ($message): ?>
    <div class="alert"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">

    <!-- Status Box -->
    <div style="padding: 20px; border: 1px solid #ccc; max-width: 500px; flex: 1;">
        <h3>Database Connection</h3>

        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 20px; height: 20px; border-radius: 50%;
                background-color: <?= ($status['status'] == 'connected') ? '#28a745' : (($status['status'] == 'server_only') ? '#ffc107' : '#dc3545') ?>;">
            </div>
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

    <!-- Config Form -->
    <div style="padding: 20px; border: 1px solid #ccc; max-width: 500px; flex: 1;">
        <h3>Configure Database</h3>
        <p>Update credentials if connection failed.</p>
        <form method="POST">
            <input type="hidden" name="update_config" value="1">
            <div class="form-group">
                <label>Host</label>
                <input type="text" name="db_host" value="<?= defined('DB_HOST') ? DB_HOST : 'localhost' ?>" required>
            </div>
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="<?= defined('DB_NAME') ? DB_NAME : 'pay_bill' ?>" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="db_user" value="<?= defined('DB_USER') ? DB_USER : 'root' ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="text" name="db_pass" value="<?= defined('DB_PASS') ? DB_PASS : '' ?>" placeholder="(Leave empty if none)">
            </div>
            <button type="submit">Save & Connect</button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>
