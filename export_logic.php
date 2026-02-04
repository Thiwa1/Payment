<?php
// export_logic.php

$has_dependencies = file_exists(__DIR__ . '/vendor/autoload.php');

if ($has_dependencies) {
    require 'vendor/autoload.php';
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

function get_payment_data($criteria = null) {
    // $criteria can be schedule_id (int) or date string (YYYY-MM...)

    $pdo = getDBConnection();

    if (is_numeric($criteria)) {
        // By Schedule ID
        $sql = "SELECT e.calling_name, e.employee_number, e.account_name, e.bank, e.branch, e.nic_no, e.account_number, e.area, p.amount, p.payment_date
                FROM payments p
                JOIN employees e ON p.employee_id = e.id
                WHERE p.schedule_id = :criteria";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':criteria' => $criteria]);
    } else {
        // By Date (Legacy/Fallback)
        if (!$criteria) $criteria = date('F Y');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $criteria)) {
            $term = $criteria;
        } elseif (preg_match('/^\d{4}-\d{2}$/', $criteria)) {
            $term = "$criteria%";
        } else {
            $term = date('Y-m') . "%";
        }

        $sql = "SELECT e.calling_name, e.employee_number, e.account_name, e.bank, e.branch, e.nic_no, e.account_number, e.area, p.amount, p.payment_date
                FROM payments p
                JOIN employees e ON p.employee_id = e.id
                WHERE p.payment_date LIKE :term";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':term' => $term]);
    }
    return $stmt->fetchAll();
}

function export_to_csv($headers, $rows, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($rows as $row) { fputcsv($output, $row); }
    fclose($output);
    exit;
}

function generate_fuel_allowance_report($criteria = null) {
    $rows = get_payment_data($criteria);

    if (!check_dependencies()) {
        $headers = ['Calling Name', 'Emp #', 'Account Name', 'Bank', 'Branch', 'Account No', 'Area', 'Amount'];
        $csv_rows = [];
        foreach ($rows as $row) {
            $csv_rows[] = [$row['calling_name'], $row['employee_number'], $row['account_name'], $row['bank'], $row['branch'], $row['account_number'], $row['area'], $row['amount']];
        }
        export_to_csv($headers, $csv_rows, "fuel_allowance_$criteria.csv");
        return;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', "Fuel Allowance Report");
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $headers = ['Calling Name', 'Emp #', 'Account Name', 'Bank', 'BRNCH', 'Account No', 'Area', 'Amount'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '2', $header);
        $sheet->getStyle($col . '2')->getFont()->setBold(true);
        $col++;
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
    foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    return $spreadsheet;
}

function generate_bank_transfer_report($criteria = null) {
    $rows = get_payment_data($criteria);

    // Attempt to get schedule details for remark if criteria is ID
    $remarkText = "Sensus BPO Fuel";
    if (is_numeric($criteria)) {
        $sch = get_schedule_by_id($criteria);
        if ($sch) {
            $ts = strtotime($sch['date']);
            $remarkText .= " " . date('F Y', $ts);
        }
    } else {
        $ts = strtotime($criteria ?? date('Y-m-d'));
        $remarkText .= " " . date('F Y', $ts);
    }

    if (!check_dependencies()) {
        $headers = ['Ref No', 'Account Name', 'Bank', 'Branch', 'Credit Acc No', 'Tran Code', 'Amount', 'Rs', 'YYYY', 'MM', 'DD', 'Remark'];
        $csv_rows = [];
        $count = 1;
        foreach ($rows as $row) {
            $pDate = strtotime($row['payment_date']);
            $csv_rows[] = [$count++, $row['account_name'], $row['bank'], $row['branch'], $row['account_number'], '052', $row['amount'], '00', date('Y', $pDate), date('m', $pDate), date('d', $pDate), $remarkText];
        }
        export_to_csv($headers, $csv_rows, "bank_transfer_$criteria.csv");
        return;
    }

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
    $sheet->setCellValue('I2', 'YYYY'); $sheet->setCellValue('J2', 'MM'); $sheet->setCellValue('K2', 'DD');
    $sheet->setCellValue('L1', "Remark");

    $mergeCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'L'];
    foreach ($mergeCols as $col) $sheet->mergeCells("{$col}1:{$col}2");

    $headerStyle = ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD3D3D3']]];
    $sheet->getStyle('A1:L2')->applyFromArray($headerStyle);

    $rowNum = 3;
    $count = 1;
    foreach ($rows as $row) {
        $sheet->setCellValue('A' . $rowNum, $count++);
        $sheet->setCellValue('B' . $rowNum, $row['account_name']);
        $sheet->setCellValue('C' . $rowNum, $row['bank']);
        $sheet->setCellValue('D' . $rowNum, $row['branch']);
        $sheet->setCellValue('E' . $rowNum, $row['account_number']);
        $sheet->setCellValue('F' . $rowNum, '052');
        $sheet->setCellValue('G' . $rowNum, $row['amount']);
        $sheet->setCellValue('H' . $rowNum, '00');

        $pDate = strtotime($row['payment_date']);
        $sheet->setCellValue('I' . $rowNum, date('Y', $pDate));
        $sheet->setCellValue('J' . $rowNum, date('m', $pDate));
        $sheet->setCellValue('K' . $rowNum, date('d', $pDate));
        $sheet->setCellValue('L' . $rowNum, $remarkText);
        $rowNum++;
    }
    foreach (range('A', 'L') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    return $spreadsheet;
}

function generate_paysheet_excel($month_year = null) {
    // Uses get_paysheet_data which handles numeric ID or date string
    $rows = get_paysheet_data($month_year);

    if (!check_dependencies()) {
        $headers = ['No.', 'Emp Code', 'Name', 'Designation', 'WORKING PLACE / PROJECT', 'PROJECT HEAD', 'STATUS', 'BASIC SALARY', 'Travelling Allowance', 'Vehicle Allowance', 'Arreas', 'GROSS PAY', 'SALARY NOPAY DAYS', 'NOPAY FOR BUDGETORY', 'NOPAY FOR OTHER', 'EPF 8%', 'Salary Advance', 'Staff Loan', 'Communication Deduction', 'NET PAY', 'HOLD'];
        $csv_rows = [];
        $count = 1;
        foreach ($rows as $row) {
            $csv_rows[] = [$count++, $row['emp_code'], $row['name'], $row['designation'], $row['working_place'], $row['project_head'], $row['status'], $row['basic_salary'], $row['travelling_allowance'], $row['vehicle_allowance'], $row['arrears'], $row['gross_pay'], $row['salary_nopay_days'], $row['nopay_budgetory'], $row['nopay_other'], $row['epf_8'], $row['salary_advance'], $row['staff_loan'], $row['communication_deduction'], $row['net_pay'], $row['hold']];
        }
        export_to_csv($headers, $csv_rows, "paysheet_$month_year.csv");
        return;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $headers = ['No.', 'Emp Code', 'Name', 'Designation', 'WORKING PLACE / PROJECT', 'PROJECT HEAD', 'STATUS', 'BASIC SALARY', 'Travelling Allowance', 'Vehicle Allowance', 'Arreas', 'GROSS PAY', 'SALARY NOPAY DAYS', 'NOPAY FOR BUDGETORY', 'NOPAY FOR OTHER', 'EPF 8%', 'Salary Advance', 'Staff Loan', 'Communication Deduction', 'NET PAY', 'HOLD'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }
    $rowNum = 2; $count = 1;
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
    foreach (range('A', 'U') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    return $spreadsheet;
}
?>
