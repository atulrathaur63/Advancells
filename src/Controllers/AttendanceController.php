<?php
/**
 * Attendance Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Notification.php';

class AttendanceController {
    public function punch(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();
        if (!$empId) {
            flash('danger', 'Your user account is not linked to an employee profile.');
            redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    json_response(['success' => false, 'message' => 'CSRF validation failed'], 400);
                }
                redirect('attendance/punch');
            }

            $type = $_POST['punch_type'] ?? '';
            $notes = trim($_POST['notes'] ?? '');

            if ($type === 'in') {
                $result = Attendance::punchIn($empId, $notes);
            } elseif ($type === 'out') {
                $result = Attendance::punchOut($empId, $notes);
            } else {
                $result = ['success' => false, 'message' => 'Invalid punch type'];
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response($result);
            }

            flash($result['success'] ? 'success' : 'danger', $result['message']);
            redirect('attendance/punch');
        }

        $todayRecord = Attendance::getToday($empId);
        require_once BASE_PATH . '/views/attendance/punch.php';
    }

    public function myAttendance(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();

        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $recordsByDate = Attendance::getMonthlyAttendance($empId, $month, $year);
        $summary = Attendance::getMonthlySummary($empId, $month, $year);
        $regularizations = Attendance::getRegularizationRequests(null, $empId);

        require_once BASE_PATH . '/views/attendance/my_attendance.php';
    }

    public function regularize(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('attendance/regularize');
            }

            $date = $_POST['date'] ?? '';
            $inTime = $_POST['requested_punch_in'] ?? '';
            $outTime = $_POST['requested_punch_out'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            if (empty($date) || empty($inTime) || empty($outTime) || empty($reason)) {
                flash('danger', 'Please fill in all fields including date, in/out times, and reason.');
                redirect('attendance/regularize');
            }

            Attendance::requestRegularization($empId, $date, $inTime, $outTime, $reason, $user['manager_id']);
            if (!empty($user['manager_id'])) {
                $mgr = Employee::findById((int)$user['manager_id']);
                if ($mgr && !empty($mgr['user_id'])) {
                    Notification::send((int)$mgr['user_id'], 'attendance', 'Regularization Request', "{$user['name']} requested attendance regularization for {$date}", 'attendance/regularize-approvals', 'fa-clock-rotate-left', '#0284c7');
                }
            }
            flash('success', 'Attendance regularization request submitted for manager approval!');
            redirect('attendance/my_attendance');
        }

        $regularizations = Attendance::getRegularizationRequests(null, $empId);
        require_once BASE_PATH . '/views/attendance/regularize.php';
    }

    public function regularizeApprovals(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        $role = Auth::role();
        $empId = Auth::employeeId();

        $managerId = ($role === 'manager') ? $empId : null;
        $requests = Attendance::getRegularizationRequests($managerId);

        require_once BASE_PATH . '/views/attendance/regularize_approvals.php';
    }

    public function approveRegularization(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $id = (int)($_POST['id'] ?? 0);
            $remarks = trim($_POST['admin_remarks'] ?? '');
            $regReq = Database::fetchOne("SELECT r.*, e.user_id, e.first_name FROM attendance_regularizations r JOIN employees e ON r.employee_id = e.id WHERE r.id = ?", [$id]);
            if (Attendance::approveRegularization($id, $remarks)) {
                if ($regReq && !empty($regReq['user_id'])) {
                    Notification::send((int)$regReq['user_id'], 'attendance', 'Regularization Approved', "Your regularization request for {$regReq['date']} was approved.", 'attendance/my-attendance', 'fa-clock-rotate-left', '#16a34a');
                }
                flash('success', 'Attendance regularization approved successfully!');
            } else {
                flash('danger', 'Could not approve regularization request.');
            }
        }
        redirect('attendance/regularize-approvals');
    }

    public function rejectRegularization(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $id = (int)($_POST['id'] ?? 0);
            $remarks = trim($_POST['admin_remarks'] ?? '');
            $regReq = Database::fetchOne("SELECT r.*, e.user_id, e.first_name FROM attendance_regularizations r JOIN employees e ON r.employee_id = e.id WHERE r.id = ?", [$id]);
            if (Attendance::rejectRegularization($id, $remarks)) {
                if ($regReq && !empty($regReq['user_id'])) {
                    Notification::send((int)$regReq['user_id'], 'attendance', 'Regularization Rejected', "Your regularization request for {$regReq['date']} was rejected.", 'attendance/my-attendance', 'fa-clock-rotate-left', '#dc2626');
                }
                flash('warning', 'Attendance regularization rejected.');
            } else {
                flash('danger', 'Could not reject regularization request.');
            }
        }
        redirect('attendance/regularize-approvals');
    }

    public function adminLogs(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $filters = [
            'date' => $_GET['date'] ?? date('Y-m-d'),
            'department_id' => $_GET['department_id'] ?? null,
            'status' => $_GET['status'] ?? null
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        $logs = Attendance::getAttendanceLogs($filters);
        $departments = Department::getAll();
        $todayStats = Attendance::getTodayCompanyStats();

        require_once BASE_PATH . '/views/attendance/admin_logs.php';
    }

    private function exportCsv(array $filters): void {
        $logs = Attendance::getAttendanceLogs($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=advancells_attendance_' . date('Ymd_His') . '.csv');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Emp Code', 'Employee Name', 'Department', 'Punch In', 'Punch Out', 'Total Hours', 'Status', 'Regularized', 'Notes']);

        foreach ($logs as $l) {
            fputcsv($out, [
                $l['date'],
                $l['emp_code'],
                $l['employee_name'],
                $l['department_name'] ?? 'N/A',
                $l['punch_in'] ? date('h:i A', strtotime($l['punch_in'])) : '--:--',
                $l['punch_out'] ? date('h:i A', strtotime($l['punch_out'])) : '--:--',
                $l['total_hours'],
                ucfirst($l['status']),
                $l['is_regularized'] ? 'Yes' : 'No',
                $l['notes'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }
}
