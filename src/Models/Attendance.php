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

    public static function getTodayCompanyStats(array $employeeIds = []): array {
        $today = date('Y-m-d');
        $params = [];
        $attParams = [$today];

        $empSql = "SELECT count(*) as count FROM employees WHERE status = 'active'";
        $attSql = "SELECT status, count(*) as count FROM attendance WHERE date = ?";

        if (!empty($employeeIds)) {
            $empList = array_values(array_filter(array_map('intval', $employeeIds)));
            if (!empty($empList)) {
                $placeholders = implode(',', array_fill(0, count($empList), '?'));
                $empSql .= " AND id IN ({$placeholders})";
                $attSql .= " AND employee_id IN ({$placeholders})";
                $params = $empList;
                $attParams = array_merge($attParams, $empList);
            } else {
                $empSql .= " AND 1=0";
                $attSql .= " AND 1=0";
            }
        }
        $attSql .= " GROUP BY status";

        $totalEmployees = (int)(Database::fetchOne($empSql, $params)['count'] ?? 0);
        $stats = Database::fetchAll($attSql, $attParams);
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

        if (isset($filters['employee_ids'])) {
            $empIds = array_values(array_filter(array_map('intval', (array)$filters['employee_ids'])));
            if (!empty($empIds)) {
                $placeholders = implode(',', array_fill(0, count($empIds), '?'));
                $sql .= " AND a.employee_id IN ({$placeholders})";
                $params = array_merge($params, $empIds);
            } else {
                $sql .= " AND 1=0";
            }
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

    public static function getRegularizationRequests(?int $managerId = null, ?int $employeeId = null, array $employeeIds = []): array {
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
        } elseif (!empty($employeeIds)) {
            $subIds = array_values(array_filter(array_map('intval', $employeeIds)));
            if (!empty($subIds)) {
                $placeholders = implode(',', array_fill(0, count($subIds), '?'));
                if ($managerId !== null) {
                    $sql .= " AND (ar.employee_id IN ({$placeholders}) OR ar.manager_id = ? OR e.manager_id = ?)";
                    $params = array_merge($params, $subIds, [$managerId, $managerId]);
                } else {
                    $sql .= " AND ar.employee_id IN ({$placeholders})";
                    $params = array_merge($params, $subIds);
                }
            } else {
                if ($managerId !== null) {
                    $sql .= " AND (ar.manager_id = ? OR e.manager_id = ?)";
                    $params[] = $managerId;
                    $params[] = $managerId;
                } else {
                    $sql .= " AND 1=0";
                }
            }
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

    /**
     * Get Complete Monthly Attendance Matrix for All Employees
     */
    public static function getCompanyMonthlyMatrix(int $month, int $year, ?int $departmentId = null, array $employeeIds = []): array {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $totalDays = (int)date('t', strtotime($startDate));
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, $totalDays);
        $today = date('Y-m-d');

        // 1. Fetch Employees
        $empSql = "SELECT e.id, e.emp_code, e.first_name, e.last_name, e.date_of_joining, e.department_id,
                          d.name AS department_name, des.title AS designation_title, u.avatar
                   FROM employees e
                   LEFT JOIN users u ON e.user_id = u.id
                   LEFT JOIN departments d ON e.department_id = d.id
                   LEFT JOIN designations des ON e.designation_id = des.id
                   WHERE e.status = 'active'";
        $params = [];
        if ($departmentId) {
            $empSql .= " AND e.department_id = ?";
            $params[] = $departmentId;
        }
        if (!empty($employeeIds)) {
            $empList = array_values(array_filter(array_map('intval', $employeeIds)));
            if (!empty($empList)) {
                $placeholders = implode(',', array_fill(0, count($empList), '?'));
                $empSql .= " AND e.id IN ({$placeholders})";
                $params = array_merge($params, $empList);
            } else {
                $empSql .= " AND 1=0";
            }
        }
        $empSql .= " ORDER BY d.name ASC, e.first_name ASC";
        $employees = Database::fetchAll($empSql, $params);

        // 2. Fetch Attendance records
        $attSql = "SELECT * FROM attendance WHERE date BETWEEN ? AND ?";
        $allAtt = Database::fetchAll($attSql, [$startDate, $endDate]);
        $attMap = [];
        foreach ($allAtt as $a) {
            $attMap[$a['employee_id']][$a['date']] = $a;
        }

        // 3. Fetch Approved Leaves
        $leavesSql = "SELECT lr.*, lt.name AS leave_name, lt.code AS leave_code
                      FROM leave_requests lr
                      JOIN leave_types lt ON lr.leave_type_id = lt.id
                      WHERE lr.status = 'approved' AND NOT (lr.to_date < ? OR lr.from_date > ?)";
        $allLeaves = Database::fetchAll($leavesSql, [$startDate, $endDate]);
        $leaveMap = [];
        foreach ($allLeaves as $l) {
            $cur = max(strtotime($startDate), strtotime($l['from_date']));
            $end = min(strtotime($endDate), strtotime($l['to_date']));
            while ($cur <= $end) {
                $dStr = date('Y-m-d', $cur);
                $leaveMap[$l['employee_id']][$dStr] = $l;
                $cur = strtotime('+1 day', $cur);
            }
        }

        // 4. Fetch Gazetted Holidays
        $holidays = Database::fetchAll("SELECT * FROM holidays WHERE holiday_date BETWEEN ? AND ?", [$startDate, $endDate]);
        $holidayMap = [];
        foreach ($holidays as $h) {
            $holidayMap[$h['holiday_date']] = $h;
        }

        // 5. Build Days Metadata
        $daysMeta = [];
        $workingDaysCount = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = (int)date('w', strtotime($dateStr)); // 0 = Sun, 6 = Sat
            $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
            $isHoliday = isset($holidayMap[$dateStr]);
            $isFuture = ($dateStr > $today);

            if (!$isWeekend && !$isHoliday) {
                $workingDaysCount++;
            }

            $daysMeta[$d] = [
                'day' => $d,
                'date' => $dateStr,
                'day_char' => substr(date('D', strtotime($dateStr)), 0, 1),
                'day_name' => date('D', strtotime($dateStr)),
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'holiday_title' => $isHoliday ? $holidayMap[$dateStr]['title'] : null,
                'is_today' => ($dateStr === $today),
                'is_future' => $isFuture
            ];
        }

        // 6. Build Employee Matrix
        $matrixRows = [];
        $companyTotalPresent = 0;
        $companyTotalAbsent = 0;
        $companyTotalLeave = 0;
        $companyTotalHours = 0;

        foreach ($employees as $emp) {
            $empDays = [];
            $presentDays = 0.0;
            $halfDays = 0;
            $leaveDays = 0;
            $absentDays = 0;
            $weekendDays = 0;
            $holidayDays = 0;
            $totalHoursWorked = 0.0;

            for ($d = 1; $d <= $totalDays; $d++) {
                $dateStr = $daysMeta[$d]['date'];
                $isWeekend = $daysMeta[$d]['is_weekend'];
                $isHoliday = $daysMeta[$d]['is_holiday'];
                $isFuture = $daysMeta[$d]['is_future'];

                $code = '-';
                $badgeClass = 'empty';
                $tooltip = '';
                $punchData = null;

                // If joined after this date
                if (!empty($emp['date_of_joining']) && $dateStr < $emp['date_of_joining']) {
                    $code = 'NJ';
                    $badgeClass = 'badge-nj';
                    $tooltip = 'Not yet joined';
                } elseif (isset($attMap[$emp['id']][$dateStr])) {
                    $att = $attMap[$emp['id']][$dateStr];
                    $punchData = $att;
                    $totalHoursWorked += (float)$att['total_hours'];

                    if ($att['status'] === 'present' || $att['status'] === 'late') {
                        $code = 'P';
                        $badgeClass = ($att['status'] === 'late') ? 'badge-late' : 'badge-present';
                        $presentDays += 1.0;
                        $tooltip = ($att['status'] === 'late' ? 'Late Punch: ' : 'Present: ') . 
                                   format_time($att['punch_in']) . ' - ' . 
                                   ($att['punch_out'] ? format_time($att['punch_out']) : 'In Progress') . 
                                   " ({$att['total_hours']}h)";
                    } elseif ($att['status'] === 'half_day') {
                        $code = 'HD';
                        $badgeClass = 'badge-halfday';
                        $halfDays++;
                        $presentDays += 0.5;
                        $tooltip = 'Half Day: ' . format_time($att['punch_in']) . ' - ' . 
                                   ($att['punch_out'] ? format_time($att['punch_out']) : '') . 
                                   " ({$att['total_hours']}h)";
                    } elseif ($att['status'] === 'leave') {
                        $code = 'L';
                        $badgeClass = 'badge-leave';
                        $leaveDays++;
                        $tooltip = 'Approved Leave';
                    } elseif ($att['status'] === 'absent') {
                        $code = 'A';
                        $badgeClass = 'badge-absent';
                        $absentDays++;
                        $tooltip = 'Marked Absent';
                    }
                } elseif (isset($leaveMap[$emp['id']][$dateStr])) {
                    $leave = $leaveMap[$emp['id']][$dateStr];
                    $code = 'L';
                    $badgeClass = 'badge-leave';
                    $leaveDays++;
                    $tooltip = 'Leave: ' . ($leave['leave_name'] ?? 'Approved');
                } elseif ($isHoliday) {
                    $code = 'H';
                    $badgeClass = 'badge-holiday';
                    $holidayDays++;
                    $tooltip = 'Holiday: ' . $daysMeta[$d]['holiday_title'];
                } elseif ($isWeekend) {
                    $code = 'W';
                    $badgeClass = 'badge-weekend';
                    $weekendDays++;
                    $tooltip = 'Weekend (' . $daysMeta[$d]['day_name'] . ')';
                } elseif (!$isFuture) {
                    $code = 'A';
                    $badgeClass = 'badge-absent';
                    $absentDays++;
                    $tooltip = 'Absent (No punch recorded)';
                } else {
                    $code = '-';
                    $badgeClass = 'badge-future';
                    $tooltip = 'Upcoming day';
                }

                $empDays[$d] = [
                    'code' => $code,
                    'badge' => $badgeClass,
                    'tooltip' => $tooltip,
                    'punch' => $punchData
                ];
            }

            $payableDays = min($totalDays, round($presentDays + $leaveDays + $holidayDays + $weekendDays, 1));
            $workingDaysCompleted = max(1, $workingDaysCount);
            $effectivePresence = $presentDays + $leaveDays;
            $attendanceRate = round(($effectivePresence / $workingDaysCompleted) * 100, 1);

            $companyTotalPresent += $presentDays;
            $companyTotalAbsent += $absentDays;
            $companyTotalLeave += $leaveDays;
            $companyTotalHours += $totalHoursWorked;

            $matrixRows[] = [
                'employee' => $emp,
                'days' => $empDays,
                'stats' => [
                    'present' => $presentDays,
                    'half_day' => $halfDays,
                    'leave' => $leaveDays,
                    'absent' => $absentDays,
                    'weekend' => $weekendDays,
                    'holiday' => $holidayDays,
                    'payable_days' => $payableDays,
                    'total_hours' => round($totalHoursWorked, 1),
                    'attendance_rate' => min(100, $attendanceRate)
                ]
            ];
        }

        $empCount = max(1, count($employees));
        $avgAttendanceRate = round(($companyTotalPresent / ($empCount * max(1, $workingDaysCount))) * 100, 1);

        return [
            'month' => $month,
            'year' => $year,
            'month_name' => date('F', strtotime($startDate)),
            'total_days' => $totalDays,
            'working_days' => $workingDaysCount,
            'days_meta' => $daysMeta,
            'matrix' => $matrixRows,
            'summary' => [
                'total_employees' => count($employees),
                'working_days' => $workingDaysCount,
                'total_present_days' => $companyTotalPresent,
                'total_absent_days' => $companyTotalAbsent,
                'total_leave_days' => $companyTotalLeave,
                'total_hours_logged' => round($companyTotalHours, 1),
                'company_avg_attendance' => min(100, $avgAttendanceRate)
            ]
        ];
    }
}

