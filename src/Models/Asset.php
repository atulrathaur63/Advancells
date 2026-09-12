<?php
/**
 * Company Asset & Hardware Inventory Model
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Notification.php';

class Asset {
    public static function getCategories(): array {
        return [
            'laptop'         => ['label' => 'Laptop', 'icon' => 'fa-laptop', 'color' => '#2563eb'],
            'desktop'        => ['label' => 'Desktop PC', 'icon' => 'fa-desktop', 'color' => '#0891b2'],
            'mobile_tablet'  => ['label' => 'Mobile / Tablet', 'icon' => 'fa-tablet-screen-button', 'color' => '#7c3aed'],
            'monitor'        => ['label' => 'Monitor / Display', 'icon' => 'fa-tv', 'color' => '#0d9488'],
            'lab_equipment'  => ['label' => 'Biotech / Lab Equipment', 'icon' => 'fa-flask-vial', 'color' => '#93206c'],
            'peripheral'     => ['label' => 'Peripheral / Accessory', 'icon' => 'fa-keyboard', 'color' => '#d97706'],
            'access_card'    => ['label' => 'Access Card / Badge', 'icon' => 'fa-id-badge', 'color' => '#059669'],
            'other'          => ['label' => 'Other Property', 'icon' => 'fa-box-archive', 'color' => '#64748b']
        ];
    }

    public static function getConditions(): array {
        return [
            'brand_new' => ['label' => 'Brand New', 'class' => 'badge-success'],
            'good'      => ['label' => 'Good Condition', 'class' => 'badge-info'],
            'fair'      => ['label' => 'Fair (Wear & Tear)', 'class' => 'badge-warning'],
            'damaged'   => ['label' => 'Damaged / Needs Service', 'class' => 'badge-danger']
        ];
    }

    public static function countAll(array $filters = []): int {
        $sql = "SELECT COUNT(*) AS total
                FROM assets a
                LEFT JOIN employees e ON a.current_employee_id = e.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND a.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND a.condition = ?";
            $params[] = $filters['condition'];
        }

        if (!empty($filters['employee_id'])) {
            $sql .= " AND a.current_employee_id = ?";
            $params[] = (int)$filters['employee_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (a.asset_code LIKE ? OR a.name LIKE ? OR a.brand LIKE ? OR a.model LIKE ? OR a.serial_number LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR e.emp_code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term]);
        }

        $row = Database::fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    public static function getAll(array $filters = [], ?int $limit = null, ?int $offset = null): array {
        $sql = "SELECT a.*, 
                       e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       e.email AS employee_email, e.phone AS employee_phone,
                       d.name AS department_name, des.title AS designation_title,
                       al.id AS active_allocation_id, al.allocated_date, al.expected_return_date
                FROM assets a
                LEFT JOIN employees e ON a.current_employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN asset_allocations al ON a.id = al.asset_id AND al.status = 'active'
                WHERE 1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND a.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND a.condition = ?";
            $params[] = $filters['condition'];
        }

        if (!empty($filters['employee_id'])) {
            $sql .= " AND a.current_employee_id = ?";
            $params[] = (int)$filters['employee_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (a.asset_code LIKE ? OR a.name LIKE ? OR a.brand LIKE ? OR a.model LIKE ? OR a.serial_number LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR e.emp_code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term, $term]);
        }

        $sql .= " ORDER BY a.id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array {
        $sql = "SELECT a.*, 
                       e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       e.email AS employee_email, e.phone AS employee_phone,
                       d.name AS department_name, des.title AS designation_title,
                       al.id AS active_allocation_id, al.allocated_date, al.expected_return_date, al.allocation_notes
                FROM assets a
                LEFT JOIN employees e ON a.current_employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN asset_allocations al ON a.id = al.asset_id AND al.status = 'active'
                WHERE a.id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    public static function getByEmployee(int $employeeId, bool $activeOnly = true): array {
        if ($activeOnly) {
            $sql = "SELECT al.id AS allocation_id, al.allocated_date, al.expected_return_date, al.allocation_notes, al.status AS allocation_status,
                           a.*, 
                           u.name AS allocated_by_name
                    FROM asset_allocations al
                    JOIN assets a ON al.asset_id = a.id
                    LEFT JOIN users u ON al.allocated_by = u.id
                    WHERE al.employee_id = ? AND al.status = 'active'
                    ORDER BY al.allocated_date DESC";
        } else {
            $sql = "SELECT al.id AS allocation_id, al.allocated_date, al.returned_date, al.return_condition, al.return_notes, al.status AS allocation_status,
                           a.*, 
                           u.name AS allocated_by_name,
                           ru.name AS received_by_name
                    FROM asset_allocations al
                    JOIN assets a ON al.asset_id = a.id
                    LEFT JOIN users u ON al.allocated_by = u.id
                    LEFT JOIN users ru ON al.received_by = ru.id
                    WHERE al.employee_id = ? AND al.status != 'active'
                    ORDER BY al.returned_date DESC";
        }
        return Database::fetchAll($sql, [$employeeId]);
    }

    public static function getPendingExitAssets(int $employeeId): array {
        $sql = "SELECT a.id, a.asset_code, a.name, a.category, a.serial_number, a.condition,
                       al.id AS allocation_id, al.allocated_date, al.expected_return_date
                FROM asset_allocations al
                JOIN assets a ON al.asset_id = a.id
                WHERE al.employee_id = ? AND al.status = 'active'
                ORDER BY a.category ASC, a.name ASC";
        return Database::fetchAll($sql, [$employeeId]);
    }

    public static function getAvailable(?string $category = null): array {
        $sql = "SELECT * FROM assets WHERE status = 'available'";
        $params = [];
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        $sql .= " ORDER BY name ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function getStats(): array {
        $total = (int)(Database::fetchOne("SELECT COUNT(*) AS count FROM assets")['count'] ?? 0);
        $allocated = (int)(Database::fetchOne("SELECT COUNT(*) AS count FROM assets WHERE status = 'allocated'")['count'] ?? 0);
        $available = (int)(Database::fetchOne("SELECT COUNT(*) AS count FROM assets WHERE status = 'available'")['count'] ?? 0);
        $repair = (int)(Database::fetchOne("SELECT COUNT(*) AS count FROM assets WHERE status = 'under_repair'")['count'] ?? 0);
        $valuation = (float)(Database::fetchOne("SELECT SUM(purchase_cost) AS sum FROM assets")['sum'] ?? 0.00);

        // Group by category
        $byCategory = Database::fetchAll("SELECT category, COUNT(*) as count FROM assets GROUP BY category");
        $catMap = [];
        foreach ($byCategory as $c) {
            $catMap[$c['category']] = (int)$c['count'];
        }

        return [
            'total'           => $total,
            'allocated'       => $allocated,
            'available'       => $available,
            'under_repair'    => $repair,
            'total_valuation' => $valuation,
            'by_category'     => $catMap
        ];
    }

    public static function nextAssetCode(): string {
        $last = Database::fetchOne("SELECT asset_code FROM assets WHERE asset_code LIKE 'ADV-AST-%' ORDER BY id DESC LIMIT 1");
        if ($last && preg_match('/ADV-AST-(\d+)/', $last['asset_code'], $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }
        return sprintf('ADV-AST-%03d', $next);
    }

    public static function create(array $data): int {
        $assetCode = !empty($data['asset_code']) ? strtoupper(trim($data['asset_code'])) : self::nextAssetCode();

        $assetId = Database::insert('assets', [
            'asset_code'      => $assetCode,
            'name'            => trim($data['name']),
            'category'        => $data['category'] ?? 'laptop',
            'brand'           => !empty($data['brand']) ? trim($data['brand']) : null,
            'model'           => !empty($data['model']) ? trim($data['model']) : null,
            'serial_number'   => !empty($data['serial_number']) ? trim($data['serial_number']) : null,
            'purchase_date'   => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
            'purchase_cost'   => !empty($data['purchase_cost']) ? (float)$data['purchase_cost'] : 0.00,
            'warranty_expiry' => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
            'status'          => 'available',
            'condition'       => $data['condition'] ?? 'good',
            'notes'           => !empty($data['notes']) ? trim($data['notes']) : null
        ]);

        Database::logActivity(Auth::id(), 'CREATE', 'ASSETS', "Registered new asset {$assetCode} - {$data['name']}");
        return $assetId;
    }

    public static function update(int $id, array $data): bool {
        $updateData = [
            'name'            => trim($data['name']),
            'category'        => $data['category'] ?? 'laptop',
            'brand'           => !empty($data['brand']) ? trim($data['brand']) : null,
            'model'           => !empty($data['model']) ? trim($data['model']) : null,
            'serial_number'   => !empty($data['serial_number']) ? trim($data['serial_number']) : null,
            'purchase_date'   => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
            'purchase_cost'   => !empty($data['purchase_cost']) ? (float)$data['purchase_cost'] : 0.00,
            'warranty_expiry' => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
            'condition'       => $data['condition'] ?? 'good',
            'notes'           => !empty($data['notes']) ? trim($data['notes']) : null
        ];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        Database::update('assets', $updateData, "id = ?", [$id]);
        Database::logActivity(Auth::id(), 'UPDATE', 'ASSETS', "Updated asset #{$id} specifications");
        return true;
    }

    public static function delete(int $id): bool {
        $asset = self::findById($id);
        if (!$asset || $asset['status'] === 'allocated') {
            return false;
        }

        // Delete past allocations or keep them?
        Database::delete('asset_allocations', "asset_id = ?", [$id]);
        Database::delete('assets', "id = ?", [$id]);
        Database::logActivity(Auth::id(), 'DELETE', 'ASSETS', "Deleted asset {$asset['asset_code']} ({$asset['name']})");
        return true;
    }

    public static function allocate(
        int $assetId, 
        int $employeeId, 
        int $allocatedByUserId, 
        string $allocatedDate, 
        ?string $expectedReturnDate = null, 
        ?string $notes = null
    ): array {
        $asset = self::findById($assetId);
        if (!$asset) {
            return ['success' => false, 'message' => 'Asset not found!'];
        }

        if ($asset['status'] === 'allocated') {
            return ['success' => false, 'message' => "Asset {$asset['asset_code']} is already assigned to {$asset['employee_name']}!"];
        }

        $emp = Database::fetchOne("SELECT id, user_id, first_name, last_name, emp_code FROM employees WHERE id = ?", [$employeeId]);
        if (!$emp) {
            return ['success' => false, 'message' => 'Target employee not found!'];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 1. Create Allocation record
            $allocationId = Database::insert('asset_allocations', [
                'asset_id'             => $assetId,
                'employee_id'          => $employeeId,
                'allocated_by'         => $allocatedByUserId,
                'allocated_date'       => $allocatedDate,
                'expected_return_date' => $expectedReturnDate ?: null,
                'status'               => 'active',
                'allocation_notes'     => $notes ? trim($notes) : null
            ]);

            // 2. Update Asset status and current holder
            Database::update('assets', [
                'status'              => 'allocated',
                'current_employee_id' => $employeeId
            ], "id = ?", [$assetId]);

            // 3. Send Notification to Employee
            if (!empty($emp['user_id'])) {
                Notification::send(
                    (int)$emp['user_id'],
                    'asset',
                    'Company Asset Assigned',
                    "You have been assigned: {$asset['name']} ({$asset['asset_code']}). Serial: " . ($asset['serial_number'] ?: 'N/A'),
                    'assets/my-assets',
                    'fa-laptop',
                    '#2563eb'
                );
            }

            $pdo->commit();
            Database::logActivity(
                $allocatedByUserId,
                'ALLOCATE',
                'ASSETS',
                "Allocated {$asset['asset_code']} ({$asset['name']}) to {$emp['first_name']} {$emp['last_name']} ({$emp['emp_code']})"
            );

            return ['success' => true, 'message' => "Asset {$asset['asset_code']} successfully assigned to {$emp['first_name']} {$emp['last_name']}!"];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Allocation failed: ' . $e->getMessage()];
        }
    }

    public static function returnAsset(
        int $allocationId, 
        int $receivedByUserId, 
        string $returnDate, 
        string $condition = 'good', 
        ?string $notes = null,
        string $nextStatus = 'available'
    ): array {
        $allocation = Database::fetchOne("SELECT al.*, a.name AS asset_name, a.asset_code, e.user_id, e.first_name, e.last_name, e.emp_code
                                          FROM asset_allocations al
                                          JOIN assets a ON al.asset_id = a.id
                                          JOIN employees e ON al.employee_id = e.id
                                          WHERE al.id = ?", [$allocationId]);
        if (!$allocation) {
            return ['success' => false, 'message' => 'Allocation record not found!'];
        }

        if ($allocation['status'] !== 'active') {
            return ['success' => false, 'message' => 'This asset has already been marked as returned!'];
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 1. Close allocation record
            Database::update('asset_allocations', [
                'status'           => 'returned',
                'returned_date'    => $returnDate,
                'received_by'      => $receivedByUserId,
                'return_condition' => $condition,
                'return_notes'     => $notes ? trim($notes) : null
            ], "id = ?", [$allocationId]);

            // 2. Set asset back to available or repair
            $assetStatus = in_array($nextStatus, ['available', 'under_repair', 'retired'], true) ? $nextStatus : 'available';
            if ($condition === 'damaged' && $assetStatus === 'available') {
                $assetStatus = 'under_repair';
            }

            Database::update('assets', [
                'status'              => $assetStatus,
                'current_employee_id' => null,
                'condition'           => $condition
            ], "id = ?", [$allocation['asset_id']]);

            // 3. Notify Employee
            if (!empty($allocation['user_id'])) {
                Notification::send(
                    (int)$allocation['user_id'],
                    'asset',
                    'Asset Return Accepted',
                    "Return for {$allocation['asset_name']} ({$allocation['asset_code']}) has been processed and cleared by HR/Admin.",
                    'assets/my-assets',
                    'fa-circle-check',
                    '#16a34a'
                );
            }

            $pdo->commit();
            Database::logActivity(
                $receivedByUserId,
                'RETURN',
                'ASSETS',
                "Returned {$allocation['asset_code']} from {$allocation['first_name']} {$allocation['last_name']} (Condition: {$condition})"
            );

            return ['success' => true, 'message' => "Asset {$allocation['asset_code']} return recorded successfully!"];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Return processing failed: ' . $e->getMessage()];
        }
    }

    public static function getAllocationsByAsset(int $assetId): array {
        $sql = "SELECT al.*, 
                       e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name,
                       u.name AS allocated_by_name,
                       ru.name AS received_by_name
                FROM asset_allocations al
                JOIN employees e ON al.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN users u ON al.allocated_by = u.id
                LEFT JOIN users ru ON al.received_by = ru.id
                WHERE al.asset_id = ?
                ORDER BY al.allocated_date DESC, al.id DESC";
        return Database::fetchAll($sql, [$assetId]);
    }
}
