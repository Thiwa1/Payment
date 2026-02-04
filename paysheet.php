<?php
require_once 'functions.php';
require_once 'export_logic.php';

$message = '';
$error = '';

// Handle Create Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    if (!empty($_POST['schedule_name']) && !empty($_POST['schedule_date'])) {
        $id = create_schedule($_POST['schedule_name'], $_POST['schedule_date']);
        if ($id) {
            $message = "Schedule '" . htmlspecialchars($_POST['schedule_name']) . "' created successfully.";
        } else {
            $error = "Failed to create schedule. Name must be unique.";
        }
    } else {
        $error = "Name and Date required.";
    }
}

// Handle CSV Upload to Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_paysheet'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0 && !empty($_POST['schedule_id'])) {
        $schedule = get_schedule_by_id($_POST['schedule_id']);
        if ($schedule) {
            $count = bulk_upload_paysheet($_FILES['csv_file']['tmp_name'], $schedule['id'], $schedule['date']);
            if ($count !== false) {
                $message = "Uploaded $count records to '" . htmlspecialchars($schedule['name']) . "' successfully.";
            } else {
                $error = "Failed to process CSV file.";
            }
        } else {
            $error = "Invalid Schedule Selected.";
        }
    } else {
        $error = "Please upload a valid CSV file and select a schedule.";
    }
}

// Handle Export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $target = $_POST['target_schedule'] ?? null; // ID or Date
    // If ID (from view mode), use that. If Date (legacy), use that.
    $target = $target ?: ($_POST['month_year'] ?? date('Y-m'));

    $spreadsheet = generate_paysheet_excel($target);

    if ($spreadsheet instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
        $filename = "paysheet_export.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

// Get Schedules List
$schedules = get_schedules();

// Get Data for Display (View Mode)
$view_schedule_id = $_GET['view_schedule_id'] ?? null;
$view_schedule = null;
$paysheets = [];

if ($view_schedule_id) {
    $view_schedule = get_schedule_by_id($view_schedule_id);
    if ($view_schedule) {
        $paysheets = get_paysheet_data($view_schedule_id);
    }
} else {
    // Default or Legacy View (by month)
    $display_month = $_GET['month'] ?? date('Y-m');
    $paysheets = get_paysheet_data($display_month);
}

include 'header.php';
?>

<h1>Paysheet Management</h1>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display: flex; gap: 20px; flex-wrap: wrap;">

    <!-- Create Schedule -->
    <div style="border: 1px solid #ccc; padding: 15px; flex: 1; min-width: 300px;">
        <h3>1. Create Schedule</h3>
        <form method="POST">
            <input type="hidden" name="create_schedule" value="1">
            <div class="form-group">
                <label>Schedule Name (Unique)</label>
                <input type="text" name="schedule_name" required placeholder="e.g. Feb 2026 Bonus">
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="schedule_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit">Create Schedule</button>
        </form>
    </div>

    <!-- Upload to Schedule -->
    <div style="border: 1px solid #ccc; padding: 15px; flex: 1; min-width: 300px;">
        <h3>2. Upload Paysheet</h3>
        <?php if (count($schedules) > 0): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_paysheet" value="1">
                <div class="form-group">
                    <label>Select Schedule</label>
                    <select name="schedule_id" required style="width: 100%; padding: 8px;">
                        <option value="">-- Select Schedule --</option>
                        <?php foreach ($schedules as $sch): ?>
                            <option value="<?= $sch['id'] ?>"><?= htmlspecialchars($sch['name']) ?> (<?= $sch['date'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit">Upload CSV</button>
            </form>
        <?php else: ?>
            <p style="color: #856404; background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba;">
                No schedules found. Please create a schedule in step 1 first.
            </p>
        <?php endif; ?>
    </div>

</div>

<hr>

<h3>Schedules List</h3>
<?php if (count($schedules) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $sch): ?>
            <tr>
                <td><?= $sch['id'] ?></td>
                <td><?= htmlspecialchars($sch['name']) ?></td>
                <td><?= $sch['date'] ?></td>
                <td>
                    <a href="paysheet.php?view_schedule_id=<?= $sch['id'] ?>">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No schedules found.</p>
<?php endif; ?>

<hr>

<!-- Display Section -->
<?php if ($view_schedule): ?>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Details: <?= htmlspecialchars($view_schedule['name']) ?></h2>

        <form method="POST">
            <input type="hidden" name="export_excel" value="1">
            <input type="hidden" name="target_schedule" value="<?= $view_schedule['id'] ?>">
            <button type="submit" style="background-color: #28a745;">Export This Schedule</button>
        </form>
    </div>
<?php else: ?>
    <h2>Viewing All/Filtered Data</h2>
<?php endif; ?>

<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Emp Code</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Net Pay</th>
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
                    <td><?= number_format($row['net_pay'], 2) ?></td>
                    <td>
                        <a href="payslip.php?id=<?= $row['id'] ?>" target="_blank">Payslip</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
