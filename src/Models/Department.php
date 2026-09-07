<?php
/**
 * Department & Designation Model
 */

require_once __DIR__ . '/../Database.php';

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
        return Database::update('departments', [
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'description' => trim($data['description'] ?? ''),
            'head_id' => !empty($data['head_id']) ? (int)$data['head_id'] : null,
            'status' => $data['status'] ?? 'active'
        ], "id = ?", [$id]) > 0;
    }

    public static function createDesignation(array $data): int {
        return Database::insert('designations', [
            'department_id' => (int)$data['department_id'],
            'title' => trim($data['title']),
            'grade' => trim($data['grade'] ?? 'L1'),
            'description' => trim($data['description'] ?? '')
        ]);
    }
}
