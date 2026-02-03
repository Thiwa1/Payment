<?php
require_once 'export_logic.php';

echo "Testing Fuel Allowance Report...\n";
$spreadsheet1 = generate_fuel_allowance_report();
$writer1 = new Xlsx($spreadsheet1);
$writer1->save('fuel_allowance.xlsx');
echo "fuel_allowance.xlsx created.\n";

echo "Testing Bank Transfer Report...\n";
$spreadsheet2 = generate_bank_transfer_report();
$writer2 = new Xlsx($spreadsheet2);
$writer2->save('bank_transfer.xlsx');
echo "bank_transfer.xlsx created.\n";
?>
