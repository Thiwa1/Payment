<?php
require_once 'functions.php';
require_once 'export_logic.php';

$message = '';
$results = [];

if (isset($_GET['search'])) {
    $results = get_employee_by_search($_GET['search']);
}

// Handle Payment Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    if (save_payment($_POST['employee_id'], $_POST['amount'], date('Y-m-d'))) {
        $message = "Payment of " . htmlspecialchars($_POST['amount']) . " saved for Employee ID " . htmlspecialchars($_POST['employee_id']);
    } else {
        $message = "Failed to save payment.";
    }
}

// Handle Delete Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment_id'])) {
    if (delete_payment($_POST['delete_payment_id'])) {
        $message = "Payment deleted successfully.";
    } else {
        $message = "Failed to delete payment.";
    }
}

// Handle Export Today
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_today'])) {
    $date = date('Y-m-d');
    $spreadsheet = generate_bank_transfer_report($date);
    // If CSV fallback was triggered, script would have exited in generate_bank_transfer_report

    // Otherwise, handle Excel output
    if ($spreadsheet instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
        $filename = "bank_transfer_$date.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

$recent_payments = get_recent_payments();

include 'header.php';
?>

<h1>Process Payments</h1>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<!-- Export Section -->
<div style="margin-bottom: 20px; text-align: right;">
    <form method="POST" style="display:inline;">
        <input type="hidden" name="download_today" value="1">
        <button type="submit" style="background-color: #28a745;">Download Report (Today)</button>
    </form>
</div>

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
                        <button type="submit">Save & Continue</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif (isset($_GET['search'])): ?>
    <p>No employees found.</p>
<?php endif; ?>

<hr>
<h2>Recent Payments</h2>
<?php if (count($recent_payments) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Emp No</th>
                <th>Name</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_payments as $pmt): ?>
            <tr>
                <td><?= htmlspecialchars($pmt['payment_date']) ?></td>
                <td><?= htmlspecialchars($pmt['employee_number']) ?></td>
                <td><?= htmlspecialchars($pmt['calling_name']) ?></td>
                <td><?= number_format($pmt['amount'], 2) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this payment?');">
                        <input type="hidden" name="delete_payment_id" value="<?= $pmt['id'] ?>">
                        <button type="submit" style="background-color: #dc3545;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No recent payments found.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
