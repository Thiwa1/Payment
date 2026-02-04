<?php
if (isset($_GET['type'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="template_' . $_GET['type'] . '.csv"');
    $output = fopen('php://output', 'w');

    if ($_GET['type'] == 'employees') {
        fputcsv($output, ['Calling Name', 'Account Name', 'Emp No', 'Bank', 'Branch', 'NIC', 'Acc No', 'Area']);
    } elseif ($_GET['type'] == 'paysheet') {
        fputcsv($output, [
            'No.', 'Emp/ Code', 'Name', 'Designation', 'WORKING PLACE / PROJECT', 'PROJECT HEAD', 'STATUS ACTIVE / NOT',
            'BASIC SALARY', 'Travelling Allowance', 'Vehicle Allowance', 'Arreas', 'GROSS PAY SALARY',
            'NOPAY DAYS', 'NOPAY FOR BUDGETORY', 'NOPAY FOR OTHER', 'EPF 8%',
            'Salary /Advance', 'Staff Loan', 'Communication/ Deduction', 'NET PAY', 'HOLD'
        ]);
    }

    fclose($output);
    exit;
}
?>
