<?php
/**
 * Resignation & Exit Workflow Model
 */

require_once __DIR__ . '/../Database.php';

class Resignation {
    public static function getByEmployeeId(int $employeeId): ?array {
        return Database::fetchOne("SELECT * FROM resignations WHERE employee_id = ?", [$employeeId]);
    }

    public static function findById(int $id): ?array {
        $sql = "SELECT r.*, e.user_id, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       e.department_id, e.manager_id
                FROM resignations r
                JOIN employees e ON r.employee_id = e.id
                WHERE r.id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    public static function countAll(?array $employeeIds = null): int {
        $where = "";
        $params = [];
        if ($employeeIds !== null) {
            if (empty($employeeIds)) {
                return 0;
            }
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
            $where = " WHERE r.employee_id IN ($placeholders)";
            $params = $employeeIds;
        }

        $sql = "SELECT count(*) AS cnt
                FROM resignations r
                JOIN employees e ON r.employee_id = e.id
                {$where}";
        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }

    public static function getAll(?array $employeeIds = null, ?int $limit = null, ?int $offset = null): array {
        $where = "";
        $params = [];
        if ($employeeIds !== null) {
            if (empty($employeeIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
            $where = " WHERE r.employee_id IN ($placeholders)";
            $params = $employeeIds;
        }

        $sql = "SELECT r.*, e.user_id, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name, des.title AS designation_title,
                       e.date_of_joining
                FROM resignations r
                JOIN employees e ON r.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                {$where}
                ORDER BY r.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return Database::fetchAll($sql, $params);
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
        Database::update('resignations', $data, "id = ?", [$id]);

        // If status is completed (offboarded / settled), deactivate user and mark employee as resigned
        if ($status === 'completed') {
            $res = self::findById($id);
            if ($res) {
                $exitDate = !empty($approvedDate) ? $approvedDate : date('Y-m-d');
                Database::update('employees', ['status' => 'resigned', 'date_of_exit' => $exitDate], "id = ?", [$res['employee_id']]);
                if (!empty($res['user_id'])) {
                    Database::update('users', ['status' => 'inactive'], "id = ?", [$res['user_id']]);
                }
            }
        }
        return true;
    }
}
