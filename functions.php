<?php
require_once 'db_adapter.php';

// Old functions kept for reference but new logic is prioritized

function bulk_upload_paysheet($file_path, $date) {
    if (!file_exists($file_path)) {
        return false;
    }

    $handle = fopen($file_path, "r");
    if ($handle === FALSE) {
        return false;
    }

    // Expected Header Order based on prompt:
    // No., Emp Code, Name, Designation, WORKING PLACE / PROJECT, PROJECT HEAD, STATUS, BASIC SALARY,
    // Travelling Allowance, Vehicle Allowance, Arreas, GROSS PAY, SALARY NOPAY DAYS, NOPAY FOR BUDGETORY,
    // NOPAY FOR OTHER, EPF 8%, Salary Advance, Staff Loan, Communication Deduction, NET PAY, HOLD

    // We skip the first row (header)
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
        // Basic validation: ensure we have enough columns?
        // Or assume the CSV is well formed. The list has about 21 columns.
        // Data[0] is No. (skip or ignore)

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
            // Log error or continue
            continue;
        }
    }
    fclose($handle);
    return $count;
}

function clean_number($val) {
    if (is_numeric($val)) return $val;
    // Remove commas, currency symbols etc if present
    return floatval(preg_replace('/[^\d.-]/', '', $val));
}

function get_paysheet_data($month_year) {
    // $month_year format YYYY-MM
    $pdo = getDBConnection();
    // Assuming 'date' column in DB is stored as YYYY-MM-DD or just a string date.
    // If we pass YYYY-MM, we should match roughly.
    $term = "$month_year%";
    $stmt = $pdo->prepare("SELECT * FROM paysheets WHERE date LIKE :term ORDER BY id ASC");
    $stmt->execute([':term' => $term]);
    return $stmt->fetchAll();
}

// Keeping old functions just in case, but they use 'db_connect.php' logic.
// We should update them to use 'db_adapter.php' if we want to support the old interface too.
// For now, I'll update the getDBConnection call in them implicitly by including db_adapter.
// But wait, the old functions called `getDBConnection` which is now in `db_adapter.php`.
// So they should work fine if I just require `db_adapter.php` instead of `db_connect.php`.
// Wait, `db_connect.php` was creating a new function with same name?
// I should delete `db_connect.php` to avoid conflict or update it to be a wrapper.
// `db_adapter.php` defines `getDBConnection`.
// The previous step created `db_adapter.php`. `db_connect.php` still exists?
// Yes. I should remove `db_connect.php` or make it require `db_adapter.php`.

?>
