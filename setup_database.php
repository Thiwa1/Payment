<?php
require_once 'db_connect.php';

try {
    $pdo = getDBConnection();

    // Create employees table
    $sql_employees = "CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        calling_name TEXT NOT NULL,
        account_name TEXT NOT NULL,
        employee_number TEXT UNIQUE NOT NULL,
        bank TEXT NOT NULL,
        branch TEXT NOT NULL,
        nic_no TEXT UNIQUE NOT NULL,
        account_number TEXT UNIQUE NOT NULL,
        area TEXT
    )";
    $pdo->exec($sql_employees);
    echo "Table 'employees' created successfully.<br>";

    // Create payments table
    $sql_payments = "CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        payment_date TEXT NOT NULL,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )";
    $pdo->exec($sql_payments);
    echo "Table 'payments' created successfully.<br>";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
