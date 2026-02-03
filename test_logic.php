<?php
require_once 'functions.php';

echo "Testing add_employee...\n";
$emp_data = [
    'calling_name' => 'John Doe',
    'account_name' => 'J. Doe',
    'employee_number' => 'EMP001',
    'bank' => 'Test Bank',
    'branch' => 'Main',
    'nic_no' => '123456789V',
    'account_number' => 'ACC123',
    'area' => 'Colombo'
];

if (add_employee($emp_data)) {
    echo "Employee added successfully.\n";
} else {
    echo "Failed to add employee (might already exist).\n";
}

echo "Testing get_employee_by_search...\n";
$results = get_employee_by_search('John');
if (count($results) > 0) {
    echo "Found " . count($results) . " employee(s).\n";
    $emp_id = $results[0]['id'];

    echo "Testing save_payment...\n";
    if (save_payment($emp_id, 5000.00, date('Y-m-d'))) {
        echo "Payment saved successfully.\n";
    } else {
        echo "Failed to save payment.\n";
    }
} else {
    echo "No employees found.\n";
}
?>
