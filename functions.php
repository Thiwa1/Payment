<?php
require_once 'db_adapter.php';

// ... (Existing Functions) ...
function add_employee($data) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE employee_number = :en");
    $stmt->execute([':en' => $data['employee_number']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $sql = "UPDATE employees SET calling_name = :calling_name, account_name = :account_name, bank = :bank, branch = :branch, nic_no = :nic_no, account_number = :account_number, area = :area WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([':calling_name' => $data['calling_name'], ':account_name' => $data['account_name'], ':bank' => $data['bank'], ':branch' => $data['branch'], ':nic_no' => $data['nic_no'], ':account_number' => $data['account_number'], ':area' => $data['area'], ':id' => $existing['id']]);
            return true;
        } catch (PDOException $e) { return false; }
    } else {
        $sql = "INSERT INTO employees (calling_name, account_name, employee_number, bank, branch, nic_no, account_number, area) VALUES (:calling_name, :account_name, :employee_number, :bank, :branch, :nic_no, :account_number, :area)";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([':calling_name' => $data['calling_name'], ':account_name' => $data['account_name'], ':employee_number' => $data['employee_number'], ':bank' => $data['bank'], ':branch' => $data['branch'], ':nic_no' => $data['nic_no'], ':account_number' => $data['account_number'], ':area' => $data['area']]);
            return true;
        } catch (PDOException $e) { return false; }
    }
}
function get_employees() { $pdo = getDBConnection(); try { $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC"); return $stmt->fetchAll(); } catch (PDOException $e) { return []; } }
function get_employee_by_search($term) { $pdo = getDBConnection(); $term = "%$term%"; $stmt = $pdo->prepare("SELECT * FROM employees WHERE calling_name LIKE :term OR employee_number LIKE :term OR nic_no LIKE :term"); $stmt->execute([':term' => $term]); return $stmt->fetchAll(); }
function get_employee_by_id($id) { $pdo = getDBConnection(); $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id"); $stmt->execute([':id' => $id]); return $stmt->fetch(); }
function get_employee_by_number($emp_no) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE employee_number = :en");
    $stmt->execute([':en' => $emp_no]);
    return $stmt->fetchColumn();
}
function bulk_upload_employees($file_path) {
    if (!file_exists($file_path)) return ['count' => 0, 'errors' => ["File not found"]];
    $handle = fopen($file_path, "r");
    if ($handle === FALSE) return ['count' => 0, 'errors' => ["Cannot open file"]];
    $header = fgetcsv($handle);
    $count = 0; $errors = []; $rowNum = 2;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (empty($data) || (count($data) == 1 && empty($data[0]))) continue;
        if (count($data) < 8) { $errors[] = "Row $rowNum: Not enough columns."; $rowNum++; continue; }
        $emp_data = ['calling_name' => $data[0], 'account_name' => $data[1], 'employee_number' => $data[2], 'bank' => $data[3], 'branch' => $data[4], 'nic_no' => $data[5], 'account_number' => $data[6], 'area' => $data[7]];
        if (add_employee($emp_data)) $count++; else $errors[] = "Row $rowNum: Failed to add/update.";
        $rowNum++;
    }
    fclose($handle);
    return ['count' => $count, 'errors' => $errors];
}

// --- Payment Functions ---

function save_payment($employee_id, $amount, $payment_date, $schedule_id) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO payments (schedule_id, employee_id, amount, payment_date) VALUES (:schedule_id, :employee_id, :amount, :payment_date)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':schedule_id' => $schedule_id, ':employee_id' => $employee_id, ':amount' => $amount, ':payment_date' => $payment_date]);
}

function get_recent_payments($limit = 20) {
    $pdo = getDBConnection();
    $sql = "SELECT p.id, p.payment_date, p.amount, e.calling_name, e.employee_number, s.name as schedule_name
            FROM payments p
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN schedules s ON p.schedule_id = s.id
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

function get_payments_by_schedule($schedule_id) {
    $pdo = getDBConnection();
    $sql = "SELECT e.calling_name, e.employee_number, e.account_name, e.bank, e.branch, e.nic_no, e.account_number, e.area, p.amount, p.payment_date
            FROM payments p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.schedule_id = :sid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':sid' => $schedule_id]);
    return $stmt->fetchAll();
}

function get_employee_payment_history($employee_id) {
    $pdo = getDBConnection();
    $sql = "SELECT p.id, p.payment_date, p.amount, s.name as schedule_name
            FROM payments p
            LEFT JOIN schedules s ON p.schedule_id = s.id
            WHERE p.employee_id = :eid
            ORDER BY p.payment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':eid' => $employee_id]);
    return $stmt->fetchAll();
}

