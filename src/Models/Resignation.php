<?php
/**
 * Resignation & Exit Workflow Model
 */

require_once __DIR__ . '/../Database.php';

class Resignation {
    public static function getByEmployeeId(int $employeeId): ?array {
        return Database::fetchOne("SELECT * FROM resignations WHERE employee_id = ?", [$employeeId]);
    }

    public static function getAll(): array {
        $sql = "SELECT r.*, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name, des.title AS designation_title,
                       e.date_of_joining
                FROM resignations r
                JOIN employees e ON r.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                ORDER BY r.created_at DESC";
        return Database::fetchAll($sql);
    }

    public static function submit(int $employeeId, string $reason, string $desiredLastDay): int {
        return Database::insert('resignations', [
            'employee_id' => $employeeId,
            'resignation_date' => date('Y-m-d'),
            'reason' => trim($reason),
            'desired_last_working_day' => $desiredLastDay,
            'status' => 'submitted'
        ]);
    }

    public static function updateStatus(int $id, string $status, ?string $approvedDate = null, ?string $exitNotes = null): bool {
        $data = ['status' => $status];
        if ($approvedDate) {
            $data['approved_last_working_day'] = $approvedDate;
        }
        if ($exitNotes) {
            $data['exit_interview_notes'] = $exitNotes;
        }
        return Database::update('resignations', $data, "id = ?", [$id]) > 0;
    }
}
