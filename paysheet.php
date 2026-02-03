<?php
require_once 'functions.php';
require_once 'export_logic.php';

$message = '';
$error = '';

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_paysheet'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0 && !empty($_POST['date'])) {
        $count = bulk_upload_paysheet($_FILES['csv_file']['tmp_name'], $_POST['date']);
        if ($count !== false) {
            $message = "Uploaded $count records successfully.";
        } else {
            $error = "Failed to process CSV file.";
        }
    } else {
        $error = "Please upload a valid CSV file and select a date.";
    }
}

// Handle Export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $month = $_POST['month_year'] ?? date('Y-m');
    $spreadsheet = generate_paysheet_excel($month);
    $filename = "paysheet_$month.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Get Data for Display
$display_month = $_GET['month'] ?? date('Y-m');
$paysheets = get_paysheet_data($display_month);

include 'header.php';
?>

<h1>Paysheet Management</h1>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display: flex; gap: 20px; margin-bottom: 20px;">
    <!-- Upload Section -->
    <div style="border: 1px solid #ccc; padding: 15px; flex: 1;">
        <h3>Upload Paysheet (CSV)</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_paysheet" value="1">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit">Upload</button>
        </form>
    </div>

    <!-- Export Section -->
    <div style="border: 1px solid #ccc; padding: 15px; flex: 1;">
        <h3>Export Paysheet (Excel)</h3>
        <form method="POST">
            <input type="hidden" name="export_excel" value="1">
            <div class="form-group">
                <label>Month</label>
                <input type="month" name="month_year" value="<?= $display_month ?>" required>
            </div>
            <button type="submit" style="background-color: #28a745;">Export to Excel</button>
        </form>
    </div>
</div>

<hr>

<!-- Display Section -->
<div style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Paysheet Data for <?= htmlspecialchars($display_month) ?></h2>
    <form method="GET">
        <label>View Month: </label>
        <input type="month" name="month" value="<?= $display_month ?>" onchange="this.form.submit()">
    </form>
</div>

<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Emp Code</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Status</th>
                <th>Basic</th>
                <th>Gross Pay</th>
                <th>Net Pay</th>
                <th>Hold</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($paysheets) > 0): ?>
                <?php foreach ($paysheets as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['emp_code']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['designation']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= number_format($row['basic_salary'], 2) ?></td>
                    <td><?= number_format($row['gross_pay'], 2) ?></td>
                    <td><?= number_format($row['net_pay'], 2) ?></td>
                    <td><?= htmlspecialchars($row['hold']) ?></td>
                    <td>
                        <a href="payslip.php?id=<?= $row['id'] ?>" target="_blank">View Payslip</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">No records found for this month.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
