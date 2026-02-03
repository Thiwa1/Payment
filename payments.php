<?php
require_once 'functions.php';

$message = '';
$results = [];

if (isset($_GET['search'])) {
    $results = get_employee_by_search($_GET['search']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    if (save_payment($_POST['employee_id'], $_POST['amount'], date('Y-m-d'))) {
        $message = "Payment of " . htmlspecialchars($_POST['amount']) . " saved for Employee ID " . htmlspecialchars($_POST['employee_id']);
    } else {
        $message = "Failed to save payment.";
    }
}

include 'header.php';
?>

<h1>Process Payments</h1>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<form method="GET" action="">
    <div class="form-group">
        <label>Search Employee (Name, Emp No, NIC)</label>
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Enter search term...">
        <button type="submit">Search</button>
    </div>
</form>

<?php if (!empty($results)): ?>
    <h2>Search Results</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Emp No</th>
                <th>NIC</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $emp): ?>
            <tr>
                <td><?= htmlspecialchars($emp['calling_name']) ?></td>
                <td><?= htmlspecialchars($emp['employee_number']) ?></td>
                <td><?= htmlspecialchars($emp['nic_no']) ?></td>
                <td>
                    <form method="POST" style="display:inline-flex; gap:10px;">
                        <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                        <input type="hidden" name="save_payment" value="1">
                        <input type="number" name="amount" placeholder="Amount" required step="0.01">
                        <button type="submit">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif (isset($_GET['search'])): ?>
    <p>No employees found.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
