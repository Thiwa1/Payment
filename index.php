<?php
include 'header.php';
require_once 'db_adapter.php'; // Ensures DB is checked/created

$status = check_db_status();
$counts = ['employees' => 0, 'payments' => 0, 'schedules' => 0, 'paysheets' => 0];

if ($status['status'] == 'connected' || $status['type'] == 'sqlite') {
    $pdo = getDBConnection();
    try {
        $counts['employees'] = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
        $counts['payments'] = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
        $counts['schedules'] = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
        $counts['paysheets'] = $pdo->query("SELECT COUNT(*) FROM paysheets")->fetchColumn();
    } catch (Exception $e) {
        // Tables might not exist if setup failed partway, but ensure_tables_exist should have run.
    }
}
?>

    <div style="text-align: center; margin-bottom: 30px;">
        <h1>Welcome to Payment Schedule System</h1>
        <p>Manage Employees, Payments, and Paysheets efficiently.</p>
    </div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;">
        <!-- DB Status Card -->
        <div style="border: 1px solid #ccc; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3>System Status</h3>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="width: 15px; height: 15px; border-radius: 50%; background-color: <?= ($status['status'] == 'connected') ? '#28a745' : (($status['type'] == 'sqlite') ? '#17a2b8' : '#dc3545') ?>;"></div>
                <span><strong><?= htmlspecialchars(strtoupper($status['type'])) ?></strong></span>
            </div>
            <p><?= htmlspecialchars($status['message']) ?></p>
            <?php if ($status['status'] != 'connected' && $status['type'] != 'sqlite'): ?>
                <a href="setup.php" style="color: #007bff;">Go to Setup</a>
            <?php endif; ?>
        </div>

        <!-- Statistics Card -->
        <div style="border: 1px solid #ccc; padding: 20px; border-radius: 8px; width: 300px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3>Overview</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 8px;"><strong>Employees:</strong> <?= $counts['employees'] ?></li>
                <li style="margin-bottom: 8px;"><strong>Active Schedules:</strong> <?= $counts['schedules'] ?></li>
                <li style="margin-bottom: 8px;"><strong>Paysheets Uploaded:</strong> <?= $counts['paysheets'] ?> records</li>
                <li style="margin-bottom: 8px;"><strong>Payments Made:</strong> <?= $counts['payments'] ?></li>
            </ul>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <h3>Quick Actions</h3>
        <div style="display: inline-flex; gap: 15px;">
            <a href="employees.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Manage Employees</a>
            <a href="payments.php" style="padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;">Process Payments</a>
            <a href="paysheet.php" style="padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px;">Manage Paysheets</a>
        </div>
    </div>

<?php include 'footer.php'; ?>
