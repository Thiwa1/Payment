<?php
require_once 'functions.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid Paysheet ID");
}

$paysheet = get_paysheet_entry($id);
if (!$paysheet) {
    die("Paysheet entry not found");
}

$month_year = date('F Y', strtotime($paysheet['date']));

// Map DB columns to Pay Slip fields
// Using explicit mapping based on available data and likely interpretations.
$basic_salary = $paysheet['basic_salary'];
$bra = 0; // Not strictly in DB, maybe derive or leave 0? Or maybe 'arrears'? Let's assume 0 if not present.
// Actually, input column "NOPAY FOR BUDGETORY" suggests Budgetory Relief Allowance exists.
// But I don't have a column for BRA income. I'll stick to what I have.
$travelling = $paysheet['travelling_allowance'];
$vehicle = $paysheet['vehicle_allowance'];

$total_income = $basic_salary + $bra + $travelling + $vehicle;

$nopay_days = $paysheet['salary_nopay_days'];
$days_for_salary = 30.0; // Standard? Or 30 - nopay? The image shows "Days for Salary: 30.0" and "No Pay Days: 0.0".
// Let's assume Days for Salary is fixed or calculated. If fixed 30:
$days_for_salary = 30.0 - $nopay_days;

// Earnings section (Repeated?)
$gross_salary = $paysheet['gross_pay'];

// Deductions
$epf = $paysheet['epf_8'];
$salary_advance = $paysheet['salary_advance'];
$staff_loan = $paysheet['staff_loan'];
$festival_advance = 0; // Not in DB
$fuel = 0; // Not in DB
$communication = $paysheet['communication_deduction'];
$other_nopay = $paysheet['nopay_other']; // Maybe add this to total deduction?

