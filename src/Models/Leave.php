<?php
/**
 * Leave & Holiday Management Model
 */

require_once __DIR__ . '/../Database.php';

class Leave {
    public static function getTypes(): array {
        return Database::fetchAll("SELECT * FROM leave_types ORDER BY id ASC");
    }

    public static function getBalances(int $employeeId, ?int $year = null): array {
        $year = $year ?: (int)date('Y');
        $sql = "SELECT lb.*, lt.name AS leave_type_name, lt.code AS leave_type_code, lt.is_paid,
                       (lb.total_allocated - lb.used) AS available
                FROM leave_balances lb
                JOIN leave_types lt ON lb.leave_type_id = lt.id
                WHERE lb.employee_id = ? AND lb.year = ?
                ORDER BY lt.id ASC";
        return Database::fetchAll($sql, [$employeeId, $year]);
    }

    public static function getRequests(?int $managerId = null, ?int $employeeId = null, array $filters = []): array {
        $sql = "SELECT lr.*, lt.name AS leave_type_name, lt.code AS leave_type_code, lt.is_paid,
                       e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name,
                       CONCAT(app.first_name, ' ', app.last_name) AS approver_name
                FROM leave_requests lr
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                JOIN employees e ON lr.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN employees app ON lr.approved_by = app.id
                WHERE 1=1";
        $params = [];

        if ($employeeId !== null) {
            $sql .= " AND lr.employee_id = ?";
            $params[] = $employeeId;
        } elseif ($managerId !== null) {
            $sql .= " AND (lr.manager_id = ? OR e.manager_id = ?)";
            $params[] = $managerId;
            $params[] = $managerId;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND lr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(lr.from_date) = ?";
            $params[] = $filters['year'];
        }

        $sql .= " ORDER BY lr.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function apply(int $employeeId, int $leaveTypeId, string $fromDate, string $toDate, string $reason, bool $isHalfDay = false, ?string $halfDayType = null, ?int $managerId = null): array {
        if (strtotime($toDate) < strtotime($fromDate)) {
            return ['success' => false, 'message' => 'End date cannot be earlier than start date!'];
        }

        // Calculate days
        if ($isHalfDay) {
            $totalDays = 0.5;
            $toDate = $fromDate; // Half day is always single day
        } else {
            $days = (strtotime($toDate) - strtotime($fromDate)) / (60 * 60 * 24) + 1;
            $totalDays = (float)$days;
        }

        // Check for overlapping leaves
        $overlap = Database::fetchOne(
            "SELECT id FROM leave_requests 
             WHERE employee_id = ? AND status IN ('pending', 'approved') 
             AND ((from_date BETWEEN ? AND ?) OR (to_date BETWEEN ? AND ?) OR (? BETWEEN from_date AND to_date))",
            [$employeeId, $fromDate, $toDate, $fromDate, $toDate, $fromDate]
        );
        if ($overlap) {
            return ['success' => false, 'message' => 'You already have an active leave application overlapping with these dates!'];
        }

        // Check leave balance (if not LOP / unpaid)
        $year = (int)date('Y', strtotime($fromDate));
        $leaveType = Database::fetchOne("SELECT * FROM leave_types WHERE id = ?", [$leaveTypeId]);
        if ($leaveType && $leaveType['is_paid']) {
            $balance = Database::fetchOne("SELECT * FROM leave_balances WHERE employee_id = ? AND leave_type_id = ? AND year = ?", [$employeeId, $leaveTypeId, $year]);
            if ($balance) {
                $available = (float)$balance['total_allocated'] - (float)$balance['used'] - (float)$balance['pending'];
                if ($available < $totalDays) {
                    return ['success' => false, 'message' => "Insufficient leave balance! Available: {$available} day(s), Requested: {$totalDays} day(s)."];
                }
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $reqId = Database::insert('leave_requests', [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_days' => $totalDays,
                'is_half_day' => $isHalfDay ? 1 : 0,
                'half_day_type' => $isHalfDay ? $halfDayType : null,
                'reason' => trim($reason),
                'status' => 'pending',
                'manager_id' => $managerId
            ]);

            // Increment pending in leave balance
            Database::query(
                "UPDATE leave_balances SET pending = pending + ? WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
                [$totalDays, $employeeId, $leaveTypeId, $year]
            );

            $pdo->commit();
            Database::logActivity(Auth::id(), 'APPLY', 'LEAVES', "Applied for {$totalDays} days leave from {$fromDate}");
            return ['success' => true, 'message' => 'Leave application submitted successfully for review!'];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Error applying for leave: ' . $e->getMessage()];
        }
    }

    public static function approve(int $requestId, int $approverEmployeeId, ?string $remarks = null): bool {
        $req = Database::fetchOne("SELECT * FROM leave_requests WHERE id = ?", [$requestId]);
        if (!$req || $req['status'] !== 'pending') return false;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $year = (int)date('Y', strtotime($req['from_date']));

            // Update balance: pending - days, used + days
            Database::query(
                "UPDATE leave_balances 
                 SET pending = GREATEST(0, pending - ?), used = used + ? 
                 WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
                [$req['total_days'], $req['total_days'], $req['employee_id'], $req['leave_type_id'], $year]
            );

            // Mark leave request as approved
            Database::update('leave_requests', [
                'status' => 'approved',
                'approved_by' => $approverEmployeeId,
                'approver_remarks' => $remarks
            ], "id = ?", [$requestId]);

            // Sync attendance table for those dates
            $start = new DateTime($req['from_date']);
            $end = new DateTime($req['to_date']);
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $dt) {
                $curDate = $dt->format('Y-m-d');
                $att = Database::fetchOne("SELECT id FROM attendance WHERE employee_id = ? AND date = ?", [$req['employee_id'], $curDate]);
                if ($att) {
                    Database::update('attendance', [
                        'status' => $req['is_half_day'] ? 'half_day' : 'leave',
                        'notes' => 'On Approved Leave'
                    ], "id = ?", [$att['id']]);
                } else {
                    Database::insert('attendance', [
                        'employee_id' => $req['employee_id'],
                        'date' => $curDate,
                        'status' => $req['is_half_day'] ? 'half_day' : 'leave',
                        'notes' => 'On Approved Leave'
                    ]);
                }
            }

            $pdo->commit();
            Database::logActivity(Auth::id(), 'APPROVE', 'LEAVES', "Approved leave application #{$requestId}");
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function reject(int $requestId, int $approverEmployeeId, ?string $remarks = null): bool {
        $req = Database::fetchOne("SELECT * FROM leave_requests WHERE id = ?", [$requestId]);
        if (!$req || $req['status'] !== 'pending') return false;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $year = (int)date('Y', strtotime($req['from_date']));

            // Release pending balance
            Database::query(
                "UPDATE leave_balances 
                 SET pending = GREATEST(0, pending - ?) 
                 WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
                [$req['total_days'], $req['employee_id'], $req['leave_type_id'], $year]
            );

            // Mark as rejected
            Database::update('leave_requests', [
                'status' => 'rejected',
                'approved_by' => $approverEmployeeId,
                'approver_remarks' => $remarks
            ], "id = ?", [$requestId]);

            $pdo->commit();
            Database::logActivity(Auth::id(), 'REJECT', 'LEAVES', "Rejected leave application #{$requestId}");
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function getHolidays(?int $year = null): array {
        $year = $year ?: (int)date('Y');
        return Database::fetchAll("SELECT * FROM holidays WHERE year = ? ORDER BY holiday_date ASC", [$year]);
    }

    public static function addHoliday(array $data): int {
        return Database::insert('holidays', [
            'title' => trim($data['title']),
            'holiday_date' => $data['holiday_date'],
            'type' => $data['type'] ?? 'mandatory',
            'description' => trim($data['description'] ?? ''),
            'year' => (int)date('Y', strtotime($data['holiday_date']))
        ]);
    }
}
