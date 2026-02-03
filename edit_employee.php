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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update logic - implementing a simple update function inline or here
    // For simplicity, let's just use raw PDO update here as it wasn't in functions.php
    $pdo = getDBConnection();
    $sql = "UPDATE employees SET
            calling_name = :cn, account_name = :an, employee_number = :en,
            bank = :bk, branch = :br, nic_no = :nic, account_number = :acc, area = :ar
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':cn' => $_POST['calling_name'],
            ':an' => $_POST['account_name'],
            ':en' => $_POST['employee_number'],
            ':bk' => $_POST['bank'],
            ':br' => $_POST['branch'],
            ':nic' => $_POST['nic_no'],
            ':acc' => $_POST['account_number'],
            ':ar' => $_POST['area'],
            ':id' => $id
        ]);
        $message = "Employee updated successfully.";
        $employee = get_employee_by_id($id); // Refresh data
    } catch (PDOException $e) {
        $error = "Failed to update employee. Check for duplicate unique fields.";
    }
}

include 'header.php';
?>

<h1>Edit Employee</h1>
<a href="employees.php">&laquo; Back to List</a>

<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" action="">
    <div class="form-group"><label>Calling Name</label><input type="text" name="calling_name" value="<?= htmlspecialchars($employee['calling_name']) ?>" required></div>
    <div class="form-group"><label>Account Name</label><input type="text" name="account_name" value="<?= htmlspecialchars($employee['account_name']) ?>" required></div>
    <div class="form-group"><label>Employee Number</label><input type="text" name="employee_number" value="<?= htmlspecialchars($employee['employee_number']) ?>" required></div>
    <div class="form-group"><label>Bank</label><input type="text" name="bank" value="<?= htmlspecialchars($employee['bank']) ?>" required></div>
    <div class="form-group"><label>Branch</label><input type="text" name="branch" value="<?= htmlspecialchars($employee['branch']) ?>" required></div>
    <div class="form-group"><label>NIC No</label><input type="text" name="nic_no" value="<?= htmlspecialchars($employee['nic_no']) ?>" required></div>
    <div class="form-group"><label>Account Number</label><input type="text" name="account_number" value="<?= htmlspecialchars($employee['account_number']) ?>" required></div>
    <div class="form-group"><label>Area</label><input type="text" name="area" value="<?= htmlspecialchars($employee['area']) ?>"></div>
    <button type="submit">Update Employee</button>
</form>

<?php include 'footer.php'; ?>
