<?php
/**
 * Department & Designation Model
 */

require_once __DIR__ . '/../Database.php';

class Department {
    public static function getAll(): array {
        $sql = "SELECT d.*, COUNT(e.id) AS employee_count,
                       CONCAT(m.first_name, ' ', m.last_name) AS head_name
                FROM departments d
                LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                LEFT JOIN employees m ON d.head_id = m.id
                GROUP BY d.id
                ORDER BY d.name ASC";
        return Database::fetchAll($sql);
    }

    public static function findById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM departments WHERE id = ?", [$id]);
    }

    public static function getDesignations(?int $departmentId = null): array {
        $sql = "SELECT des.*, d.name AS department_name, COUNT(e.id) AS employee_count
                FROM designations des
                JOIN departments d ON des.department_id = d.id
                LEFT JOIN employees e ON des.id = e.designation_id AND e.status = 'active'";
        $params = [];
        if ($departmentId !== null) {
            $sql .= " WHERE des.department_id = ?";
            $params[] = $departmentId;
        }
        $sql .= " GROUP BY des.id ORDER BY d.name ASC, des.title ASC";
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
