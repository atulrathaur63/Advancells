<?php
/**
 * Attendance & Regularization Model
 */

require_once __DIR__ . '/../Database.php';

class Attendance {
    public static function getToday(int $employeeId): ?array {
        $today = date('Y-m-d');
        return Database::fetchOne("SELECT * FROM attendance WHERE employee_id = ? AND date = ?", [$employeeId, $today]);
    }

    public static function punchIn(int $employeeId, ?string $notes = null): array {
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $existing = self::getToday($employeeId);
        if ($existing && !empty($existing['punch_in'])) {
            return ['success' => false, 'message' => 'Already punched in today at ' . date('h:i A', strtotime($existing['punch_in']))];
        }

        // Determine if late (Grace time till 09:15:00)
        $status = (strtotime($nowTime) > strtotime('09:15:00')) ? 'late' : 'present';

        if ($existing) {
            Database::update('attendance', [
                'punch_in' => $nowTime,
                'punch_in_ip' => $ip,
                'status' => $status,
                'notes' => $notes ?: $existing['notes']
            ], "id = ?", [$existing['id']]);
        } else {
            Database::insert('attendance', [
                'employee_id' => $employeeId,
                'date' => $today,
                'punch_in' => $nowTime,
                'punch_in_ip' => $ip,
                'status' => $status,
                'notes' => $notes
            ]);
        }

        Database::logActivity(Auth::id(), 'PUNCH_IN', 'ATTENDANCE', "Punched in at {$nowTime} ({$status})");
        return ['success' => true, 'message' => 'Punched in successfully at ' . date('h:i A', strtotime($nowTime)), 'time' => $nowTime, 'status' => $status];
    }

    public static function punchOut(int $employeeId, ?string $notes = null): array {
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $existing = self::getToday($employeeId);
        if (!$existing || empty($existing['punch_in'])) {
            return ['success' => false, 'message' => 'You must punch in first before punching out!'];
        }
        if (!empty($existing['punch_out'])) {
            return ['success' => false, 'message' => 'Already punched out today at ' . date('h:i A', strtotime($existing['punch_out']))];
        }

        // Calculate hours worked
        $punchInTs = strtotime($today . ' ' . $existing['punch_in']);
        $punchOutTs = strtotime($today . ' ' . $nowTime);
        $diffSeconds = max(0, $punchOutTs - $punchInTs);
        $totalHours = round($diffSeconds / 3600, 2);

        $status = $existing['status'];
        if ($totalHours < 4.5 && $status !== 'leave') {
            $status = 'half_day';
        }

        Database::update('attendance', [
            'punch_out' => $nowTime,
            'punch_out_ip' => $ip,
            'total_hours' => $totalHours,
            'status' => $status,
            'notes' => $notes ?: $existing['notes']
        ], "id = ?", [$existing['id']]);

        Database::logActivity(Auth::id(), 'PUNCH_OUT', 'ATTENDANCE', "Punched out at {$nowTime}, Total: {$totalHours}h");
        return ['success' => true, 'message' => 'Punched out successfully at ' . date('h:i A', strtotime($nowTime)) . " ({$totalHours} hours)", 'time' => $nowTime, 'hours' => $totalHours];
    }

    public static function getMonthlyAttendance(int $employeeId, int $month, int $year): array {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC";
        $records = Database::fetchAll($sql, [$employeeId, $startDate, $endDate]);

        // Key by date
        $byDate = [];
        foreach ($records as $r) {
            $byDate[$r['date']] = $r;
        }

        return $byDate;
    }

    public static function getMonthlySummary(int $employeeId, int $month, int $year): array {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $records = Database::fetchAll("SELECT * FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?", [$employeeId, $startDate, $endDate]);

        $summary = [
            'total_days' => (int)date('t', strtotime($startDate)),
            'present' => 0,
            'late' => 0,
            'half_day' => 0,
            'leave' => 0,
            'absent' => 0,
            'total_hours' => 0.0
        ];

        foreach ($records as $r) {
            if ($r['status'] === 'present') $summary['present']++;
            elseif ($r['status'] === 'late') {
                $summary['late']++;
                $summary['present']++; // late counts towards presence
            } elseif ($r['status'] === 'half_day') $summary['half_day']++;
            elseif ($r['status'] === 'leave') $summary['leave']++;
            elseif ($r['status'] === 'absent') $summary['absent']++;

            $summary['total_hours'] += (float)$r['total_hours'];
        }

        return $summary;
    }