// --- Schedule & Paysheet Functions ---

function create_schedule($name, $date) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO schedules (name, date, created_at) VALUES (:name, :date, :created_at)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([':name' => $name, ':date' => $date, ':created_at' => date('Y-m-d H:i:s')]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) { return false; }
}

function update_schedule($id, $name, $date) {
    $pdo = getDBConnection();
    $sql = "UPDATE schedules SET name = :name, date = :date WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    try {
        return $stmt->execute([':name' => $name, ':date' => $date, ':id' => $id]);
    } catch (PDOException $e) { return false; }
}

function delete_schedule($id) {
    $pdo = getDBConnection();
    try {
        // Delete dependent payments first
        $stmt = $pdo->prepare("DELETE FROM payments WHERE schedule_id = :id");
        $stmt->execute([':id' => $id]);

        // Delete dependent paysheets first
        $stmt = $pdo->prepare("DELETE FROM paysheets WHERE schedule_id = :id");
        $stmt->execute([':id' => $id]);

        // Delete schedule
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) { return false; }
}

function get_schedules() {
    $pdo = getDBConnection();
    try {
        $stmt = $pdo->query("SELECT * FROM schedules ORDER BY date DESC, id DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function get_schedule_by_id($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function bulk_upload_paysheet($file_path, $schedule_id, $date) {
    if (!file_exists($file_path)) return false;
    $handle = fopen($file_path, "r");
    if ($handle === FALSE) return false;
    $header = fgetcsv($handle);
    $pdo = getDBConnection();
    $sql = "INSERT INTO paysheets (schedule_id, date, emp_code, name, designation, working_place, project_head, status, basic_salary, travelling_allowance, vehicle_allowance, arrears, gross_pay, salary_nopay_days, nopay_budgetory, nopay_other, epf_8, salary_advance, staff_loan, communication_deduction, net_pay, hold) VALUES (:schedule_id, :date, :emp_code, :name, :designation, :working_place, :project_head, :status, :basic_salary, :travelling_allowance, :vehicle_allowance, :arrears, :gross_pay, :salary_nopay_days, :nopay_budgetory, :nopay_other, :epf_8, :salary_advance, :staff_loan, :communication_deduction, :net_pay, :hold)";
    $stmt = $pdo->prepare($sql);
    $count = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        try {
            $stmt->execute([':schedule_id' => $schedule_id, ':date' => $date, ':emp_code' => $data[1]??'', ':name' => $data[2]??'', ':designation' => $data[3]??'', ':working_place' => $data[4]??'', ':project_head' => $data[5]??'', ':status' => $data[6]??'', ':basic_salary' => clean_number($data[7]??0), ':travelling_allowance' => clean_number($data[8]??0), ':vehicle_allowance' => clean_number($data[9]??0), ':arrears' => clean_number($data[10]??0), ':gross_pay' => clean_number($data[11]??0), ':salary_nopay_days' => clean_number($data[12]??0), ':nopay_budgetory' => clean_number($data[13]??0), ':nopay_other' => clean_number($data[14]??0), ':epf_8' => clean_number($data[15]??0), ':salary_advance' => clean_number($data[16]??0), ':staff_loan' => clean_number($data[17]??0), ':communication_deduction' => clean_number($data[18]??0), ':net_pay' => clean_number($data[19]??0), ':hold' => $data[20]??'']);
            $count++;
        } catch (PDOException $e) { continue; }
    }
    fclose($handle);
    return $count;
}
function clean_number($val) { if (is_numeric($val)) return $val; return floatval(preg_replace('/[^\d.-]/', '', $val)); }
function get_paysheet_data($month_year) {
    $pdo = getDBConnection();
    if (is_numeric($month_year)) {
        $stmt = $pdo->prepare("SELECT * FROM paysheets WHERE schedule_id = :id ORDER BY id ASC");
        $stmt->execute([':id' => $month_year]);
        return $stmt->fetchAll();
    } else {
        $term = "$month_year%";
        $stmt = $pdo->prepare("SELECT * FROM paysheets WHERE date LIKE :term ORDER BY id ASC");
        $stmt->execute([':term' => $term]);
        return $stmt->fetchAll();
    }
}
function get_paysheet_entry($id) { $pdo = getDBConnection(); $stmt = $pdo->prepare("SELECT * FROM paysheets WHERE id = :id"); $stmt->execute([':id' => $id]); return $stmt->fetch(); }
?>
