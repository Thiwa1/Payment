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

// Data Mapping
$basic_salary = $paysheet['basic_salary'];
$bra = 0;
$travelling = $paysheet['travelling_allowance'];
$vehicle = $paysheet['vehicle_allowance'];
$arrears = $paysheet['arrears'];

$total_income = $basic_salary + $bra + $travelling + $vehicle + $arrears;

$nopay_days = $paysheet['salary_nopay_days'];
$days_for_salary = 30.0 - $nopay_days;

$gross_salary = $paysheet['gross_pay'];

$epf = $paysheet['epf_8'];
$salary_advance = $paysheet['salary_advance'];
$staff_loan = $paysheet['staff_loan'];
$festival_advance = 0;
$fuel = 0;
$communication = $paysheet['communication_deduction'];
$other_nopay = $paysheet['nopay_other'];

$total_deduction = $epf + $salary_advance + $staff_loan + $festival_advance + $fuel + $communication + $other_nopay;
$net_salary = $paysheet['net_pay'];

// Contribution Logic
$epf_contribution = $basic_salary * 0.12;
$etf_contribution = $basic_salary * 0.03;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Advice - <?= htmlspecialchars($paysheet['name']) ?></title>
    <style>
        @page {
            size: A5 portrait; /* Half A4 */
            margin: 10mm;
        }
        body {
            font-family: 'Calibri', 'Arial', sans-serif;
            font-size: 11px; /* Slightly smaller to fit */
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }
        .container {
            width: 148mm; /* A5 Width */
            /* height: 210mm; A5 Height */
            margin: 0 auto;
            background-color: white;
            padding: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc; /* For screen visibility */
        }
        .header { text-align: center; font-weight: bold; }
        .company-name { font-size: 14px; text-transform: uppercase; margin-bottom: 2px; }
        .address { font-size: 10px; font-weight: normal; margin-bottom: 1px; }
        .title { font-size: 12px; margin-top: 5px; text-decoration: underline; font-weight: bold; text-align: center;}
        .month-row { border: 1px solid #000; text-align: center; font-weight: bold; padding: 3px; margin-top: 5px; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        td { border: 1px solid #000; padding: 2px 5px; vertical-align: middle; }
        .label { width: 60%; }
        .value { width: 40%; text-align: right; }
        .section-header { font-weight: bold; text-decoration: underline; background-color: #f9f9f9; }
        .total-row { font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .dashed { border-bottom: 1px dashed #ccc; }

        @media print {
            body { background-color: white; }
            .no-print { display: none; }
            .container { border: none; width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; padding: 10px;">
    <button onclick="window.print()" style="padding: 8px 16px; font-size: 14px; cursor: pointer;">Print Payslip (A5)</button>
    <a href="paysheet.php" style="margin-left: 20px;">Back</a>
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

        <!-- Earnings Block -->
        <tr>
            <td class="label section-header">Earnings</td>
            <td class="value section-header"></td>
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
         <?php if ($arrears > 0): ?>
         <tr>
            <td class="label">Arrears</td>
            <td class="value"><?= number_format($arrears, 2) ?></td>
        </tr>
         <?php endif; ?>

        <tr class="total-row">
            <td class="label">Gross Salary</td>
            <td class="value"><?= number_format($gross_salary, 2) ?></td>
        </tr>

        <!-- Deductions Block -->
        <tr>
            <td class="label section-header">Deductions</td>
            <td class="value section-header"></td>
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

        <!-- Footer -->
        <tr>
            <td class="label dashed" style="border:none; border-bottom: 1px dashed #000; padding-top: 10px;">12% EPF Contribution</td>
            <td class="value dashed" style="border:none; border-bottom: 1px dashed #000; padding-top: 10px;"><?= number_format($epf_contribution, 2) ?></td>
        </tr>
        <tr>
            <td class="label dashed" style="border:none; border-bottom: 1px dashed #000;">3% ETF Contribution</td>
            <td class="value dashed" style="border:none; border-bottom: 1px dashed #000;"><?= number_format($etf_contribution, 2) ?></td>
        </tr>
    </table>
</div>

</body>
</html>
