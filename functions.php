<?php
require_once 'db_adapter.php';

// --- Employee Management Functions ---

function add_employee($data) {
    $pdo = getDBConnection();

    $sql = "INSERT INTO employees (calling_name, account_name, employee_number, bank, branch, nic_no, account_number, area)
            VALUES (:calling_name, :account_name, :employee_number, :bank, :branch, :nic_no, :account_number, :area)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':calling_name' => $data['calling_name'],
            ':account_name' => $data['account_name'],
            ':employee_number' => $data['employee_number'],
            ':bank' => $data['bank'],
            ':branch' => $data['branch'],
            ':nic_no' => $data['nic_no'],
            ':account_number' => $data['account_number'],
            ':area' => $data['area']
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function get_employees() {
    $pdo = getDBConnection();
    try {
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function get_employee_by_search($term) {
    $pdo = getDBConnection();
    $term = "%$term%";
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE calling_name LIKE :term OR employee_number LIKE :term OR nic_no LIKE :term");
    $stmt->execute([':term' => $term]);
    return $stmt->fetchAll();
}

function get_employee_by_id($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function bulk_upload_employees($file_path) {
    if (!file_exists($file_path)) {
        return ['count' => 0, 'errors' => ["File not found"]];
    }

    $handle = fopen($file_path, "r");
    if ($handle === FALSE) {
        return ['count' => 0, 'errors' => ["Cannot open file"]];
    }

    $header = fgetcsv($handle);

    $count = 0;
    $errors = [];
    $rowNum = 2; // Starting after header

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (empty($data) || (count($data) == 1 && empty($data[0]))) {
            continue;
        }

        if (count($data) < 8) {
            $errors[] = "Row $rowNum: Not enough columns (found " . count($data) . ", expected 8).";
            $rowNum++;
            continue;
        }

        $emp_data = [
            'calling_name' => $data[0],
            'account_name' => $data[1],
            'employee_number' => $data[2],
            'bank' => $data[3],
            'branch' => $data[4],
            'nic_no' => $data[5],
            'account_number' => $data[6],
            'area' => $data[7]
        ];

        if (add_employee($emp_data)) {
            $count++;
        } else {
            $errors[] = "Row $rowNum: Failed to add (Duplicate Emp No/NIC/Acc No or invalid data).";
        }
        $rowNum++;
    }
    fclose($handle);
    return ['count' => $count, 'errors' => $errors];
}

function save_payment($employee_id, $amount, $payment_date) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO payments (employee_id, amount, payment_date) VALUES (:employee_id, :amount, :payment_date)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':employee_id' => $employee_id,
        ':amount' => $amount,
        ':payment_date' => $payment_date
    ]);
}

function get_recent_payments($limit = 20) {
    $pdo = getDBConnection();
    // Use SQL to join with employees
    $sql = "SELECT p.id, p.payment_date, p.amount, e.calling_name, e.employee_number
            FROM payments p
            JOIN employees e ON p.employee_id = e.id
            ORDER BY p.id DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function delete_payment($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}

// --- Paysheet Functions ---

function bulk_upload_paysheet($file_path, $date) {
    if (!file_exists($file_path)) {
        return false;
    }

    $handle = fopen($file_path, "r");
    if ($handle === FALSE) {
        return false;
    }

    $header = fgetcsv($handle);

    $pdo = getDBConnection();

    $sql = "INSERT INTO paysheets (
        date, emp_code, name, designation, working_place, project_head, status,
        basic_salary, travelling_allowance, vehicle_allowance, arrears, gross_pay,
        salary_nopay_days, nopay_budgetory, nopay_other, epf_8, salary_advance,
        staff_loan, communication_deduction, net_pay, hold
    ) VALUES (
        :date, :emp_code, :name, :designation, :working_place, :project_head, :status,
        :basic_salary, :travelling_allowance, :vehicle_allowance, :arrears, :gross_pay,
        :salary_nopay_days, :nopay_budgetory, :nopay_other, :epf_8, :salary_advance,
        :staff_loan, :communication_deduction, :net_pay, :hold
    )";
    $stmt = $pdo->prepare($sql);

    $count = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        try {
            $stmt->execute([
                ':date' => $date,
                ':emp_code' => $data[1] ?? '',
                ':name' => $data[2] ?? '',
                ':designation' => $data[3] ?? '',
                ':working_place' => $data[4] ?? '',
                ':project_head' => $data[5] ?? '',
                ':status' => $data[6] ?? '',
                ':basic_salary' => clean_number($data[7] ?? 0),
                ':travelling_allowance' => clean_number($data[8] ?? 0),
                ':vehicle_allowance' => clean_number($data[9] ?? 0),
                ':arrears' => clean_number($data[10] ?? 0),
                ':gross_pay' => clean_number($data[11] ?? 0),
                ':salary_nopay_days' => clean_number($data[12] ?? 0),
                ':nopay_budgetory' => clean_number($data[13] ?? 0),
                ':nopay_other' => clean_number($data[14] ?? 0),
                ':epf_8' => clean_number($data[15] ?? 0),
                ':salary_advance' => clean_number($data[16] ?? 0),
                ':staff_loan' => clean_number($data[17] ?? 0),
                ':communication_deduction' => clean_number($data[18] ?? 0),
                ':net_pay' => clean_number($data[19] ?? 0),
                ':hold' => $data[20] ?? ''
            ]);
            $count++;
        } catch (PDOException $e) {
            continue;
        }
    }
    fclose($handle);
    return $count;
}

function clean_number($val) {
    if (is_numeric($val)) return $val;
    return floatval(preg_replace('/[^\d.-]/', '', $val));
}

function get_paysheet_data($month_year) {
    $pdo = getDBConnection();
    $term = "$month_year%";
    $stmt = $pdo->prepare("SELECT * FROM paysheets WHERE date LIKE :term ORDER BY id ASC");
    $stmt->execute([':term' => $term]);
    return $stmt->fetchAll();
}

function get_paysheet_entry($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM paysheets WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}
?>
