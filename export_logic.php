<?php
// export_logic.php

// Check dependencies without dying immediately
$has_dependencies = file_exists(__DIR__ . '/vendor/autoload.php');

if ($has_dependencies) {
    require 'vendor/autoload.php';

    // Import classes only if dependencies exist
    // Note: We cannot use 'use' statement conditionally in global scope easily in a way that prevents error if class missing?
    // Actually 'use' is compile time, but autoload happens at runtime usage.
    // So 'use' is fine as long as we don't instantiate if missing.
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require_once 'functions.php';

function check_dependencies() {
    global $has_dependencies;
    return $has_dependencies;
}

function get_payment_data($month_year = null) {
    if (!$month_year) {
        $month_year = date('F Y');
    }

    if (preg_match('/^\d{4}-\d{2}$/', $month_year)) {
        $current_month_prefix = $month_year;
    } else {
        $current_month_prefix = date('Y-m');
    }

    $pdo = getDBConnection();
    $sql = "SELECT e.calling_name, e.employee_number, e.account_name, e.bank, e.branch, e.nic_no, e.account_number, e.area, p.amount, p.payment_date
            FROM payments p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.payment_date LIKE :month_prefix";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month_prefix' => "$current_month_prefix%"]);
    return $stmt->fetchAll();
}

function generate_fuel_allowance_report($month_year = null) {
    if (!check_dependencies()) {
        throw new Exception("Dependencies missing. Please run 'composer install'.");
    }

    $rows = get_payment_data($month_year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', "Fuel Allowance for the month of " . date('d.m.Y'));
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $headers = ['Calling Name', 'Emp #', 'Account Name', 'Bank', 'BRNCH', 'Account No', 'Area', 'Amount'];
    $columnLetter = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($columnLetter . '2', $header);
        $sheet->getStyle($columnLetter . '2')->getFont()->setBold(true);
        $columnLetter++;
    }

    $rowNum = 3;
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['calling_name']);
        $sheet->setCellValue('B' . $rowNum, $row['employee_number']);
        $sheet->setCellValue('C' . $rowNum, $row['account_name']);
        $sheet->setCellValue('D' . $rowNum, $row['bank']);
        $sheet->setCellValue('E' . $rowNum, $row['branch']);
        $sheet->setCellValue('F' . $rowNum, $row['account_number']);
        $sheet->setCellValue('G' . $rowNum, $row['area']);
        $sheet->setCellValue('H' . $rowNum, $row['amount']);
        $rowNum++;
    }

    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return $spreadsheet;
}

function generate_bank_transfer_report($month_year = null) {
    if (!check_dependencies()) {
        throw new Exception("Dependencies missing. Please run 'composer install'.");
    }

    $rows = get_payment_data($month_year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', "Reference\nNo (Max 8\nDigits)");
    $sheet->setCellValue('B1', "Staff / Supplier Account Name");
    $sheet->setCellValue('C1', "Bank Name\n(For the Banks not existing in the list\ntype only the Bank Code)");
    $sheet->setCellValue('D1', "Branch Name\n(For the Branches not\nexisting in the list type\nonly the Branch Code)");
    $sheet->setCellValue('E1', "Staff / Supplier\nCredit Account No");
    $sheet->setCellValue('F1', "Transaction Code");
    $sheet->setCellValue('G1', "Amount");
    $sheet->setCellValue('H1', "Rs.");

    $sheet->setCellValue('I1', "Value Date");
    $sheet->mergeCells('I1:K1');
    $sheet->setCellValue('I2', 'YYYY');
    $sheet->setCellValue('J2', 'MM');
    $sheet->setCellValue('K2', 'DD');

    $sheet->setCellValue('L1', "Remark");

    $mergeCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'L'];
    foreach ($mergeCols as $col) {
        $sheet->mergeCells("{$col}1:{$col}2");
    }

    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFD3D3D3']
        ]
    ];
    $sheet->getStyle('A1:L2')->applyFromArray($headerStyle);

    $rowNum = 3;
    foreach ($rows as $row) {
        $refNo = substr(preg_replace('/\D/', '', $row['employee_number']), 0, 8);

        $sheet->setCellValue('A' . $rowNum, $refNo);
        $sheet->setCellValue('B' . $rowNum, $row['account_name']);
        $sheet->setCellValue('C' . $rowNum, $row['bank']);
        $sheet->setCellValue('D' . $rowNum, $row['branch']);
        $sheet->setCellValue('E' . $rowNum, $row['account_number']);
        $sheet->setCellValue('F' . $rowNum, '23');
        $sheet->setCellValue('G' . $rowNum, $row['amount']);
        $sheet->setCellValue('H' . $rowNum, '00');

        $pDate = strtotime($row['payment_date']);
        $sheet->setCellValue('I' . $rowNum, date('Y', $pDate));
        $sheet->setCellValue('J' . $rowNum, date('m', $pDate));
        $sheet->setCellValue('K' . $rowNum, date('d', $pDate));
        $sheet->setCellValue('L' . $rowNum, "Fuel Allowance");

        $rowNum++;
    }

    foreach (range('A', 'L') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return $spreadsheet;
}

function generate_paysheet_excel($month_year = null) {
    if (!check_dependencies()) {
        throw new Exception("Dependencies missing. Please run 'composer install'.");
    }

    $rows = get_paysheet_data($month_year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = [
        'No.', 'Emp Code', 'Name', 'Designation', 'WORKING PLACE / PROJECT', 'PROJECT HEAD', 'STATUS',
        'BASIC SALARY', 'Travelling Allowance', 'Vehicle Allowance', 'Arreas', 'GROSS PAY',
        'SALARY NOPAY DAYS', 'NOPAY FOR BUDGETORY', 'NOPAY FOR OTHER', 'EPF 8%',
        'Salary Advance', 'Staff Loan', 'Communication Deduction', 'NET PAY', 'HOLD'
    ];

    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;
    }

    $rowNum = 2;
    $count = 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $rowNum, $count++);
        $sheet->setCellValue('B' . $rowNum, $row['emp_code']);
        $sheet->setCellValue('C' . $rowNum, $row['name']);
        $sheet->setCellValue('D' . $rowNum, $row['designation']);
        $sheet->setCellValue('E' . $rowNum, $row['working_place']);
        $sheet->setCellValue('F' . $rowNum, $row['project_head']);
        $sheet->setCellValue('G' . $rowNum, $row['status']);
        $sheet->setCellValue('H' . $rowNum, $row['basic_salary']);
        $sheet->setCellValue('I' . $rowNum, $row['travelling_allowance']);
        $sheet->setCellValue('J' . $rowNum, $row['vehicle_allowance']);
        $sheet->setCellValue('K' . $rowNum, $row['arrears']);
        $sheet->setCellValue('L' . $rowNum, $row['gross_pay']);
        $sheet->setCellValue('M' . $rowNum, $row['salary_nopay_days']);
        $sheet->setCellValue('N' . $rowNum, $row['nopay_budgetory']);
        $sheet->setCellValue('O' . $rowNum, $row['nopay_other']);
        $sheet->setCellValue('P' . $rowNum, $row['epf_8']);
        $sheet->setCellValue('Q' . $rowNum, $row['salary_advance']);
        $sheet->setCellValue('R' . $rowNum, $row['staff_loan']);
        $sheet->setCellValue('S' . $rowNum, $row['communication_deduction']);
        $sheet->setCellValue('T' . $rowNum, $row['net_pay']);
        $sheet->setCellValue('U' . $rowNum, $row['hold']);

        $rowNum++;
    }

    foreach (range('A', 'U') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    return $spreadsheet;
}
?>
