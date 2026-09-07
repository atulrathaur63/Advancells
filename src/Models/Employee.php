<?php
/**
 * Employee Model
 */

require_once __DIR__ . '/../Database.php';

class Employee {
    public static function getAll(array $filters = []): array {
        $sql = "SELECT e.*, u.role, u.status AS user_status,
                       d.name AS department_name, des.title AS designation_title,
                       CONCAT(m.first_name, ' ', m.last_name) AS manager_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN employees m ON e.manager_id = m.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (e.emp_code LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.phone LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY e.id ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array {
        $sql = "SELECT e.*, u.role, u.status AS user_status, u.avatar,
                       d.name AS department_name, des.title AS designation_title, des.grade,
                       CONCAT(m.first_name, ' ', m.last_name) AS manager_name, m.email AS manager_email,
                       s.basic_salary, s.hra, s.special_allowance, s.conveyance, s.medical_allowance,
                       s.other_allowances, s.pf_deduction, s.esi_deduction, s.professional_tax, s.tds,
                       s.gross_salary, s.net_salary
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN employees m ON e.manager_id = m.id
                LEFT JOIN salary_structures s ON e.id = s.employee_id
                WHERE e.id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    public static function findByUserId(int $userId): ?array {
         $sql = "SELECT e.*, u.role, d.name AS department_name, des.title AS designation_title
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.user_id = ?";
        return Database::fetchOne($sql, [$userId]);
    }

    public static function getManagers(): array {
        $sql = "SELECT e.id, CONCAT(e.first_name, ' ', e.last_name) AS name, des.title AS designation
                FROM employees e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE u.role IN ('super_admin', 'hr_admin', 'manager') AND e.status = 'active'
                ORDER BY e.first_name ASC";
        return Database::fetchAll($sql);
    }

    public static function create(array $data, string $password, string $role): int {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 1. Create User
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userId = Database::insert('users', [
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => strtolower(trim($data['email'])),
                'password' => $hashedPassword,
                'role' => $role,
                'status' => 'active'
            ]);

            // 2. Create Employee
            $empData = [
                'user_id' => $userId,
                'emp_code' => strtoupper(trim($data['emp_code'])),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => trim($data['phone'] ?? ''),
                'gender' => $data['gender'] ?? 'male',
                'dob' => !empty($data['dob']) ? $data['dob'] : null,
                'blood_group' => $data['blood_group'] ?? null,
                'marital_status' => $data['marital_status'] ?? 'single',
                'address' => $data['address'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
                'designation_id' => !empty($data['designation_id']) ? (int)$data['designation_id'] : null,
                'manager_id' => !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                'date_of_joining' => !empty($data['date_of_joining']) ? $data['date_of_joining'] : date('Y-m-d'),
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'status' => 'active',
                'bank_name' => $data['bank_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'ifsc_code' => $data['ifsc_code'] ?? null,
                'pan_number' => $data['pan_number'] ?? null,
                'uan_number' => $data['uan_number'] ?? null
            ];
            $empId = Database::insert('employees', $empData);

            // 3. Allocate default leaves for current year
            $year = (int)date('Y');
            $leaveTypes = Database::fetchAll("SELECT * FROM leave_types");
            foreach ($leaveTypes as $lt) {
                Database::insert('leave_balances', [
                    'employee_id' => $empId,
                    'leave_type_id' => $lt['id'],
                    'year' => $year,
                    'total_allocated' => $lt['days_per_year'],
                    'used' => 0.0,
                    'pending' => 0.0
                ]);
            }

            // 4. Default salary structure if provided
            if (isset($data['basic_salary']) && (float)$data['basic_salary'] > 0) {
                $basic = (float)$data['basic_salary'];
                $hra = (float)($data['hra'] ?? ($basic * 0.40));
                $special = (float)($data['special_allowance'] ?? 0);
                $conveyance = (float)($data['conveyance'] ?? 0);
                $medical = (float)($data['medical_allowance'] ?? 0);
                $other = (float)($data['other_allowances'] ?? 0);
                $pf = (float)($data['pf_deduction'] ?? ($basic * 0.12));
                $esi = (float)($data['esi_deduction'] ?? 0);
                $pt = (float)($data['professional_tax'] ?? 200);
                $tds = (float)($data['tds'] ?? 0);
                $gross = $basic + $hra + $special + $conveyance + $medical + $other;
                $net = $gross - ($pf + $esi + $pt + $tds);

                Database::insert('salary_structures', [
                    'employee_id' => $empId,
                    'basic_salary' => $basic,
                    'hra' => $hra,
                    'special_allowance' => $special,
                    'conveyance' => $conveyance,
                    'medical_allowance' => $medical,
                    'other_allowances' => $other,
                    'pf_deduction' => $pf,
                    'esi_deduction' => $esi,
                    'professional_tax' => $pt,
                    'tds' => $tds,
                    'gross_salary' => $gross,
                    'net_salary' => $net,
                    'effective_date' => date('Y-m-d')
                ]);
            }

            $pdo->commit();
            Database::logActivity(Auth::id(), 'CREATE', 'EMPLOYEES', "Created employee {$empData['emp_code']}");
            return $empId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data, ?string $role = null): bool {
        $emp = self::findById($id);
        if (!$emp) return false;

        $empData = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']), 
            'phone' => trim($data['phone'] ?? ''),
            'gender' => $data['gender'] ?? 'male',
            'dob' => !empty($data['dob']) ? $data['dob'] : null,
            'blood_group' => $data['blood_group'] ?? null,
            'marital_status' => $data['marital_status'] ?? 'single',
            'address' => $data['address'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            'designation_id' => !empty($data['designation_id']) ? (int)$data['designation_id'] : null,
            'manager_id' => !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
            'date_of_joining' => $data['date_of_joining'] ?? $emp['date_of_joining'],
            'employment_type' => $data['employment_type'] ?? $emp['employment_type'],
            'status' => $data['status'] ?? $emp['status'],
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'ifsc_code' => $data['ifsc_code'] ?? null,
            'pan_number' => $data['pan_number'] ?? null,
            'uan_number' => $data['uan_number'] ?? null
        ];

        Database::update('employees', $empData, "id = ?", [$id]);

        // Update User name & role if specified
        $userData = [
            'name' => trim($data['first_name'] . ' ' . $data['last_name'])
        ];
        if (!empty($role)) {
            $userData['role'] = $role;
        }
        if (!empty($data['status'])) {
            $userData['status'] = ($data['status'] === 'active') ? 'active' : 'inactive';
        }
        Database::update('users', $userData, "id = ?", [$emp['user_id']]);

        Database::logActivity(Auth::id(), 'UPDATE', 'EMPLOYEES', "Updated employee {$emp['emp_code']}");
        return true;
    }

    public static function getStats(): array {
        $total = Database::fetchOne("SELECT count(*) as count FROM employees")['count'] ?? 0;
        $active = Database::fetchOne("SELECT count(*) as count FROM employees WHERE status = 'active'")['count'] ?? 0;
        $departments = Database::fetchOne("SELECT count(*) as count FROM departments WHERE status = 'active'")['count'] ?? 0;
        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'departments' => (int)$departments
        ];
    }

    /**
     * Get Complete Organization Hierarchy Tree
     */
     public static function getOrgHierarchy(?int $departmentId = null): array {
        $sql = "SELECT e.id, e.emp_code, e.first_name, e.last_name, e.email, e.phone, e.gender,
                       e.department_id, e.designation_id, e.manager_id, e.date_of_joining, e.status, e.employment_type, 
                       u.role, u.avatar,
                       d.name AS department_name, d.code AS department_code,
                       des.title AS designation_title, des.grade,
                       CONCAT(m.first_name, ' ', m.last_name) AS manager_name
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN employees m ON e.manager_id = m.id
                WHERE e.status = 'active'";
        
        $params = [];

        if ($departmentId !== null && $departmentId > 0) {
            $sql .= " AND e.department_id = ?";
            $params[] = $departmentId;
        }

        $sql .= " ORDER BY (e.manager_id IS NULL) DESC, e.id ASC";
        $employees = Database::fetchAll($sql, $params);

        // Map by ID
        $nodesById = [];
        foreach ($employees as $emp) {
            $emp['name'] = $emp['first_name'] . ' ' . $emp['last_name'];
            $emp['children'] = [];
            $emp['direct_reports_count'] = 0;
            $emp['total_reports_count'] = 0;
            $nodesById[$emp['id']] = $emp;
        }

        // Build parent-child relationships
        $tree = [];
        foreach ($nodesById as $id => &$node) {
            $managerId = $node['manager_id'];
            if ($managerId !== null && isset($nodesById[$managerId])) {
                $nodesById[$managerId]['children'][] = &$node;
                $nodesById[$managerId]['direct_reports_count']++;
            } else {
                // Root node (e.g. CEO or filtered top parent)
                $tree[] = &$node;
            }
        }
        unset($node);

        // Recursive function to calculate total subtree headcounts
        $calcSubtree = function(&$item) use (&$calcSubtree) {
            $count = count($item['children']);
            foreach ($item['children'] as &$child) {
                $count += $calcSubtree($child);
            }
            $item['total_reports_count'] = $count;
            return $count;
        };

        foreach ($tree as &$root) {
            $calcSubtree($root);
        }
        unset($root);

        return [
            'tree' => $tree,
            'flat' => array_values($nodesById)
        ];
    }

    /**
     * Reassign reporting manager with circular validation
     */
    public static function updateManager(int $employeeId, ?int $managerId): bool {
        if ($managerId === $employeeId) {
            throw new Exception("An employee cannot be their own reporting manager.");
        }

        // Circular reporting validation
        if ($managerId !== null && $managerId > 0) {
            $curr = $managerId;
            $visited = [$employeeId];
            while ($curr !== null) {
                if (in_array($curr, $visited, true)) {
                    throw new Exception("Circular reporting detected! An employee cannot report to their own subordinate.");
                }
                $visited[] = $curr;
                $parent = Database::fetchOne("SELECT manager_id FROM employees WHERE id = ?", [$curr]);
                $curr = $parent ? $parent['manager_id'] : null;
            }
        } else {
            $managerId = null;
        }

        Database::query("UPDATE employees SET manager_id = ? WHERE id = ?", [$managerId, $employeeId]);
        
        $emp = self::findById($employeeId);
        $mgr = $managerId ? self::findById($managerId) : null;
        $empName = $emp ? "{$emp['first_name']} {$emp['last_name']}" : "Employee #{$employeeId}";
        $mgrName = $mgr ? "{$mgr['first_name']} {$mgr['last_name']}" : "Direct / None";

        Database::logActivity(Auth::id(), 'UPDATE_MANAGER', 'ORGANIZATION', "Updated reporting manager for {$empName} to {$mgrName}");
        return true;
    }

    /**
     * Get Org Overview Summary Statistics
     */
    public static function getOrgStats(): array {
        $total = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM employees WHERE status = 'active'")['c'] ?? 0);
        $managers = (int)(Database::fetchOne("SELECT COUNT(DISTINCT manager_id) AS c FROM employees WHERE manager_id IS NOT NULL AND status = 'active'")['c'] ?? 0);
        $departments = (int)(Database::fetchOne("SELECT COUNT(DISTINCT department_id) AS c FROM employees WHERE status = 'active'")['c'] ?? 0);
        $topExecs = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM employees WHERE manager_id IS NULL AND status = 'active'")['c'] ?? 0);

        return [
            'total_workforce'   => $total,
            'total_managers'    => $managers,
            'departments_count' => $departments,
            'top_executives'    => $topExecs
        ];
    }
}
