<?php
require_once 'functions.php';
require_once 'export_logic.php';

$message = '';
$results = [];

// Handle Create Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    if (!empty($_POST['schedule_name']) && !empty($_POST['schedule_date'])) {
        $id = create_schedule($_POST['schedule_name'], $_POST['schedule_date']);
        if ($id) {
            $message = "Schedule '" . htmlspecialchars($_POST['schedule_name']) . "' created successfully.";
        } else {
            $message = "Failed to create schedule. Name must be unique.";
        }
    }
}

// Handle Update Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    if (update_schedule($_POST['schedule_id'], $_POST['schedule_name'], $_POST['schedule_date'])) {
        $message = "Schedule updated successfully.";
    } else {
        $message = "Failed to update schedule.";
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

// Handle Save Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    if (!empty($_POST['active_schedule_id'])) {
        if (save_payment($_POST['employee_id'], $_POST['amount'], date('Y-m-d'), $_POST['active_schedule_id'])) {
            $message = "Payment saved.";
        } else {
            $message = "Failed to save payment.";
        }
    } else {
        $message = "No Schedule selected.";
    }
}

// Handle Export by Schedule ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_schedule_report'])) {
    $schedule_id = $_POST['schedule_id'];
    $type = $_POST['report_type']; // 'fuel' or 'bank'

    if ($type === 'bank') {
        $spreadsheet = generate_bank_transfer_report($schedule_id); // Pass ID now
        $prefix = "bank_transfer";
    } else {
        $spreadsheet = generate_fuel_allowance_report($schedule_id);
        $prefix = "fuel_allowance";
    }

    if ($spreadsheet instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
        $filename = "{$prefix}_{$schedule_id}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

// Get Schedules
$schedules = get_schedules();
$active_schedule_id = $_GET['schedule_id'] ?? ($_POST['active_schedule_id'] ?? null);

// Get Search Results
if (isset($_GET['search'])) {
    $results = get_employee_by_search($_GET['search']);
}

$recent_payments = get_recent_payments();

include 'header.php';
?>

<h1>Payment Schedules & Processing</h1>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
    <!-- Create Schedule -->
    <div style="border: 1px solid #ccc; padding: 15px; flex: 1;">
        <h3>1. Create Payment Schedule</h3>
        <form method="POST">
            <input type="hidden" name="create_schedule" value="1">
            <input type="text" name="schedule_name" required placeholder="Schedule Name (e.g. Feb 2026 Fuel)">
            <input type="date" name="schedule_date" required value="<?= date('Y-m-d') ?>">
            <button type="submit">Create</button>
        </form>
    </div>

    <!-- Select Active Schedule -->
    <div style="border: 1px solid #ccc; padding: 15px; flex: 1; background: #e9ecef;">
        <h3>2. Select Schedule to Mark Payments</h3>
        <form method="GET">
            <select name="schedule_id" onchange="this.form.submit()" style="width: 100%; padding: 8px;">
                <option value="">-- Select Schedule --</option>
                <?php foreach ($schedules as $sch): ?>
                    <option value="<?= $sch['id'] ?>" <?= ($active_schedule_id == $sch['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sch['name']) ?> (<?= $sch['date'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($active_schedule_id): ?>
    <hr>
    <h3>Marking Payments for Schedule ID: <?= $active_schedule_id ?></h3>
    <form method="GET" action="">
        <input type="hidden" name="schedule_id" value="<?= $active_schedule_id ?>">
        <div class="form-group">
            <label>Search Employee</label>
            <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Enter search term...">
            <button type="submit">Search</button>
        </div>
    </form>

    <?php if (!empty($results)): ?>
        <table>
            <thead><tr><th>Name</th><th>Emp No</th><th>NIC</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($results as $emp): ?>
                <tr>
                    <td><?= htmlspecialchars($emp['calling_name']) ?></td>
                    <td><?= htmlspecialchars($emp['employee_number']) ?></td>
                    <td><?= htmlspecialchars($emp['nic_no']) ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:10px;">
                            <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                            <input type="hidden" name="active_schedule_id" value="<?= $active_schedule_id ?>">
                            <input type="hidden" name="save_payment" value="1">
                            <input type="number" name="amount" placeholder="Amount" required step="0.01">
                            <button type="submit">Save & Continue</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<hr>
<h3>Manage Schedules</h3>
<table>
    <thead><tr><th>Name</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
        <?php foreach ($schedules as $sch): ?>
        <tr>
            <form method="POST">
                <input type="hidden" name="schedule_id" value="<?= $sch['id'] ?>">
                <input type="hidden" name="update_schedule" value="1">
                <td><input type="text" name="schedule_name" value="<?= htmlspecialchars($sch['name']) ?>"></td>
                <td><input type="date" name="schedule_date" value="<?= $sch['date'] ?>"></td>
                <td>
                    <button type="submit" style="font-size: 12px;">Update</button>
            </form>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="schedule_id" value="<?= $sch['id'] ?>">
                <input type="hidden" name="download_schedule_report" value="1">
                <input type="hidden" name="report_type" value="bank">
                <button type="submit" style="background-color: #28a745; font-size: 12px;">Bank Report</button>
            </form>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="schedule_id" value="<?= $sch['id'] ?>">
                <input type="hidden" name="download_schedule_report" value="1">
                <input type="hidden" name="report_type" value="fuel">
                <button type="submit" style="background-color: #17a2b8; font-size: 12px;">Fuel Report</button>
            </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<hr>
<h3>Recent Payments (All)</h3>
<?php if (count($recent_payments) > 0): ?>
    <table>
        <thead><tr><th>Schedule</th><th>Date</th><th>Emp No</th><th>Amount</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($recent_payments as $pmt): ?>
            <tr>
                <td><?= htmlspecialchars($pmt['schedule_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($pmt['payment_date']) ?></td>
                <td><?= htmlspecialchars($pmt['employee_number']) ?></td>
                <td><?= number_format($pmt['amount'], 2) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete?');">
                        <input type="hidden" name="delete_payment_id" value="<?= $pmt['id'] ?>">
                        <button type="submit" style="background-color: #dc3545;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>
