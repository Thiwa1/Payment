<?php
require_once 'export_logic.php';

if (isset($_POST['export_fuel'])) {
    $month = $_POST['month_year'] ?? date('Y-m');
    $spreadsheet = generate_fuel_allowance_report($month);
    $filename = "fuel_allowance_$month.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if (isset($_POST['export_bank'])) {
    $month = $_POST['month_year'] ?? date('Y-m');
    $spreadsheet = generate_bank_transfer_report($month);
    $filename = "bank_transfer_$month.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

include 'header.php';
?>

<h1>Reports</h1>

<form method="POST" action="">
    <div class="form-group">
        <label>Select Month</label>
        <input type="month" name="month_year" value="<?= date('Y-m') ?>" required>
    </div>

    <div class="form-group">
        <button type="submit" name="export_fuel">Export Fuel Allowance Report</button>
        <button type="submit" name="export_bank" style="background-color: #28a745;">Export Bank Transfer Report</button>
    </div>
</form>

<?php include 'footer.php'; ?>