    public static function getTodayCompanyStats(): array {
        $today = date('Y-m-d');
        $totalEmployees = (int)(Database::fetchOne("SELECT count(*) as count FROM employees WHERE status = 'active'")['count'] ?? 0);
        
        $stats = Database::fetchAll("SELECT status, count(*) as count FROM attendance WHERE date = ? GROUP BY status", [$today]);
        $map = [
            'present' => 0,
            'late' => 0,
            'half_day' => 0,
            'leave' => 0,
            'absent' => 0
        ];
        foreach ($stats as $s) {
            $map[$s['status']] = (int)$s['count'];
        }

        $loggedCount = array_sum($map);
        $notLogged = max(0, $totalEmployees - $loggedCount);

        return [
            'total' => $totalEmployees,
            'present' => $map['present'],
            'late' => $map['late'],
            'half_day' => $map['half_day'],
            'leave' => $map['leave'],
            'absent' => $map['absent'],
            'not_logged' => $notLogged,
            'attendance_rate' => $totalEmployees > 0 ? round((($map['present'] + $map['late']) / $totalEmployees) * 100, 1) : 0
        ];
    }

    public static function getAttendanceLogs(array $filters = []): array {
        $sql = "SELECT a.*, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name
                FROM attendance a
                JOIN employees e ON a.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['date'])) {
            $sql .= " AND a.date = ?";
            $params[] = $filters['date'];
        } elseif (!empty($filters['month']) && !empty($filters['year'])) {
            $sql .= " AND MONTH(a.date) = ? AND YEAR(a.date) = ?";
            $params[] = $filters['month'];
            $params[] = $filters['year'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY a.date DESC, e.emp_code ASC";
        return Database::fetchAll($sql, $params);
    }

    // Regularization methods
    public static function requestRegularization(int $employeeId, string $date, string $inTime, string $outTime, string $reason, ?int $managerId = null): int {
        return Database::insert('attendance_regularizations', [
            'employee_id' => $employeeId,
            'date' => $date,
            'requested_punch_in' => $inTime,
            'requested_punch_out' => $outTime,
            'reason' => trim($reason),
            'manager_id' => $managerId,
            'status' => 'pending'
        ]);
    }

    public static function getRegularizationRequests(?int $managerId = null, ?int $employeeId = null): array {
        $sql = "SELECT ar.*, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name
                FROM attendance_regularizations ar
                JOIN employees e ON ar.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE 1=1";
        $params = [];

        if ($employeeId !== null) {
            $sql .= " AND ar.employee_id = ?";
            $params[] = $employeeId;
        } elseif ($managerId !== null) {
            $sql .= " AND (ar.manager_id = ? OR e.manager_id = ?)";
            $params[] = $managerId;
            $params[] = $managerId;
        }

        $sql .= " ORDER BY ar.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function approveRegularization(int $reqId, ?string $adminRemarks = null): bool {
        $req = Database::fetchOne("SELECT * FROM attendance_regularizations WHERE id = ?", [$reqId]);
        if (!$req || $req['status'] !== 'pending') return false;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Update request status
            Database::update('attendance_regularizations', [
                'status' => 'approved',
                'admin_remarks' => $adminRemarks
            ], "id = ?", [$reqId]);

            // Calculate hours
            $inTs = strtotime($req['date'] . ' ' . $req['requested_punch_in']);
            $outTs = strtotime($req['date'] . ' ' . $req['requested_punch_out']);
            $diffHours = round(max(0, $outTs - $inTs) / 3600, 2);
            $status = $diffHours >= 8.0 ? 'present' : ($diffHours >= 4.5 ? 'half_day' : 'absent');

            // Update or insert daily attendance
            $existing = Database::fetchOne("SELECT id FROM attendance WHERE employee_id = ? AND date = ?", [$req['employee_id'], $req['date']]);
            if ($existing) {
                Database::update('attendance', [
                    'punch_in' => $req['requested_punch_in'],
                    'punch_out' => $req['requested_punch_out'],
                    'total_hours' => $diffHours,
                    'status' => $status,
                    'is_regularized' => 1,
                    'notes' => 'Regularized: ' . ($adminRemarks ?: 'Approved')
                ], "id = ?", [$existing['id']]);
            } else {
                Database::insert('attendance', [
                    'employee_id' => $req['employee_id'],
                    'date' => $req['date'],
                    'punch_in' => $req['requested_punch_in'],
                    'punch_out' => $req['requested_punch_out'],
                    'total_hours' => $diffHours,
                    'status' => $status,
                    'is_regularized' => 1,
                    'notes' => 'Regularized: ' . ($adminRemarks ?: 'Approved')
                ]);
            }

            $pdo->commit();
            Database::logActivity(Auth::id(), 'APPROVE', 'REGULARIZATION', "Approved regularization #{$reqId}");
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function rejectRegularization(int $reqId, ?string $adminRemarks = null): bool {
        Database::logActivity(Auth::id(), 'REJECT', 'REGULARIZATION', "Rejected regularization #{$reqId}");
        return Database::update('attendance_regularizations', [
            'status' => 'rejected',
            'admin_remarks' => $adminRemarks
        ], "id = ?", [$reqId]) > 0;
    }
}
