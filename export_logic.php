<?php
require 'vendor/autoload.php';
require_once 'functions.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Keep old export functions for reference? The prompt implies replacing.
// I will just add the new one.

function generate_paysheet_excel($month_year = null) {
    $rows = get_paysheet_data($month_year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    // No., Emp Code, Name, Designation, WORKING PLACE / PROJECT, PROJECT HEAD, STATUS, BASIC SALARY,
    // Travelling Allowance, Vehicle Allowance, Arreas, GROSS PAY, SALARY NOPAY DAYS, NOPAY FOR BUDGETORY,
    // NOPAY FOR OTHER, EPF 8%, Salary Advance, Staff Loan, Communication Deduction, NET PAY, HOLD

    $headers = [
        'No.', 'Emp Code', 'Name', 'Designation', 'WORKING PLACE / PROJECT', 'PROJECT HEAD', 'STATUS',
        'BASIC SALARY', 'Travelling Allowance', 'Vehicle Allowance', 'Arreas', 'GROSS PAY',
        'SALARY NOPAY DAYS', 'NOPAY FOR BUDGETORY', 'NOPAY FOR OTHER', 'EPF 8%',
        'Salary Advance', 'Staff Loan', 'Communication Deduction', 'NET PAY', 'HOLD'
    ];

    // Header Row
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;
    }

    // Data
    $rowNum = 2;
    $count = 1;
    foreach ($rows as $row) {
        // Map DB columns to Excel columns order
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

    // Auto size
    foreach (range('A', 'U') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    return $spreadsheet;
}
?>
