<?php
require_once 'db_connect.php';

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
        // In a real app, handle duplicate errors gracefully
        return false;
    }
}

function get_employees() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
    return $stmt->fetchAll();
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

function bulk_upload_employees($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }

    $handle = fopen($file_path, "r");
    if ($handle === FALSE) {
        return false;
    }

    $header = fgetcsv($handle); // Skip header or map it? Assuming standard order for now or we map it.
    // Let's assume the CSV columns match the DB columns order: Calling Name, Account Name, Emp No, Bank, Branch, NIC, Acc No, Area

    $count = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (count($data) < 8) continue;

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
        }
    }
    fclose($handle);
    return $count;
}
?>
