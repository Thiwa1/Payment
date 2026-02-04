<?php
require_once 'functions.php';

$message = '';
$error = '';

// Handle Single Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $data = [
        'calling_name' => $_POST['calling_name'],
        'account_name' => $_POST['account_name'],
        'employee_number' => $_POST['employee_number'],
        'bank' => $_POST['bank'],
        'branch' => $_POST['branch'],
        'nic_no' => $_POST['nic_no'],
        'account_number' => $_POST['account_number'],
        'area' => $_POST['area']
    ];

    if (add_employee($data)) {
        $message = "Employee added successfully.";
    } else {
        $error = "Failed to add employee. Check for duplicate Emp No, NIC or Account No.";
    }
}

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $result = bulk_upload_employees($_FILES['csv_file']['tmp_name']);
        if (is_array($result)) {
            $count = $result['count'];
            $uploadErrors = $result['errors'];
            $message = "Uploaded $count employees successfully.";
            if ($count === 0 && empty($uploadErrors)) {
                 $message = "Uploaded 0 employees. (Did you include a header row? First row is skipped.)";
            }
            if (!empty($uploadErrors)) {
                $error = "Some errors occurred during upload:<br>" . implode("<br>", $uploadErrors);
            }
        } else {
            // Fallback if logic changes back to boolean/int for some reason
            $message = "Uploaded successfully.";
        }
    } else {
        $error = "Please upload a valid CSV file.";
    }
}

$employees = get_employees();
include 'header.php';
?>

<h1>Manage Employees</h1>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<h2>Add New Employee</h2>
<form method="POST" action="">
    <input type="hidden" name="add_employee" value="1">
    <div class="form-group"><label>Calling Name</label><input type="text" name="calling_name" required></div>
    <div class="form-group"><label>Account Name</label><input type="text" name="account_name" required></div>
    <div class="form-group"><label>Employee Number</label><input type="text" name="employee_number" required></div>
    <div class="form-group"><label>Bank</label><input type="text" name="bank" required></div>
    <div class="form-group"><label>Branch</label><input type="text" name="branch" required></div>
    <div class="form-group"><label>NIC No</label><input type="text" name="nic_no" required></div>
    <div class="form-group"><label>Account Number</label><input type="text" name="account_number" required></div>
    <div class="form-group"><label>Area</label><input type="text" name="area"></div>
    <button type="submit">Add Employee</button>
</form>

<hr>

<h2>Bulk Upload (CSV)</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="upload_csv" value="1">
    <div class="form-group">
        <label>Select CSV File (Format: Calling Name, Account Name, Emp No, Bank, Branch, NIC, Acc No, Area)</label>
        <input type="file" name="csv_file" accept=".csv" required>
    </div>
    <button type="submit">Upload CSV</button>
</form>

<hr>

<h2>Employee List</h2>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Emp No</th>
            <th>Bank</th>
            <th>Branch</th>
            <th>NIC</th>
            <th>Acc No</th>
            <th>Area</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $emp): ?>
        <tr>
            <td><?= htmlspecialchars($emp['calling_name']) ?></td>
            <td><?= htmlspecialchars($emp['employee_number']) ?></td>
            <td><?= htmlspecialchars($emp['bank']) ?></td>
            <td><?= htmlspecialchars($emp['branch']) ?></td>
            <td><?= htmlspecialchars($emp['nic_no']) ?></td>
            <td><?= htmlspecialchars($emp['account_number']) ?></td>
            <td><?= htmlspecialchars($emp['area']) ?></td>
            <td>
                <a href="edit_employee.php?id=<?= $emp['id'] ?>">Edit</a> |
                <a href="employee_history.php?id=<?= $emp['id'] ?>">History</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
