<?php
require_once 'functions.php';
require_once 'export_logic.php';

$message = '';
$results = [];
$bulk_report = null;

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

// Handle Delete Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule_id'])) {
    if (delete_schedule($_POST['delete_schedule_id'])) {
        $message = "Schedule and its details deleted successfully.";
        // Clear active schedule if it was the one deleted
        if (isset($_GET['schedule_id']) && $_GET['schedule_id'] == $_POST['delete_schedule_id']) {
            unset($_GET['schedule_id']);
        }
        if (isset($_POST['active_schedule_id']) && $_POST['active_schedule_id'] == $_POST['delete_schedule_id']) {
            unset($_POST['active_schedule_id']);
        }
    } else {
        $message = "Failed to delete schedule.";
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

// Helper for scientific notation cleaning
if (!function_exists('clean_csv_emp_no')) {
    function clean_csv_emp_no($val) {
        $val = trim($val);
        // If numeric and contains 'E' or 'e' (Scientific Notation)
        if (is_numeric($val) && stripos($val, 'E') !== false) {
            return number_format((float)$val, 0, '', '');
        }
        return $val;
    }
}

// Handle Bulk Upload Preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bulk_payments'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0 && !empty($_POST['active_schedule_id'])) {
        $schedule_id = $_POST['active_schedule_id'];
        $file_path = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file_path, "r");

        if ($handle !== FALSE) {
            $unavailable_employees = [];
            $blank_amounts = [];
            $valid_rows = [];
            $total_valid_amount = 0;
            $rowNum = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNum++;
                if (empty($data) || (count($data) < 2)) continue;

                // Expecting Col 0: Emp No, Col 1: Amount
                $emp_no_raw = $data[0];
                $amount = trim($data[1]);

                // Skip header row roughly checking if amount is not numeric and emp_no is "Emp No" or similar
                if ($rowNum == 1 && !is_numeric($amount) && (stripos($emp_no_raw, 'emp') !== false || stripos($amount, 'amount') !== false)) {
                    continue;
                }

                $emp_no = clean_csv_emp_no($emp_no_raw);

                // Validate Emp No
                $emp_id = get_employee_by_number($emp_no);

                if (!$emp_id) {
                    $unavailable_employees[] = $emp_no . " (Row $rowNum)";
                    continue;
                }

                // Validate Amount
                if ($amount === '' || $amount === null) {
                    $blank_amounts[] = $emp_no . " (Row $rowNum)";
                    continue;
                }

                // Prepare valid payment
                $clean_amount = clean_number($amount);
                $valid_rows[] = ['emp_id' => $emp_id, 'amount' => $clean_amount];
                $total_valid_amount += $clean_amount;
            }
            fclose($handle);

            $bulk_report = [
                'unavailable' => $unavailable_employees,
                'blank' => $blank_amounts,
                'total_amount' => $total_valid_amount,
                'valid_count' => count($valid_rows),
                'valid_rows_encoded' => base64_encode(json_encode($valid_rows)),
                'schedule_id' => $schedule_id
            ];
            $message = "Please verify the summary below before confirming.";
        } else {
            $message = "Cannot open CSV file.";
        }
    } else {
        $message = "Invalid file or no schedule selected.";
    }
}

// Handle Bulk Upload Confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_bulk_upload'])) {
    $encoded_rows = $_POST['valid_rows_encoded'];
    $schedule_id = $_POST['active_schedule_id'];

    $valid_rows = json_decode(base64_decode($encoded_rows), true);

    if (is_array($valid_rows) && !empty($valid_rows)) {
        $count = 0;
        foreach ($valid_rows as $row) {
            if (save_payment($row['emp_id'], $row['amount'], date('Y-m-d'), $schedule_id)) {
                $count++;
            }
        }
        $message = "Successfully processed $count payments.";
    } else {
        $message = "No valid payments to process.";
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

<?php if ($bulk_report): ?>
<div style="background-color: #f8f9fa; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">
    <h3>Bulk Upload Verification Summary</h3>
    <p><strong>Total Valid Payments Found:</strong> <?= $bulk_report['valid_count'] ?></p>
    <p><strong>Total Value:</strong> <?= number_format($bulk_report['total_amount'], 2) ?></p>

    <?php if ($bulk_report['valid_count'] > 0): ?>
        <form method="POST">
            <input type="hidden" name="confirm_bulk_upload" value="1">
            <input type="hidden" name="active_schedule_id" value="<?= $bulk_report['schedule_id'] ?>">
            <input type="hidden" name="valid_rows_encoded" value="<?= htmlspecialchars($bulk_report['valid_rows_encoded']) ?>">
            <button type="submit" style="background-color: #28a745; font-size: 1.1em; padding: 10px 20px;">Confirm & Process Payments</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($bulk_report['unavailable'])): ?>
        <div style="color: red; margin-top: 10px;">
            <strong>Unavailable Employees (Skipped):</strong><br>
            <?= implode(', ', array_map('htmlspecialchars', $bulk_report['unavailable'])) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($bulk_report['blank'])): ?>
        <div style="color: orange; margin-top: 10px;">
            <strong>Blank Amounts (Skipped):</strong><br>
            <?= implode(', ', array_map('htmlspecialchars', $bulk_report['blank'])) ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

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
        <?php if (count($schedules) > 0): ?>
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
        <?php else: ?>
            <p style="color: #856404; background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba;">
                No schedules found. Please create one on the left first.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php if ($active_schedule_id): ?>
    <hr>
    <h3>Marking Payments for Schedule ID: <?= $active_schedule_id ?></h3>

    <!-- Bulk Upload Section -->
    <div style="border: 1px dashed #007bff; padding: 15px; margin-bottom: 20px; background-color: #f0f8ff;">
        <h4>Bulk Payment Upload (CSV)</h4>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_bulk_payments" value="1">
            <input type="hidden" name="active_schedule_id" value="<?= $active_schedule_id ?>">
            <input type="file" name="csv_file" accept=".csv" required>
            <p style="font-size: 0.9em; color: #666;">Format: <code>Emp No, Amount</code> (Header optional)</p>
            <button type="submit">Upload & Verify</button>
        </form>
    </div>

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
            <td><input type="text" name="schedule_name" value="<?= htmlspecialchars($sch['name']) ?>" form="form_update_<?= $sch['id'] ?>"></td>
            <td><input type="date" name="schedule_date" value="<?= $sch['date'] ?>" form="form_update_<?= $sch['id'] ?>"></td>
            <td>
                <form method="POST" onsubmit="return confirm('Are you sure? This will delete the schedule AND all associated payments.');" style="display:inline;">
                    <input type="hidden" name="delete_schedule_id" value="<?= $sch['id'] ?>">
                    <button type="submit" style="background-color: #dc3545; font-size: 12px; margin-right: 5px;">Delete</button>
                </form>

            <form method="POST" id="form_update_<?= $sch['id'] ?>" style="display:inline;">
                <input type="hidden" name="schedule_id" value="<?= $sch['id'] ?>">
                <input type="hidden" name="update_schedule" value="1">
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
