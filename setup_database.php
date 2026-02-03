<?php
require_once 'db_adapter.php';

try {
    $pdo = getDBConnection();

    // Create paysheets table matching the new requirements
    // Columns: date, emp_code, name, designation, working_place, project_head, status, basic_salary,
    // travelling_allowance, vehicle_allowance, arrears, gross_pay, salary_nopay_days, nopay_budgetory,
    // nopay_other, epf_8, salary_advance, staff_loan, communication_deduction, net_pay, hold

    // Using TEXT for most fields to be safe with CSV data, REAL for money/numbers.
    // In MySQL we might use DECIMAL(10,2), but REAL is fine for SQLite/generic PDO.

    $sql_paysheets = "CREATE TABLE IF NOT EXISTS paysheets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        emp_code TEXT,
        name TEXT,
        designation TEXT,
        working_place TEXT,
        project_head TEXT,
        status TEXT,
        basic_salary REAL,
        travelling_allowance REAL,
        vehicle_allowance REAL,
        arrears REAL,
        gross_pay REAL,
        salary_nopay_days REAL,
        nopay_budgetory REAL,
        nopay_other REAL,
        epf_8 REAL,
        salary_advance REAL,
        staff_loan REAL,
        communication_deduction REAL,
        net_pay REAL,
        hold TEXT
    )";

    // Note: 'AUTOINCREMENT' is SQLite specific. MySQL uses 'AUTO_INCREMENT'.
    // If running on MySQL, this query might fail if passed directly.
    // I should adjust syntax based on driver.

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver == 'mysql') {
         $sql_paysheets = "CREATE TABLE IF NOT EXISTS paysheets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            emp_code VARCHAR(50),
            name VARCHAR(100),
            designation VARCHAR(100),
            working_place VARCHAR(100),
            project_head VARCHAR(100),
            status VARCHAR(50),
            basic_salary DECIMAL(10,2),
            travelling_allowance DECIMAL(10,2),
            vehicle_allowance DECIMAL(10,2),
            arrears DECIMAL(10,2),
            gross_pay DECIMAL(10,2),
            salary_nopay_days DECIMAL(10,2),
            nopay_budgetory DECIMAL(10,2),
            nopay_other DECIMAL(10,2),
            epf_8 DECIMAL(10,2),
            salary_advance DECIMAL(10,2),
            staff_loan DECIMAL(10,2),
            communication_deduction DECIMAL(10,2),
            net_pay DECIMAL(10,2),
            hold VARCHAR(50)
        )";
    }

    $pdo->exec($sql_paysheets);
    echo "Table 'paysheets' created successfully using $driver driver.<br>";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
