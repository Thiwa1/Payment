<?php
require_once 'functions.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: employees.php");
    exit;
}

$employee = get_employee_by_id($id);
if (!$employee) {
    die("Employee not found.");
}

$history = get_employee_payment_history($id);

include 'header.php';
?>

<h1>Payment History: <?= htmlspecialchars($employee['calling_name']) ?></h1>
<p>
    <strong>Emp No:</strong> <?= htmlspecialchars($employee['employee_number']) ?> |
    <strong>NIC:</strong> <?= htmlspecialchars($employee['nic_no']) ?> |
    <strong>Bank:</strong> <?= htmlspecialchars($employee['bank']) ?>
</p>

<a href="employees.php">&laquo; Back to Employees</a>

<hr>

<?php if (count($history) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Schedule Name</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            foreach ($history as $row):
                $total += $row['amount'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['payment_date']) ?></td>
                <td><?= htmlspecialchars($row['schedule_name'] ?? 'N/A') ?></td>
                <td style="text-align: right;"><?= number_format($row['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: #eee;">
                <td colspan="2" style="text-align: right;">Total</td>
                <td style="text-align: right;"><?= number_format($total, 2) ?></td>
            </tr>
        </tbody>
    </table>
<?php else: ?>
    <p>No payment history found for this employee.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
