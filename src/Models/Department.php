<?php
/**
 * Department & Designation Model
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';

class Department {
    public static function getAll(): array {
        $sql = "SELECT d.*,
                       (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.status = 'active') AS employee_count,
                       (SELECT CONCAT(m.first_name, ' ', m.last_name) FROM employees m WHERE m.id = d.head_id) AS head_name
                FROM departments d
                ORDER BY d.name ASC";
        return Database::fetchAll($sql);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM departments WHERE id = ?", [$id]);
    }

    public static function getDesignations(?int $departmentId = null): array {
        $sql = "SELECT des.*, d.name AS department_name,
                       (SELECT COUNT(*) FROM employees e WHERE e.designation_id = des.id AND e.status = 'active') AS employee_count
                FROM designations des
                JOIN departments d ON des.department_id = d.id";
        $params = [];
        if ($departmentId !== null) {
            $sql .= " WHERE des.department_id = ?";
            $params[] = $departmentId;
        }
        $sql .= " ORDER BY d.name ASC, des.title ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): int {
        return Database::insert('departments', [
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'description' => trim($data['description'] ?? ''),
            'head_id' => !empty($data['head_id']) ? (int)$data['head_id'] : null,
            'status' => $data['status'] ?? 'active'
        ]);
    }

    public static function update(int $id, array $data): bool {
        Database::update('departments', [
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'description' => trim($data['description'] ?? ''),
            'head_id' => !empty($data['head_id']) ? (int)$data['head_id'] : null,
            'status' => $data['status'] ?? 'active'
        ], "id = ?", [$id]);
        return true;
    }

    public static function createDesignation(array $data): int {
        return Database::insert('designations', [
            'department_id' => (int)$data['department_id'],
            'title' => trim($data['title']),
            'grade' => trim($data['grade'] ?? 'L1'),
            'description' => trim($data['description'] ?? '')
        ]);
    }

    public static function updateDesignation(int $id, array $data): bool {
        Database::update('designations', [
            'department_id' => (int)$data['department_id'],
            'title' => trim($data['title']),
            'grade' => trim($data['grade'] ?? 'L1'),
            'description' => trim($data['description'] ?? '')
        ], "id = ?", [$id]);
        return true;
    }

    public static function canDelete(int $id): array {
        $dept = self::findById($id);
        if (!$dept) {
            return ['can_delete' => false, 'reason' => 'Department not found.'];
        }

        $empCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM employees WHERE department_id = ?", [$id])['c'] ?? 0);
        if ($empCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Cannot delete department '{$dept['name']}': {$empCount} employee profile(s) are assigned to it. Please reassign those employees first."
            ];
        }

        $desigCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM designations WHERE department_id = ?", [$id])['c'] ?? 0);
        if ($desigCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Cannot delete department '{$dept['name']}': {$desigCount} designation(s) exist under it. Please remove or reassign designations first."
            ];
        }

        return ['can_delete' => true, 'reason' => ''];
    }

    public static function delete(int $id): bool {
        $check = self::canDelete($id);
        if (!$check['can_delete']) {
            return false;
        }
        return Database::query("DELETE FROM departments WHERE id = ?", [$id])->rowCount() > 0;
    }

    public static function canDeleteDesignation(int $id): array {
        $desig = Database::fetchOne("SELECT des.*, d.name AS department_name FROM designations des JOIN departments d ON des.department_id = d.id WHERE des.id = ?", [$id]);
        if (!$desig) {
            return ['can_delete' => false, 'reason' => 'Designation not found.'];
        }

        $empCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM employees WHERE designation_id = ?", [$id])['c'] ?? 0);
        if ($empCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Cannot delete designation '{$desig['title']}': {$empCount} employee(s) currently hold this title. Please reassign them first."
            ];
        }

        return ['can_delete' => true, 'reason' => ''];
    }

    public static function deleteDesignation(int $id): bool {
        $check = self::canDeleteDesignation($id);
        if (!$check['can_delete']) {
            return false;
        }
        return Database::query("DELETE FROM designations WHERE id = ?", [$id])->rowCount() > 0;
    }
}
