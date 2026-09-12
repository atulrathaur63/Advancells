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
require_once __DIR__ . '/../Models/Celebration.php';

class DashboardController {
    public function index(): void {
        Auth::requireLogin();
        $user = Auth::user();
        $empId = Auth::employeeId();

        $role = Auth::role();
        $announcements = Announcement::getActive($role);
        $holidays = Leave::getHolidays((int)date('Y'));
        
        // Next upcoming public holidays (deduplicated)
        $upcomingHolidays = Database::fetchAll("SELECT DISTINCT title, holiday_date, type, description 
                                                FROM holidays 
                                                WHERE holiday_date >= CURDATE() 
                                                ORDER BY holiday_date ASC 
                                                LIMIT 4");

        // Celebrations: Birthdays, Work Anniversaries, New Joinees
        $celebrations = Celebration::getAllCelebrations((int)$user['id']);
        $myCelebration = $empId ? Celebration::getCelebrantStatus($empId) : null;

        if ($role === 'super_admin' || $role === 'hr_admin') {
            $stats = Employee::getStats();
            $attToday = Attendance::getTodayCompanyStats();
            $pendingLeaves = Leave::getRequests(null, null, ['status' => 'pending']);
            $rawRegs = Attendance::getRegularizationRequests();
            $pendingRegs = array_values(array_filter($rawRegs, fn($r) => $r['status'] === 'pending'));
            $pendingRegsCount = count($pendingRegs);
            
            // 1. Department Breakdown for Pie/Donut Chart
            $deptData = Database::fetchAll("SELECT d.name, count(e.id) as count 
                                           FROM departments d 
                                           LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active' 
                                           GROUP BY d.id, d.name");
            
            // 2. Attendance Trend (Last 7 Days) for Graph Chart - Optimized single aggregate query
            $trendStartDate = date('Y-m-d', strtotime('-6 days'));
            $trendRows = Database::fetchAll("SELECT date, status, COUNT(*) as count 
                                             FROM attendance 
                                             WHERE date >= ? 
                                             GROUP BY date, status", [$trendStartDate]);
            $trendMap = [];
            foreach ($trendRows as $tr) {
                $trendMap[$tr['date']][$tr['status']] = (int)$tr['count'];
            }
            $trendDays = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $presCount = ($trendMap[$d]['present'] ?? 0) + ($trendMap[$d]['late'] ?? 0);
                $leaveCount = $trendMap[$d]['leave'] ?? 0;
                $trendDays[] = [
                    'date' => date('d M', strtotime($d)),
                    'present' => $presCount,
                    'leave' => $leaveCount
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
            $rawRegs = Attendance::getRegularizationRequests($empId, null, $subordinateIds);
            $pendingRegs = array_values(array_filter($rawRegs, fn($r) => $r['status'] === 'pending'));
            $pendingRegsCount = count($pendingRegs);

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

            // 7-Day Team Availability & Coverage Radar
            $today = date('Y-m-d');
            $sevenDaysEnd = date('Y-m-d', strtotime('+6 days'));
            $teamCoverage7Days = [];
            $totalScheduledLeavesCount = 0;

            $allTeamLeaves = !empty($subordinateIds) ? Leave::getRequests(null, null, [
                'employee_ids' => $subordinateIds,
            ]) : [];

            $overlappingLeaves = array_filter($allTeamLeaves, function($l) use ($today, $sevenDaysEnd) {
                return in_array($l['status'], ['approved', 'pending']) && $l['to_date'] >= $today && $l['from_date'] <= $sevenDaysEnd;
            });

            for ($i = 0; $i < 7; $i++) {
                $curDate = date('Y-m-d', strtotime("+{$i} days"));
                $dayLeaves = [];
                foreach ($overlappingLeaves as $l) {
                    if ($curDate >= $l['from_date'] && $curDate <= $l['to_date']) {
                        $dayLeaves[] = $l;
                    }
                }
                $leavesCount = count($dayLeaves);
                if ($leavesCount > 0) $totalScheduledLeavesCount += $leavesCount;
                $availableCount = max(0, $teamCount - $leavesCount);
                $coveragePercent = $teamCount > 0 ? round(($availableCount / $teamCount) * 100) : 100;

                $teamCoverage7Days[] = [
                    'date' => $curDate,
                    'day_name' => date('D', strtotime($curDate)),
                    'display_date' => date('d M', strtotime($curDate)),
                    'is_today' => ($i === 0),
                    'leaves' => $dayLeaves,
                    'leaves_count' => $leavesCount,
                    'available_count' => $availableCount,
                    'coverage_percent' => $coveragePercent
                ];
            }

            require_once BASE_PATH . '/views/dashboard/manager.php';
        } else {
            // Employee Self-Service (ESS)
            $myTodayAtt = Attendance::getToday($empId);
            $month = (int)date('m');
            $year = (int)date('Y');
            $attSummary = Attendance::getMonthlySummary($empId, $month, $year);
            $leaveBalances = Leave::getBalances($empId, $year);
            $leaveTypes = Leave::getTypes();
            $myRecentLeaves = Leave::getRequests(null, $empId);
            $myRecentLeaves = array_slice($myRecentLeaves, 0, 5);
            $latestPayroll = Database::fetchOne("SELECT * FROM payrolls WHERE employee_id = ? ORDER BY year DESC, month DESC LIMIT 1", [$empId]);
            $myAssets = Database::fetchAll("SELECT * FROM assets WHERE current_employee_id = ? AND status = 'allocated' ORDER BY name ASC", [$empId]);

            // Last 7 days hours worked for Employee Graph Chart - Optimized single bulk query
            $empTrendStartDate = date('Y-m-d', strtotime('-6 days'));
            $empAttLogs = Database::fetchAll("SELECT date, total_hours FROM attendance WHERE employee_id = ? AND date >= ?", [$empId, $empTrendStartDate]);
            $empAttMap = [];
            foreach ($empAttLogs as $ea) {
                $empAttMap[$ea['date']] = (float)$ea['total_hours'];
            }
            $last7DaysHours = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $last7DaysHours[] = [
                    'day' => date('D, d M', strtotime($d)),
                    'hours' => $empAttMap[$d] ?? 0.0
                ];
            }

            require_once BASE_PATH . '/views/dashboard/employee.php';
        }
    }

    /**
     * Submit a celebration greeting
     */
    public function sendWish(): void {
        Auth::requireLogin();
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('');
        }

        if (!validate_csrf()) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'Security token invalid. Please refresh the page.'], 403);
            }
            flash('error', 'Security token invalid.');
            redirect('');
        }

        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $type = trim($_POST['celebration_type'] ?? 'birthday');
        $message = trim($_POST['message'] ?? '');

        if ($receiverId <= 0 || empty($message)) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'Please provide a recipient and a greeting message.'], 422);
            }
            flash('error', 'Please provide a recipient and a greeting message.');
            redirect('');
        }

        $ok = Celebration::sendWish((int)$user['id'], $receiverId, $type, $message);

        if (is_ajax()) {
            if ($ok) {
                $wishes = Celebration::getWishes($receiverId, $type);
                json_response([
                    'success' => true,
                    'message' => 'Your warm wishes have been posted! 🎉',
                    'wishes_count' => count($wishes),
                    'receiver_id' => $receiverId,
                    'type' => $type
                ]);
            } else {
                json_response(['success' => false, 'message' => 'Failed to record wishes. Please try again.'], 500);
            }
        }

        if ($ok) {
            flash('success', 'Your warm wishes have been posted! 🎉');
        } else {
            flash('error', 'Failed to send wishes.');
        }
        redirect('');
    }

    /**
     * Get wishes list for an employee
     */
    public function getWishes(): void {
        Auth::requireLogin();
        $receiverId = (int)($_GET['employee_id'] ?? 0);
        $type = !empty($_GET['type']) ? trim($_GET['type']) : null;

        if ($receiverId <= 0) {
            json_response(['success' => false, 'wishes' => []], 422);
        }

        $wishes = Celebration::getWishes($receiverId, $type);
        json_response(['success' => true, 'wishes' => $wishes]);
    }
}
