<?php
function ensure_tables_exist($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    $auto_inc = ($driver === 'mysql') ? "INT AUTO_INCREMENT PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT";

    // 1. Employees Table
    $sql_employees = "CREATE TABLE IF NOT EXISTS employees (
        id $auto_inc,
        calling_name TEXT NOT NULL,
        account_name TEXT NOT NULL,
        employee_number TEXT NOT NULL,
        bank TEXT NOT NULL,
        branch TEXT NOT NULL,
        nic_no TEXT NOT NULL,
        account_number TEXT NOT NULL,
        area TEXT,
        CONSTRAINT unique_emp_no UNIQUE (employee_number),
        CONSTRAINT unique_nic UNIQUE (nic_no),
        CONSTRAINT unique_acc_no UNIQUE (account_number)
    )";

    if ($driver === 'mysql') {
        $sql_employees = "CREATE TABLE IF NOT EXISTS employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            calling_name VARCHAR(100) NOT NULL,
            account_name VARCHAR(100) NOT NULL,
            employee_number VARCHAR(50) NOT NULL,
            bank VARCHAR(100) NOT NULL,
            branch VARCHAR(100) NOT NULL,
            nic_no VARCHAR(50) NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            area VARCHAR(100),
            UNIQUE (employee_number),
            UNIQUE (nic_no),
            UNIQUE (account_number)
        )";
    }
    $pdo->exec($sql_employees);

    // 4. Schedules Table (Moved up as dependency)
    $sql_schedules = "CREATE TABLE IF NOT EXISTS schedules (
        id $auto_inc,
        name TEXT NOT NULL UNIQUE,
        date TEXT NOT NULL,
        created_at TEXT
    )";
    if ($driver === 'mysql') {
        $sql_schedules = "CREATE TABLE IF NOT EXISTS schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    }
    $pdo->exec($sql_schedules);

    // 2. Payments Table (Updated with schedule_id)
    $sql_payments = "CREATE TABLE IF NOT EXISTS payments (
        id $auto_inc,
        schedule_id INTEGER,
        employee_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        payment_date TEXT NOT NULL,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        FOREIGN KEY (schedule_id) REFERENCES schedules(id)
    )";
    if ($driver === 'mysql') {
        $sql_payments = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schedule_id INT,
            employee_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATE NOT NULL,
            FOREIGN KEY (employee_id) REFERENCES employees(id),
            FOREIGN KEY (schedule_id) REFERENCES schedules(id)
        )";
    }
    $pdo->exec($sql_payments);

    // Migration: Add schedule_id to payments if missing
    try {
        if ($driver === 'mysql') {
            $pdo->exec("ALTER TABLE payments ADD COLUMN schedule_id INT AFTER id");
            $pdo->exec("ALTER TABLE payments ADD FOREIGN KEY (schedule_id) REFERENCES schedules(id)");
        } else {
            $pdo->exec("ALTER TABLE payments ADD COLUMN schedule_id INTEGER REFERENCES schedules(id)");
        }
    } catch (PDOException $e) { }

    // 3. Paysheets Table
    $sql_paysheets = "CREATE TABLE IF NOT EXISTS paysheets (
        id $auto_inc,
        schedule_id INTEGER,
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
        hold TEXT,
        FOREIGN KEY (schedule_id) REFERENCES schedules(id)
    )";

    if ($driver === 'mysql') {
         $sql_paysheets = "CREATE TABLE IF NOT EXISTS paysheets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schedule_id INT,
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
            hold VARCHAR(50),
            FOREIGN KEY (schedule_id) REFERENCES schedules(id)
        )";
    }
    $pdo->exec($sql_paysheets);

    try {
        if ($driver === 'mysql') {
            $pdo->exec("ALTER TABLE paysheets ADD COLUMN schedule_id INT AFTER id");
            $pdo->exec("ALTER TABLE paysheets ADD FOREIGN KEY (schedule_id) REFERENCES schedules(id)");
        } else {
            $pdo->exec("ALTER TABLE paysheets ADD COLUMN schedule_id INTEGER REFERENCES schedules(id)");
        }
    } catch (PDOException $e) { }
}
?>
