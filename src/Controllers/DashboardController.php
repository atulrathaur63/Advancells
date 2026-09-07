<?php
/**
 * Dashboard Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Leave.php';
require_once __DIR__ . '/../Models/Payroll.php';
require_once __DIR__ . '/../Models/Announcement.php';

class DashboardController {
    public function index(): void {
        Auth::requireLogin();
        $user = Auth::user();
        $empId = Auth::employeeId();

        $role = Auth::role();
        $announcements = Announcement::getActive($role);
        $holidays = Leave::getHolidays((int)date('Y'));

        if ($role === 'super_admin' || $role === 'hr_admin') {
            $stats = Employee::getStats();
            $attToday = Attendance::getTodayCompanyStats();
            $pendingLeaves = Leave::getRequests(null, null, ['status' => 'pending']);
            $pendingRegs = Attendance::getRegularizationRequests();
            $pendingRegsCount = count(array_filter($pendingRegs, fn($r) => $r['status'] === 'pending'));
            
            // 1. Department Breakdown for Pie/Donut Chart
            $deptData = Database::fetchAll("SELECT d.name, count(e.id) as count 
                                           FROM departments d 
                                           LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active' 
                                           GROUP BY d.id, d.name");
            
            // 2. Attendance Trend (Last 7 Days) for Graph Chart
            $trendDays = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $presCount = Database::fetchOne("SELECT count(*) as count FROM attendance WHERE date = ? AND status IN ('present', 'late')", [$d])['count'] ?? 0;
                $leaveCount = Database::fetchOne("SELECT count(*) as count FROM attendance WHERE date = ? AND status = 'leave'", [$d])['count'] ?? 0;
                $trendDays[] = [
                    'date' => date('d M', strtotime($d)),
                    'present' => (int)$presCount,
                    'leave' => (int)$leaveCount
                ];
            }

            // 3. Leave Utilization by Category for Pie Chart
            $currentYear = (int)date('Y');
            $leaveTypeDist = Database::fetchAll("SELECT lt.name, lt.code, COALESCE(SUM(lb.used), 0) as used 
                                                FROM leave_types lt 
                                                LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id AND lb.year = ? 
                                                GROUP BY lt.id, lt.name, lt.code", [$currentYear]);

            // 4. Employment Type Distribution for Pie Chart
            $empTypeDist = Database::fetchAll("SELECT employment_type, count(*) as count FROM employees WHERE status = 'active' GROUP BY employment_type");

            // 5. Recent Activity Logs
            $recentActivities = Database::fetchAll("SELECT a.*, u.name as user_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 6");

            require_once BASE_PATH . '/views/dashboard/admin.php';
        } elseif ($role === 'manager') {
            $subordinateIds = Employee::getSubordinateIds($empId, false);

            if (!empty($subordinateIds)) {
                $placeholders = implode(',', array_fill(0, count($subordinateIds), '?'));
                $teamMembers = Database::fetchAll("SELECT e.*, des.title as designation_title, d.name as department_name 
                                                   FROM employees e 
                                                   LEFT JOIN designations des ON e.designation_id = des.id 
                                                   LEFT JOIN departments d ON e.department_id = d.id 
                                                   WHERE e.id IN ({$placeholders}) AND e.status = 'active'
                                                   ORDER BY d.name ASC, e.first_name ASC", $subordinateIds);
                $teamCount = count($teamMembers);

                // Team attendance today
                $today = date('Y-m-d');
                $teamAtt = Database::fetchAll("SELECT e.emp_code, CONCAT(e.first_name, ' ', e.last_name) as name, 
                                                      a.punch_in, a.punch_out, a.status, a.total_hours
                                               FROM employees e
                                               LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
                                               WHERE e.id IN ({$placeholders}) AND e.status = 'active'
                                               ORDER BY e.first_name ASC", array_merge([$today], $subordinateIds));
            } else {
                $teamMembers = [];
                $teamCount = 0;
                $teamAtt = [];
            }

            $pendingLeaves = Leave::getRequests($empId, null, ['status' => 'pending', 'employee_ids' => $subordinateIds]);
            $pendingRegs = Attendance::getRegularizationRequests($empId, null, $subordinateIds);
            $pendingRegsCount = count(array_filter($pendingRegs, fn($r) => $r['status'] === 'pending'));

            // Team Attendance Breakdown for Pie Chart
            $teamPresent = 0;
            $teamLate = 0;
            $teamLeave = 0;
            $teamPending = 0;
            foreach ($teamAtt as $tm) {
                if ($tm['status'] === 'present') $teamPresent++;
                elseif ($tm['status'] === 'late') $teamLate++;
                elseif ($tm['status'] === 'leave') $teamLeave++;
                else $teamPending++;
            }

            // Manager personal attendance
            $myTodayAtt = Attendance::getToday($empId);

            require_once BASE_PATH . '/views/dashboard/manager.php';
        } else {
            // Employee Self-Service (ESS)
            $myTodayAtt = Attendance::getToday($empId);
            $month = (int)date('m');
            $year = (int)date('Y');
            $attSummary = Attendance::getMonthlySummary($empId, $month, $year);
            $leaveBalances = Leave::getBalances($empId, $year);
            $myRecentLeaves = Leave::getRequests(null, $empId);
            $myRecentLeaves = array_slice($myRecentLeaves, 0, 5);
            $latestPayroll = Database::fetchOne("SELECT * FROM payrolls WHERE employee_id = ? ORDER BY year DESC, month DESC LIMIT 1", [$empId]);

            // Last 7 days hours worked for Employee Graph Chart
            $last7DaysHours = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $att = Database::fetchOne("SELECT total_hours FROM attendance WHERE employee_id = ? AND date = ?", [$empId, $d]);
                $last7DaysHours[] = [
                    'day' => date('D, d M', strtotime($d)),
                    'hours' => $att ? (float)$att['total_hours'] : 0.0
                ];
            }

            require_once BASE_PATH . '/views/dashboard/employee.php';
        }
    }
}
