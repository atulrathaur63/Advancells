<?php
/**
 * Payroll & Compensation Model
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Helpers.php';

class Payroll {
    public static function getSalaryStructure(int $employeeId): ?array {
        return Database::fetchOne("SELECT * FROM salary_structures WHERE employee_id = ?", [$employeeId]);
    }

    public static function saveSalaryStructure(int $employeeId, array $data): bool {
        $basic = (float)($data['basic_salary'] ?? 0);
        $hra = (float)($data['hra'] ?? ($basic * 0.40));
        $special = (float)($data['special_allowance'] ?? 0);
        $conveyance = (float)($data['conveyance'] ?? 0);
        $medical = (float)($data['medical_allowance'] ?? 0);
        $other = (float)($data['other_allowances'] ?? 0);

        // Deductions
        $pf = (float)($data['pf_deduction'] ?? ($basic * 0.12));
        $esi = (float)($data['esi_deduction'] ?? 0);
        $pt = (float)($data['professional_tax'] ?? 200.00);
        $tds = (float)($data['tds'] ?? 0);

        $gross = $basic + $hra + $special + $conveyance + $medical + $other;
        $net = max(0, $gross - ($pf + $esi + $pt + $tds));

        $payload = [
            'employee_id' => $employeeId,
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
            'effective_date' => $data['effective_date'] ?? date('Y-m-d')
        ];

        $existing = self::getSalaryStructure($employeeId);
        if ($existing) {
            unset($payload['employee_id']);
            Database::update('salary_structures', $payload, "id = ?", [$existing['id']]);
        } else {
            Database::insert('salary_structures', $payload);
        }

        Database::logActivity(Auth::id(), 'UPDATE', 'PAYROLL', "Updated salary structure for employee #{$employeeId}");
        return true;
    }

    public static function generatePayroll(int $employeeId, int $month, int $year): array {
        $structure = self::getSalaryStructure($employeeId);
        if (!$structure) {
            return ['success' => false, 'message' => 'Employee has no salary structure configured!'];
        }

        // Employee details for joining/exit dates
        $emp = Database::fetchOne("SELECT id, date_of_joining, date_of_exit FROM employees WHERE id = ?", [$employeeId]);

        // Days in month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
        $today = date('Y-m-d');

        // Fetch attendance punches for the month
        $attLogs = Database::fetchAll("SELECT * FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?", [$employeeId, $startDate, $endDate]);
        $attMap = [];
        foreach ($attLogs as $a) {
            $attMap[$a['date']] = $a;
        }

        // Fetch approved leaves with paid status
        $leaves = Database::fetchAll("SELECT lr.*, lt.is_paid 
                                      FROM leave_requests lr 
                                      JOIN leave_types lt ON lr.leave_type_id = lt.id 
                                      WHERE lr.employee_id = ? AND lr.status = 'approved' 
                                      AND NOT (lr.to_date < ? OR lr.from_date > ?)", [$employeeId, $startDate, $endDate]);
        $leaveMap = [];
        foreach ($leaves as $l) {
            $cur = max(strtotime($startDate), strtotime($l['from_date']));
            $end = min(strtotime($endDate), strtotime($l['to_date']));
            while ($cur <= $end) {
                $leaveMap[date('Y-m-d', $cur)] = $l;
                $cur = strtotime('+1 day', $cur);
            }
        }

        // Fetch gazetted holidays
        $holidays = Database::fetchAll("SELECT holiday_date, title FROM holidays WHERE holiday_date BETWEEN ? AND ?", [$startDate, $endDate]);
        $holidayMap = [];
        foreach ($holidays as $h) {
            $holidayMap[$h['holiday_date']] = $h;
        }

        $presentDays = 0.0;
        $lopDays = 0.0;
        $paidLeaveDays = 0.0;
        $holidayDays = 0.0;
        $weekendDays = 0.0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOfWeek = (int)date('w', strtotime($dateStr)); // 0 = Sun, 6 = Sat
            $isWeekend = is_weekend($dateStr);
            $isHoliday = isset($holidayMap[$dateStr]);
            $isFuture = ($dateStr > $today);

            // 1. Check if date falls outside employment tenure (Not joined or already exited)
            if (($emp && !empty($emp['date_of_joining']) && $dateStr < $emp['date_of_joining']) ||
                ($emp && !empty($emp['date_of_exit']) && $dateStr > $emp['date_of_exit'])) {
                $lopDays += 1.0;
                continue;
            }

            // 2. Attendance punched for this date
            if (isset($attMap[$dateStr])) {
                $att = $attMap[$dateStr];
                if ($att['status'] === 'present' || $att['status'] === 'late') {
                    $presentDays += 1.0;
                } elseif ($att['status'] === 'half_day') {
                    $presentDays += 0.5;
                    $lopDays += 0.5;
                } elseif ($att['status'] === 'leave') {
                    if (isset($leaveMap[$dateStr]) && empty($leaveMap[$dateStr]['is_paid'])) {
                        $lopDays += 1.0;
                    } else {
                        $paidLeaveDays += 1.0;
                    }
                } elseif ($att['status'] === 'absent') {
                    $lopDays += 1.0;
                }
                continue;
            }

            // 3. Approved leave from leave_requests without separate attendance punch
            if (isset($leaveMap[$dateStr])) {
                if (empty($leaveMap[$dateStr]['is_paid'])) {
                    $lopDays += 1.0;
                } else {
                    $paidLeaveDays += 1.0;
                }
                continue;
            }

            // 4. Gazetted company holiday (paid)
            if ($isHoliday) {
                $holidayDays += 1.0;
                continue;
            }

            // 5. Weekend (paid)
            if ($isWeekend) {
                $weekendDays += 1.0;
                continue;
            }

            // 6. Working day with no punch and no approved leave
            if (!$isFuture || $endDate <= $today) {
                // Past or closed working day without punch is Absent (Loss of Pay)
                $lopDays += 1.0;
            } else {
                // Future working day within ongoing month cycle
                $presentDays += 1.0;
            }
        }

        // Present & Paid Days = total days in month - LOP days
        $paidDays = round(max(0, $daysInMonth - $lopDays), 1);

        // Calculate LOP deduction if any
        $dailyRate = $structure['gross_salary'] / max(1, $daysInMonth);
        $lopDeduction = round($lopDays * $dailyRate, 2);

        $gross = (float)$structure['gross_salary'];
        $pf = (float)$structure['pf_deduction'];
        $esi = (float)$structure['esi_deduction'];
        $tax = (float)$structure['tds'] + (float)$structure['professional_tax'];
        $totalDeductions = round($pf + $esi + $tax + $lopDeduction, 2);
        $netSalary = max(0, round($gross - $totalDeductions, 2));

        $payslipNumber = sprintf('PAY-ADV-%04d%02d-%03d', $year, $month, $employeeId);

        // Check if already exists
        $existing = Database::fetchOne("SELECT id FROM payrolls WHERE employee_id = ? AND month = ? AND year = ?", [$employeeId, $month, $year]);
        
        $payrollData = [
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'basic_salary' => $structure['basic_salary'],
            'hra' => $structure['hra'],
            'allowances' => $structure['special_allowance'] + $structure['conveyance'] + $structure['medical_allowance'] + $structure['other_allowances'],
            'overtime_pay' => 0.00,
            'bonus' => 0.00,
            'gross_earnings' => $gross,
            'pf_deduction' => $pf,
            'esi_deduction' => $esi,
            'tax_deduction' => $tax,
            'lop_deduction' => $lopDeduction,
            'other_deductions' => 0.00,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'working_days' => $daysInMonth,
            'present_days' => $paidDays,
            'lop_days' => $lopDays,
            'payment_status' => 'generated',
            'payslip_number' => $payslipNumber
        ];

        if ($existing) {
            Database::update('payrolls', $payrollData, "id = ?", [$existing['id']]);
            $payrollId = $existing['id'];
        } else {
            $payrollId = Database::insert('payrolls', $payrollData);
        }

        return ['success' => true, 'payroll_id' => $payrollId, 'payslip_number' => $payslipNumber];
    }

    public static function processBatch(int $month, int $year): array {
        $employees = Database::fetchAll("SELECT id, emp_code FROM employees WHERE status = 'active'");
        $processed = 0;
        $errors = [];

        foreach ($employees as $emp) {
            $res = self::generatePayroll($emp['id'], $month, $year);
            if ($res['success']) {
                $processed++;
            } else {
                $errors[] = "Emp {$emp['emp_code']}: {$res['message']}";
            }
        }

        Database::logActivity(Auth::id(), 'GENERATE', 'PAYROLL', "Generated payroll for {$processed} employees for {$month}/{$year}");
        return ['processed' => $processed, 'errors' => $errors];
    }

    public static function countPayrolls(array $filters = []): int {
        $sql = "SELECT count(*) AS cnt
                FROM payrolls p
                JOIN employees e ON p.employee_id = e.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['employee_id'])) {
            $sql .= " AND p.employee_id = ?";
            $params[] = $filters['employee_id'];
        }

        if (!empty($filters['month'])) {
            $sql .= " AND p.month = ?";
            $params[] = $filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND p.year = ?";
            $params[] = $filters['year'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.payment_status = ?";
            $params[] = $filters['status'];
        }

        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }

    public static function getPayrolls(array $filters = [], ?int $limit = null, ?int $offset = null): array {
        $sql = "SELECT p.*, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name, des.title AS designation_title,
                       e.bank_name, e.account_number, e.pan_number, e.uan_number
                FROM payrolls p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['employee_id'])) {
            $sql .= " AND p.employee_id = ?";
            $params[] = $filters['employee_id'];
        }

        if (!empty($filters['month'])) {
            $sql .= " AND p.month = ?";
            $params[] = $filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND p.year = ?";
            $params[] = $filters['year'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.payment_status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY p.year DESC, p.month DESC, e.emp_code ASC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return Database::fetchAll($sql, $params);
    }

    public static function getPayslipById(int $payrollId): ?array {
        $sql = "SELECT p.*, e.emp_code, e.first_name, e.last_name, e.email, e.phone, e.date_of_joining,
                       d.name AS department_name, des.title AS designation_title,
                       e.bank_name, e.account_number, e.ifsc_code, e.pan_number, e.uan_number,
                       s.special_allowance, s.conveyance, s.medical_allowance, s.other_allowances,
                       s.professional_tax, s.tds
                FROM payrolls p
                JOIN employees e ON p.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN salary_structures s ON e.id = s.employee_id
                WHERE p.id = ?";
        return Database::fetchOne($sql, [$payrollId]);
    }

    public static function markAsPaid(int $payrollId, ?string $paymentDate = null, string $paymentMethod = 'bank_transfer'): bool {
        $paymentDate = $paymentDate ?: date('Y-m-d');
        Database::logActivity(Auth::id(), 'MARK_PAID', 'PAYROLL', "Marked payroll #{$payrollId} as paid");
        return Database::update('payrolls', [
            'payment_status' => 'paid',
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod
        ], "id = ?", [$payrollId]) > 0;
    }
}