$total_deduction = $epf + $salary_advance + $staff_loan + $festival_advance + $fuel + $communication + $other_nopay;
$net_salary = $paysheet['net_pay'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Advice - <?= htmlspecialchars($paysheet['name']) ?></title>
    <style>
        body { font-family: 'Calibri', 'Arial', sans-serif; font-size: 14px; }
        .container { width: 700px; margin: 20px auto; border: 2px solid #000; padding: 20px; }
        .header { text-align: center; font-weight: bold; }
        .company-name { font-size: 18px; text-transform: uppercase; }
        .address { font-size: 14px; font-weight: normal; }
        .title { font-size: 16px; margin-top: 10px; text-decoration: underline; font-weight: bold; text-align: center;}
        .month-row { border: 1px solid #000; text-align: center; font-weight: bold; padding: 5px; margin-top: 10px; background: #fff; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        td { border: 1px solid #000; padding: 4px 8px; }
        .label { width: 50%; }
        .value { width: 50%; text-align: right; }
        .section-header { font-weight: bold; text-decoration: underline; }
        .total-row { font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .footer-note { font-size: 12px; margin-top: 10px; }
        .dashed { border-bottom: 1px dashed #ccc; }

        @media print {
            .no-print { display: none; }
            .container { border: none; width: 100%; margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Payslip</button>
    <a href="paysheet.php" style="margin-left: 20px;">Back to Paysheets</a>
</div>

<div class="container">
    <div class="header">
        <div class="company-name">SENSUS BPO Services (Pvt) Ltd</div>
        <div class="address">No-51/1A</div>
        <div class="address">Vihara Mw- Pepliyana,Borelasgamuwa</div>
        <div class="address">Tel +94 11 4472825 / +94 71 2929355</div>
    </div>

    <div class="title">PAY ADVICE</div>

    <div class="month-row">Month OF <?= htmlspecialchars($month_year) ?></div>

    <table>
        <tr>
            <td class="label">Name</td>
            <td class="value" style="text-align: left;"><?= htmlspecialchars($paysheet['name']) ?></td>
        </tr>
        <tr>
            <td class="label">EMP No</td>
            <td class="value" style="text-align: left;"><?= htmlspecialchars($paysheet['emp_code']) ?></td>
        </tr>
        <tr>
            <td class="label">Designation</td>
            <td class="value" style="text-align: left;"><?= htmlspecialchars($paysheet['designation']) ?></td>
        </tr>

        <!-- Top Earnings Block -->
        <tr>
            <td class="label">Basic Salary</td>
            <td class="value"><?= number_format($basic_salary, 2) ?></td>
        </tr>
        <tr>
            <td class="label">BRA</td>
            <td class="value"><?= number_format($bra, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Travelling Allowance</td>
            <td class="value"><?= number_format($travelling, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Vehicle Allowance</td>
            <td class="value"><?= number_format($vehicle, 2) ?></td>
        </tr>
        <tr class="total-row">
            <td class="label">Total</td>
            <td class="value"><?= number_format($total_income, 2) ?></td>
        </tr>

        <!-- Days Block -->
        <tr>
            <td class="label">No Pay Days</td>
            <td class="value"><?= number_format($nopay_days, 1) ?></td>
        </tr>
        <tr>
            <td class="label">Days for Salary</td>
            <td class="value"><?= number_format($days_for_salary, 1) ?></td>
        </tr>

        <!-- Earnings Block (Repeated/Gross) -->
        <tr>
            <td class="label section-header">Earnings</td>
            <td class="value"></td>
        </tr>
        <tr>
            <td class="label">Basic Salary</td>
            <td class="value"><?= number_format($basic_salary, 2) ?></td>
        </tr>
        <tr>
            <td class="label">BRA</td>
            <td class="value"><?= number_format($bra, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Travelling Allowance</td>
            <td class="value"><?= number_format($travelling, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Vehicle Allowance</td>
            <td class="value"><?= number_format($vehicle, 2) ?></td>
        </tr>
         <!-- Maybe Arrears should be here? -->
         <?php if ($paysheet['arrears'] > 0): ?>
         <tr>
            <td class="label">Arrears</td>
            <td class="value"><?= number_format($paysheet['arrears'], 2) ?></td>
        </tr>
         <?php endif; ?>

        <tr class="total-row">
            <td class="label">Gross Salary</td>
            <td class="value"><?= number_format($gross_salary, 2) ?></td>
        </tr>

        <!-- Deductions Block -->
        <tr>
            <td class="label section-header">Deductions</td>
            <td class="value"></td>
        </tr>
        <tr>
            <td class="label">EPF</td>
            <td class="value"><?= number_format($epf, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Salary Advances</td>
            <td class="value"><?= number_format($salary_advance, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Staff Loans</td>
            <td class="value"><?= number_format($staff_loan, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Festival Advance</td>
            <td class="value"><?= number_format($festival_advance, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Fuel</td>
            <td class="value"><?= number_format($fuel, 2) ?></td>
        </tr>
        <tr>
            <td class="label">Communication A/c</td>
            <td class="value"><?= number_format($communication, 2) ?></td>
        </tr>
        <?php if ($other_nopay > 0): ?>
        <tr>
            <td class="label">Other / No Pay</td>
            <td class="value"><?= number_format($other_nopay, 2) ?></td>
        </tr>
        <?php endif; ?>

        <tr class="total-row">
            <td class="label">Total Deduction</td>
            <td class="value"><?= number_format($total_deduction, 2) ?></td>
        </tr>
        <tr class="total-row" style="border-top: 4px double #000; border-bottom: 4px double #000;">
            <td class="label">Net Salary</td>
            <td class="value"><?= number_format($net_salary, 2) ?></td>
        </tr>

        <!-- EPF/ETF Contribution -->
        <tr>
            <td class="label dashed" style="border:none; border-bottom: 1px dashed #000;">12% EPF Contribution</td>
            <td class="value dashed" style="border:none; border-bottom: 1px dashed #000;">-</td>
        </tr>
        <tr>
            <td class="label dashed" style="border:none; border-bottom: 1px dashed #000;">3% ETF Contribution</td>
            <td class="value dashed" style="border:none; border-bottom: 1px dashed #000;">-</td>
        </tr>
    </table>
</div>

</body>
</html>
