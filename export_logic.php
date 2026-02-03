<?php
require 'vendor/autoload.php';
require_once 'db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

function get_payment_data($month_year = null) {
    if (!$month_year) {
        $month_year = date('F Y');
    }

    // We expect $month_year to be something like "January 2024" or just rely on current month if null.
    // In a real scenario, we might want to pass YYYY-MM explicitly.
    // Let's support YYYY-MM if passed, otherwise default to current month.

    if (preg_match('/^\d{4}-\d{2}$/', $month_year)) {
        $current_month_prefix = $month_year;
    } else {
        $current_month_prefix = date('Y-m');
    }

    $pdo = getDBConnection();
    // Join employees and payments
    $sql = "SELECT e.calling_name, e.employee_number, e.account_name, e.bank, e.branch, e.nic_no, e.account_number, e.area, p.amount, p.payment_date
            FROM payments p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.payment_date LIKE :month_prefix";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month_prefix' => "$current_month_prefix%"]);
    return $stmt->fetchAll();
}

function generate_fuel_allowance_report($month_year = null) {
    $rows = get_payment_data($month_year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Main Header
    $sheet->setCellValue('A1', "Fuel Allowance for the month of " . date('d.m.Y')); // Requirement says "current date" in header
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Column Headers
    $headers = ['Calling Name', 'Emp #', 'Account Name', 'Bank', 'BRNCH', 'Account No', 'Area', 'Amount'];
    $columnLetter = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($columnLetter . '2', $header);
        $sheet->getStyle($columnLetter . '2')->getFont()->setBold(true);
        $columnLetter++;
    }

    // Data
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

    // Auto size columns
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return $spreadsheet;
}

function generate_bank_transfer_report($month_year = null) {
    $rows = get_payment_data($month_year);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers from the image
    // Row 1 merge setup
    // Reference No (Max 8 Digits) | Staff / Supplier Account Name | Bank Name (For the Banks...) | Branch Name (For the Branches...) | Staff / Supplier Credit Account No | Transaction Code | Amount | Rs. | Value Date | Remark

    // The "Value Date" column in the image seems to span 3 sub-columns: YYYY | MM | DD

    // Let's set up the headers
    $sheet->setCellValue('A1', "Reference\nNo (Max 8\nDigits)");
    $sheet->setCellValue('B1', "Staff / Supplier Account Name");
    $sheet->setCellValue('C1', "Bank Name\n(For the Banks not existing in the list\ntype only the Bank Code)");
    $sheet->setCellValue('D1', "Branch Name\n(For the Branches not\nexisting in the list type\nonly the Branch Code)");
    $sheet->setCellValue('E1', "Staff / Supplier\nCredit Account No");
    $sheet->setCellValue('F1', "Transaction Code");
    $sheet->setCellValue('G1', "Amount");
    $sheet->setCellValue('H1', "Rs.");

    $sheet->setCellValue('I1', "Value Date");
    $sheet->mergeCells('I1:K1'); // Merge YYYY MM DD header area
    $sheet->setCellValue('I2', 'YYYY');
    $sheet->setCellValue('J2', 'MM');
    $sheet->setCellValue('K2', 'DD');

    $sheet->setCellValue('L1', "Remark");

    // Merge rows for the single-column headers (A1:A2, B1:B2, etc. except Value Date)
    $mergeCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'L'];
    foreach ($mergeCols as $col) {
        $sheet->mergeCells("{$col}1:{$col}2");
    }

    // Styling
    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFD3D3D3'] // Light grey
        ]
    ];
    $sheet->getStyle('A1:L2')->applyFromArray($headerStyle);

    // Data
    $rowNum = 3;
    foreach ($rows as $row) {
        // Reference No: max 8 digits. Maybe use Emp No or just a counter?
        // Using Employee Number for now, assuming it fits or truncating.
        $refNo = substr(preg_replace('/\D/', '', $row['employee_number']), 0, 8);

        $sheet->setCellValue('A' . $rowNum, $refNo);
        $sheet->setCellValue('B' . $rowNum, $row['account_name']);

        // Bank Name: The instruction says "For the Banks not existing in the list type only the Bank Code".
        // We will just put the Bank value from DB.
        $sheet->setCellValue('C' . $rowNum, $row['bank']);

        // Branch Name: Same logic
        $sheet->setCellValue('D' . $rowNum, $row['branch']);

        $sheet->setCellValue('E' . $rowNum, $row['account_number']);

        // Transaction Code: Unknown requirement. Hardcoding '23' or empty for now?
        // Usually bank transfers have a code like '23' (SLIPS) or 'CEFT'.
        // I'll leave it empty or put a placeholder '23' as is common in SL.
        $sheet->setCellValue('F' . $rowNum, '23');

        $sheet->setCellValue('G' . $rowNum, $row['amount']);
        $sheet->setCellValue('H' . $rowNum, '00'); // Cents? Image says "Rs." then "Amount"? Or "Amount" then "Rs"?
        // Looking at the image: Amount column, then a small "Rs." column (maybe currency code or just label? No, usually it's cents or currency).
        // Wait, standard SL bank text file formats often separate Amount and Cents, OR "Amount" is integer and "Rs." is meaningless label?
        // Actually, usually "Rs" column might be for Cents or just fixed text "LKR".
        // Let's assume G is Amount (Integer part?) and H is "Rs." column which might be cents or empty.
        // Let's put '00' in H for cents if G is the main amount.

        // Value Date
        // $row['payment_date'] is YYYY-MM-DD
        $pDate = strtotime($row['payment_date']);
        $sheet->setCellValue('I' . $rowNum, date('Y', $pDate));
        $sheet->setCellValue('J' . $rowNum, date('m', $pDate));
        $sheet->setCellValue('K' . $rowNum, date('d', $pDate));

        $sheet->setCellValue('L' . $rowNum, "Fuel Allowance"); // Remark

        $rowNum++;
    }

    // Auto size
    foreach (range('A', 'L') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return $spreadsheet;
}
?>
