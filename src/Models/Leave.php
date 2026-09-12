<?php
/**
 * Leave & Holiday Management Model
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Employee.php';

class Leave {
    public static function getTypes(): array {
        return Database::fetchAll("SELECT * FROM leave_types ORDER BY id ASC");
    }

    /**
     * Calculate accrued leave days for an employee up to a specific month in a given year.
     * Policy:
     * - Monthly categories (CL, SL, EL) accrue proportionately each month:
     *     CL (12/yr) = 1.00 day/month
     *     SL (10/yr) = 0.83 days/month (e.g. 7.50 in Sep, 10.00 in Dec)
     *     EL (15/yr) = 1.25 days/month (e.g. 11.25 in Sep, 15.00 in Dec)
     * - Event-based categories (e.g. ML) grant the full quota upon entitlement.
     * - LOP / unpaid grants 0.0.
     * - Pro-rata joins: If employee joined during the year, only active months count.
     */
    public static function calculateAccruedDays(array $employee, array $leaveType, int $year, ?int $asOfMonth = null): float {
        $daysPerYear = (float)($leaveType['days_per_year'] ?? 0);
        $code = strtoupper(trim($leaveType['code'] ?? ''));

        if ($daysPerYear <= 0 || $code === 'LOP') {
            return 0.0;
        }

        // Non-monthly fixed categories (e.g. Maternity Leave)
        if ($code === 'ML') {
            return $daysPerYear;
        }

        $currentYear = (int)date('Y');
        $asOfMonth = $asOfMonth ?: ($year === $currentYear ? (int)date('n') : 12);

        // Past years: fully accrued
        if ($year < $currentYear) {
            $asOfMonth = 12;
        } elseif ($year > $currentYear) {
            return 0.0;
        }

        // Determine start month in this year based on date_of_joining
        $startMonth = 1;
        if (!empty($employee['date_of_joining'])) {
            $joinYear = (int)date('Y', strtotime($employee['date_of_joining']));
            $joinMonth = (int)date('n', strtotime($employee['date_of_joining']));

            if ($joinYear > $year) {
                return 0.0; // Not yet joined in this year
            }
            if ($joinYear === $year) {
                $startMonth = $joinMonth;
            }
        }

        if ($startMonth > $asOfMonth) {
            return 0.0;
        }

        $eligibleMonths = ($asOfMonth - $startMonth + 1);
        $eligibleMonths = max(0, min(12, $eligibleMonths));

        // Accrue monthly: (days_per_year / 12) * eligibleMonths
        $accrued = round(($daysPerYear / 12.0) * $eligibleMonths, 2);
        return min($daysPerYear, $accrued);
    }

    /**
     * Synchronize monthly accrued leave balances for employee(s) in a given year.
     * Preserves existing used and pending amounts safely.
     */
    public static function syncMonthlyAccrual(?int $employeeId = null, ?int $year = null): void {
        $year = $year ?: (int)date('Y');
        $leaveTypes = self::getTypes();

        $empSql = "SELECT id, date_of_joining, status FROM employees WHERE 1=1";
        $params = [];
        if ($employeeId !== null) {
            $empSql .= " AND id = ?";
            $params[] = $employeeId;
        } else {
            $empSql .= " AND status = 'active'";
        }
        $employees = Database::fetchAll($empSql, $params);

        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                $accrued = self::calculateAccruedDays($emp, $lt, $year);

                $existing = Database::fetchOne(
                    "SELECT id, carried_forward, total_allocated, used, pending FROM leave_balances WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
                    [$emp['id'], $lt['id'], $year]
                );

                if ($existing) {
                    $carriedForward = (float)($existing['carried_forward'] ?? 0.0);
                    // Safe allocation: ensure total_allocated is at least carried_forward + accrued AND used + pending
                    $usedAndPending = (float)$existing['used'] + (float)$existing['pending'];
                    $targetAllocated = max($carriedForward + $accrued, $usedAndPending);

                    if (abs((float)$existing['total_allocated'] - $targetAllocated) > 0.001) {
                        Database::update('leave_balances', [
                            'total_allocated' => $targetAllocated
                        ], "id = ?", [$existing['id']]);
                    }
                } else {
                    Database::insert('leave_balances', [
                        'employee_id' => $emp['id'],
                        'leave_type_id' => $lt['id'],
                        'year' => $year,
                        'carried_forward' => 0.0,
                        'total_allocated' => $accrued,
                        'used' => 0.0,
                        'pending' => 0.0
                    ]);
                }
            }
        }
    }

    public static function getBalances(int $employeeId, ?int $year = null): array {
        $year = $year ?: (int)date('Y');
        // Auto-sync monthly accrual so balances reflect current month
        self::syncMonthlyAccrual($employeeId, $year);

        $sql = "SELECT lb.*, lt.name AS leave_type_name, lt.code AS leave_type_code, lt.days_per_year, lt.is_paid,
                       (lb.total_allocated - lb.used) AS available
                FROM leave_balances lb
                JOIN leave_types lt ON lb.leave_type_id = lt.id
                WHERE lb.employee_id = ? AND lb.year = ?
                ORDER BY lt.id ASC";
        $balances = Database::fetchAll($sql, [$employeeId, $year]);
        foreach ($balances as &$b) {
            $code = strtoupper($b['leave_type_code']);
            if (in_array($code, ['CL', 'SL', 'EL'], true)) {
                $rate = round((float)$b['days_per_year'] / 12.0, 2);
                $b['accrual_label'] = "+{$rate}/mo";
            } else {
                $b['accrual_label'] = 'Annual';
            }
        }
        unset($b);
        return $balances;
    }

    public static function countRequests(?int $managerId = null, ?int $employeeId = null, array $filters = []): int {
        $sql = "SELECT COUNT(*) AS total
                FROM leave_requests lr
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                JOIN employees e ON lr.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE 1=1";
        $params = [];

        if ($employeeId !== null) {
            $sql .= " AND lr.employee_id = ?";
            $params[] = $employeeId;
        } elseif (isset($filters['employee_ids'])) {
            $subIds = array_values(array_filter(array_map('intval', (array)$filters['employee_ids'])));
            if (!empty($subIds)) {
                $placeholders = implode(',', array_fill(0, count($subIds), '?'));
                if ($managerId !== null) {
                    $sql .= " AND (lr.employee_id IN ({$placeholders}) OR lr.manager_id = ? OR e.manager_id = ?)";
                    $params = array_merge($params, $subIds, [$managerId, $managerId]);
                } else {
                    $sql .= " AND lr.employee_id IN ({$placeholders})";
                    $params = array_merge($params, $subIds);
                }
            } else {
                if ($managerId !== null) {
                    $sql .= " AND (lr.manager_id = ? OR e.manager_id = ?)";
                    $params[] = $managerId;
                    $params[] = $managerId;
                } else {
                    $sql .= " AND 1=0";
                }
            }
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

        $res = Database::fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    public static function getRequests(?int $managerId = null, ?int $employeeId = null, array $filters = [], ?int $limit = null, ?int $offset = null): array {
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
        } elseif (isset($filters['employee_ids'])) {
            $subIds = array_values(array_filter(array_map('intval', (array)$filters['employee_ids'])));
            if (!empty($subIds)) {
                $placeholders = implode(',', array_fill(0, count($subIds), '?'));
                if ($managerId !== null) {
                    $sql .= " AND (lr.employee_id IN ({$placeholders}) OR lr.manager_id = ? OR e.manager_id = ?)";
                    $params = array_merge($params, $subIds, [$managerId, $managerId]);
                } else {
                    $sql .= " AND lr.employee_id IN ({$placeholders})";
                    $params = array_merge($params, $subIds);
                }
            } else {
                if ($managerId !== null) {
                    $sql .= " AND (lr.manager_id = ? OR e.manager_id = ?)";
                    $params[] = $managerId;
                    $params[] = $managerId;
                } else {
                    $sql .= " AND 1=0";
                }
            }
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

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return Database::fetchAll($sql, $params);
    }

    public static function getPreviousWorkingDay(string $date): string {
        $cur = strtotime($date);
        for ($i = 1; $i <= 14; $i++) {
            $prev = strtotime("-{$i} day", $cur);
            $prevStr = date('Y-m-d', $prev);
            if (is_working_day($prevStr)) {
                return $prevStr;
            }
        }
        return date('Y-m-d', strtotime('-1 day', $cur));
    }

    public static function getNextWorkingDay(string $date): string {
        $cur = strtotime($date);
        for ($i = 1; $i <= 14; $i++) {
            $next = strtotime("+{$i} day", $cur);
            $nextStr = date('Y-m-d', $next);
            if (is_working_day($nextStr)) {
                return $nextStr;
            }
        }
        return date('Y-m-d', strtotime('+1 day', $cur));
    }

    public static function isEmployeeOnApprovedLeave(int $employeeId, string $date): bool {
        $exists = Database::fetchOne(
            "SELECT id FROM leave_requests 
             WHERE employee_id = ? AND status = 'approved' AND ? BETWEEN from_date AND to_date",
            [$employeeId, $date]
        );
        return !empty($exists);
    }

    /**
     * Calculate leave days under the Company Sandwich Rule:
     * - Intervening weekends / holidays between working leave days are counted as leave.
     * - Cross-application sandwiching: checks if preceding or following working day is already on approved leave.
     * - Trailing or leading weekends are trimmed if not sandwiched.
     * - Date ranges with 0 working days are rejected.
     */
    public static function calculateSandwichDays(int $employeeId, string $fromDate, string $toDate, bool $isHalfDay = false): array {
        if (strtotime($toDate) < strtotime($fromDate)) {
            return [
                'success' => false,
                'message' => 'End date cannot be earlier than start date!'
            ];
        }

        if ($isHalfDay) {
            $toDate = $fromDate;
            if (!is_working_day($fromDate)) {
                return [
                    'success' => false,
                    'message' => 'Cannot apply for half-day leave on a weekend or gazetted holiday.'
                ];
            }
            return [
                'success' => true,
                'total_days' => 0.5,
                'working_days' => 1,
                'sandwiched_days' => 0,
                'deducted_dates' => [$fromDate],
                'trimmed_dates' => [],
                'details' => '0.5 Day (Half Day Leave)',
                'is_half_day' => true
            ];
        }

        // Generate all calendar dates in requested range
        $start = new DateTime($fromDate);
        $end = new DateTime($toDate);
        $end->modify('+1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);

        $rangeDates = [];
        foreach ($period as $dt) {
            $rangeDates[] = $dt->format('Y-m-d');
        }

        // Identify working days in range
        $workingIndices = [];
        foreach ($rangeDates as $idx => $dateStr) {
            if (is_working_day($dateStr)) {
                $workingIndices[] = $idx;
            }
        }

        // Reject if no working days exist in the requested span
        if (empty($workingIndices)) {
            return [
                'success' => false,
                'message' => 'The selected date range contains only weekends/holidays. No leave application is required.'
            ];
        }

        $firstWorkIdx = $workingIndices[0];
        $lastWorkIdx = end($workingIndices);
        $firstWorkDate = $rangeDates[$firstWorkIdx];
        $lastWorkDate = $rangeDates[$lastWorkIdx];

        $deductedDates = [];
        $trimmedDates = [];
        $sandwichedDates = [];
        $workingDates = [];

        // Check Prefix non-working dates
        $prevWorkDay = self::getPreviousWorkingDay($firstWorkDate);
        $prevIsApprovedLeave = self::isEmployeeOnApprovedLeave($employeeId, $prevWorkDay);

        for ($i = 0; $i < $firstWorkIdx; $i++) {
            $d = $rangeDates[$i];
            if ($prevIsApprovedLeave) {
                $deductedDates[] = $d;
                $sandwichedDates[] = $d;
            } else {
                $trimmedDates[] = $d;
            }
        }

        // Middle dates (first working day through last working day)
        for ($i = $firstWorkIdx; $i <= $lastWorkIdx; $i++) {
            $d = $rangeDates[$i];
            $deductedDates[] = $d;
            if (is_working_day($d)) {
                $workingDates[] = $d;
            } else {
                $sandwichedDates[] = $d;
            }
        }

        // Check Suffix non-working dates
        $nextWorkDay = self::getNextWorkingDay($lastWorkDate);
        $nextIsApprovedLeave = self::isEmployeeOnApprovedLeave($employeeId, $nextWorkDay);

        for ($i = $lastWorkIdx + 1; $i < count($rangeDates); $i++) {
            $d = $rangeDates[$i];
            if ($nextIsApprovedLeave) {
                $deductedDates[] = $d;
                $sandwichedDates[] = $d;
            } else {
                $trimmedDates[] = $d;
            }
        }

        // Cross-application sandwich: if prefix was not selected in picker, but previous working day is leave
        if ($firstWorkIdx === 0 && $prevIsApprovedLeave) {
            $pStart = new DateTime($prevWorkDay);
            $pStart->modify('+1 day');
            $pEnd = new DateTime($firstWorkDate);
            if ($pStart < $pEnd) {
                $pPeriod = new DatePeriod($pStart, new DateInterval('P1D'), $pEnd);
                foreach ($pPeriod as $pDt) {
                    $pDateStr = $pDt->format('Y-m-d');
                    if (!in_array($pDateStr, $deductedDates, true) && !self::isEmployeeOnApprovedLeave($employeeId, $pDateStr)) {
                        $deductedDates[] = $pDateStr;
                        $sandwichedDates[] = $pDateStr;
                    }
                }
            }
        }

        // Cross-application sandwich: if suffix was not selected in picker, but next working day is leave
        if ($lastWorkIdx === count($rangeDates) - 1 && $nextIsApprovedLeave) {
            $nStart = new DateTime($lastWorkDate);
            $nStart->modify('+1 day');
            $nEnd = new DateTime($nextWorkDay);
            if ($nStart < $nEnd) {
                $nPeriod = new DatePeriod($nStart, new DateInterval('P1D'), $nEnd);
                foreach ($nPeriod as $nDt) {
                    $nDateStr = $nDt->format('Y-m-d');
                    if (!in_array($nDateStr, $deductedDates, true) && !self::isEmployeeOnApprovedLeave($employeeId, $nDateStr)) {
                        $deductedDates[] = $nDateStr;
                        $sandwichedDates[] = $nDateStr;
                    }
                }
            }
        }

        sort($deductedDates);
        sort($sandwichedDates);
        sort($trimmedDates);

        $totalDays = (float)count($deductedDates);
        $workingCount = count($workingDates);
        $sandwichCount = count($sandwichedDates);

        if ($sandwichCount > 0) {
            $details = "{$workingCount} working day(s) + {$sandwichCount} sandwiched weekend/holiday day(s) = {$totalDays} total days (Sandwich Rule Applied)";
        } elseif (!empty($trimmedDates)) {
            $trimmedCount = count($trimmedDates);
            $details = "{$workingCount} working day(s) ({$trimmedCount} weekend/holiday day(s) trimmed as not sandwiched)";
        } else {
            $details = "{$workingCount} working day(s)";
        }

        return [
            'success' => true,
            'total_days' => $totalDays,
            'working_days' => $workingCount,
            'sandwiched_days' => $sandwichCount,
            'sandwiched_dates' => $sandwichedDates,
            'deducted_dates' => $deductedDates,
            'trimmed_dates' => $trimmedDates,
            'details' => $details,
            'is_half_day' => false
        ];
    }

    public static function apply(int $employeeId, int $leaveTypeId, string $fromDate, string $toDate, string $reason, bool $isHalfDay = false, ?string $halfDayType = null, ?int $managerId = null): array {
        if (strtotime($toDate) < strtotime($fromDate)) {
            return ['success' => false, 'message' => 'End date cannot be earlier than start date!'];
        }

        // Calculate sandwich-aware leave days
        $calc = self::calculateSandwichDays($employeeId, $fromDate, $toDate, $isHalfDay);
        if (!$calc['success']) {
            return ['success' => false, 'message' => $calc['message']];
        }

        $totalDays = (float)$calc['total_days'];
        if ($isHalfDay) {
            $toDate = $fromDate;
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
                    $sandwichMsg = $calc['sandwiched_days'] > 0 ? " (includes {$calc['sandwiched_days']} sandwiched weekend/holiday day(s) per company policy)" : '';
                    return ['success' => false, 'message' => "Insufficient leave balance! Available: {$available} day(s), Requested: {$totalDays} day(s){$sandwichMsg}."];
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
            Database::logActivity(Auth::id(), 'APPLY', 'LEAVES', "Applied for {$totalDays} days leave from {$fromDate} to {$toDate} ({$calc['details']})");
            return ['success' => true, 'message' => 'Leave application submitted successfully for review! ' . $calc['details']];
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

            // Re-calculate the exact deducted dates per sandwich rule
            $calc = self::calculateSandwichDays((int)$req['employee_id'], $req['from_date'], $req['to_date'], (bool)$req['is_half_day']);
            $deductedDates = $calc['deducted_dates'] ?? [];

            foreach ($deductedDates as $curDate) {
                $isSandwich = !is_working_day($curDate);
                $note = $isSandwich ? 'Sandwiched Week-Off / Leave' : 'On Approved Leave';
                $status = $req['is_half_day'] ? 'half_day' : 'leave';

                $att = Database::fetchOne("SELECT id, punch_in FROM attendance WHERE employee_id = ? AND date = ?", [$req['employee_id'], $curDate]);
                if ($att) {
                    Database::update('attendance', [
                        'status' => $status,
                        'notes' => $note
                    ], "id = ?", [$att['id']]);
                } else {
                    Database::insert('attendance', [
                        'employee_id' => $req['employee_id'],
                        'date' => $curDate,
                        'status' => $status,
                        'notes' => $note
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

    /**
     * Cancel a leave application (pending or approved) with balance refund and attendance cleanup.
     */
    public static function cancel(int $requestId, int $cancellerEmpId, string $reason = ''): array {
        $req = Database::fetchOne(
            "SELECT lr.*, e.user_id, e.first_name, e.manager_id 
             FROM leave_requests lr 
             JOIN employees e ON lr.employee_id = e.id 
             WHERE lr.id = ?",
            [$requestId]
        );

        if (!$req) {
            return ['success' => false, 'message' => 'Leave request not found.'];
        }

        if ($req['status'] === 'cancelled') {
            return ['success' => false, 'message' => 'This leave application is already cancelled.'];
        }

        if ($req['status'] === 'rejected') {
            return ['success' => false, 'message' => 'Cannot cancel an application that has already been rejected.'];
        }

        $isSelf = ((int)$req['employee_id'] === $cancellerEmpId);
        $isHR = Auth::isHR();
        $isManager = ((int)$req['manager_id'] === $cancellerEmpId || Employee::isSubordinateOf((int)$req['employee_id'], $cancellerEmpId));

        if (!$isSelf && !$isHR && !$isManager) {
            return ['success' => false, 'message' => 'Unauthorized! You do not have permission to cancel this leave application.'];
        }

        // Regular employees cannot self-cancel past approved leaves
        if ($req['status'] === 'approved' && !$isHR) {
            if ($req['to_date'] < date('Y-m-d')) {
                return ['success' => false, 'message' => 'Cannot self-cancel past approved leaves. Please contact HR.'];
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $year = (int)date('Y', strtotime($req['from_date']));
            $totalDays = (float)$req['total_days'];

            if ($req['status'] === 'pending') {
                // Refund pending balance
                Database::query(
                    "UPDATE leave_balances 
                     SET pending = GREATEST(0, pending - ?) 
                     WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
                    [$totalDays, $req['employee_id'], $req['leave_type_id'], $year]
                );
            } elseif ($req['status'] === 'approved') {
                // Refund used balance
                Database::query(
                    "UPDATE leave_balances 
                     SET used = GREATEST(0, used - ?) 
                     WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
                    [$totalDays, $req['employee_id'], $req['leave_type_id'], $year]
                );

                // Clean up attendance records for deducted dates
                $calc = self::calculateSandwichDays((int)$req['employee_id'], $req['from_date'], $req['to_date'], (bool)$req['is_half_day']);
                $deductedDates = $calc['deducted_dates'] ?? [];

                foreach ($deductedDates as $curDate) {
                    $att = Database::fetchOne(
                        "SELECT id, punch_in, punch_out, status, notes FROM attendance WHERE employee_id = ? AND date = ?",
                        [$req['employee_id'], $curDate]
                    );

                    if ($att) {
                        // If it was a pure placeholder generated by leave approval (no physical punch)
                        if ($att['punch_in'] === null && $att['punch_out'] === null) {
                            Database::query("DELETE FROM attendance WHERE id = ?", [$att['id']]);
                        } else {
                            // If user had a punch, reset status to working hours
                            $hours = (float)($att['total_hours'] ?? 0);
                            $newStatus = ($hours >= 8.0) ? 'present' : (($hours >= 4.5) ? 'half_day' : 'absent');
                            Database::update('attendance', [
                                'status' => $newStatus,
                                'notes' => 'Leave Cancelled'
                            ], "id = ?", [$att['id']]);
                        }
                    }
                }
            }

            // Update request status to cancelled
            $cancelRemarks = trim(($req['approver_remarks'] ? $req['approver_remarks'] . ' | ' : '') . "Cancelled: {$reason}");
            Database::update('leave_requests', [
                'status' => 'cancelled',
                'approver_remarks' => $cancelRemarks
            ], "id = ?", [$requestId]);

            $pdo->commit();

            Database::logActivity(
                Auth::id(),
                'CANCEL',
                'LEAVES',
                "Cancelled leave request #{$requestId} for employee #{$req['employee_id']} ({$totalDays} days refunded)"
            );

            // Notify parties
            if ($isSelf && !empty($req['manager_id'])) {
                $mgr = Employee::findById((int)$req['manager_id']);
                if ($mgr && !empty($mgr['user_id'])) {
                    Notification::send(
                        (int)$mgr['user_id'],
                        'leave',
                        'Leave Cancelled',
                        "{$req['first_name']} cancelled leave from {$req['from_date']} to {$req['to_date']}.",
                        'leaves/approvals',
                        'fa-calendar-xmark',
                        '#64748b'
                    );
                }
            } elseif (!$isSelf && !empty($req['user_id'])) {
                Notification::send(
                    (int)$req['user_id'],
                    'leave',
                    'Leave Cancelled',
                    "Your leave from {$req['from_date']} to {$req['to_date']} was cancelled by management.",
                    'leaves/my-leaves',
                    'fa-calendar-xmark',
                    '#64748b'
                );
            }

            return ['success' => true, 'message' => "Leave application #{$requestId} cancelled successfully! {$totalDays} day(s) refunded to balance."];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Error cancelling leave application: ' . $e->getMessage()];
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

    public static function deleteHoliday(int $id): bool {
        $holiday = Database::fetchOne("SELECT * FROM holidays WHERE id = ?", [$id]);
        if (!$holiday) {
            return false;
        }
        $deleted = Database::query("DELETE FROM holidays WHERE id = ?", [$id])->rowCount() > 0;
        if ($deleted) {
            Database::logActivity(Auth::id(), 'DELETE', 'HOLIDAYS', "Deleted holiday '{$holiday['title']}' on {$holiday['holiday_date']}");
        }
        return $deleted;
    }

    /**
     * Get all employees with their leave balances for a given year.
     * Used by HR/Super Admin for the balance editor.
     */
    public static function getAllEmployeeBalances(?int $year = null, ?int $filterEmployeeId = null): array {
        $year = $year ?: (int)date('Y');

        $empSql = "SELECT e.id, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                          d.name AS department_name, e.user_id
                   FROM employees e
                   LEFT JOIN departments d ON e.department_id = d.id
                   WHERE e.status = 'active'";
        $params = [];

        if ($filterEmployeeId !== null) {
            $empSql .= " AND e.id = ?";
            $params[] = $filterEmployeeId;
        }
        $empSql .= " ORDER BY e.first_name ASC";
        $employees = Database::fetchAll($empSql, $params);

        $leaveTypes = self::getTypes();

        $result = [];
        foreach ($employees as $emp) {
            // Ensure balances exist for this employee (auto-sync)
            self::syncMonthlyAccrual((int)$emp['id'], $year);

            $balances = Database::fetchAll(
                "SELECT lb.*, lt.name AS leave_type_name, lt.code AS leave_type_code
                 FROM leave_balances lb
                 JOIN leave_types lt ON lb.leave_type_id = lt.id
                 WHERE lb.employee_id = ? AND lb.year = ?
                 ORDER BY lt.id ASC",
                [$emp['id'], $year]
            );

            $result[] = [
                'employee' => $emp,
                'balances' => $balances
            ];
        }

        return ['employees' => $result, 'leave_types' => $leaveTypes, 'year' => $year];
    }

    /**
     * Update a specific leave balance record.
     * Only total_allocated can be edited. Used/Pending are system-managed.
     */
    public static function updateBalanceAllocation(int $balanceId, float $newAllocated, ?string $reason = null): bool {
        $existing = Database::fetchOne(
            "SELECT lb.*, lt.name AS leave_type_name, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS emp_name
             FROM leave_balances lb
             JOIN leave_types lt ON lb.leave_type_id = lt.id
             JOIN employees e ON lb.employee_id = e.id
             WHERE lb.id = ?",
            [$balanceId]
        );

        if (!$existing) {
            return false;
        }

        $usedAndPending = (float)$existing['used'] + (float)$existing['pending'];
        // Ensure new allocation is at least used + pending to prevent negative available
        $safeAllocated = max($newAllocated, $usedAndPending);

        Database::update('leave_balances', [
            'total_allocated' => $safeAllocated
        ], "id = ?", [$balanceId]);

        $oldVal = $existing['total_allocated'];
        Database::logActivity(
            Auth::id(),
            'UPDATE',
            'LEAVE_BALANCE',
            "Manually adjusted {$existing['leave_type_name']} balance for {$existing['emp_name']} ({$existing['emp_code']}): {$oldVal} → {$safeAllocated}" . ($reason ? " | Reason: {$reason}" : '')
        );

        return true;
    }
}
