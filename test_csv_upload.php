<?php
require_once 'functions.php';

echo "Testing bulk upload...\n";
$count = bulk_upload_employees('sample_employees.csv');

echo "Uploaded $count employees.\n";

$employees = get_employees();
echo "Total employees in DB: " . count($employees) . "\n";
?>
