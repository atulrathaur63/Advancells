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
        $subordinateIds = ($role === 'manager') ? Employee::getSubordinateIds((int)$empId, false) : [];
        $requests = Attendance::getRegularizationRequests($managerId, null, $subordinateIds);

        require_once BASE_PATH . '/views/attendance/regularize_approvals.php';
    }

    public function approveRegularization(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $id = (int)($_POST['id'] ?? 0);
            $remarks = trim($_POST['admin_remarks'] ?? '');
            $approverEmpId = (int)Auth::employeeId();

            $regReq = Database::fetchOne("SELECT r.*, e.user_id, e.first_name FROM attendance_regularizations r JOIN employees e ON r.employee_id = e.id WHERE r.id = ?", [$id]);
            if (!$regReq) {
                flash('danger', 'Regularization request not found.');
                redirect('attendance/regularize-approvals');
            }

            if (Auth::role() === 'manager') {
                if ((int)$regReq['manager_id'] !== $approverEmpId && !Employee::isSubordinateOf((int)$regReq['employee_id'], $approverEmpId)) {
                    flash('danger', 'Unauthorized! You can only approve attendance regularizations for your own team members.');
                    redirect('attendance/regularize-approvals');
                }
            }

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
            $approverEmpId = (int)Auth::employeeId();

            $regReq = Database::fetchOne("SELECT r.*, e.user_id, e.first_name FROM attendance_regularizations r JOIN employees e ON r.employee_id = e.id WHERE r.id = ?", [$id]);
            if (!$regReq) {
                flash('danger', 'Regularization request not found.');
                redirect('attendance/regularize-approvals');
            }

            if (Auth::role() === 'manager') {
                if ((int)$regReq['manager_id'] !== $approverEmpId && !Employee::isSubordinateOf((int)$regReq['employee_id'], $approverEmpId)) {
                    flash('danger', 'Unauthorized! You can only reject attendance regularizations for your own team members.');
                    redirect('attendance/regularize-approvals');
                }
            }

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

        $subordinateIds = [];
        if (Auth::role() === 'manager') {
            $subordinateIds = Employee::getSubordinateIds((int)Auth::employeeId(), true);
            $filters['employee_ids'] = $subordinateIds;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        $logs = Attendance::getAttendanceLogs($filters);
        $departments = (Auth::role() === 'manager') ? [] : Department::getAll();
        $todayStats = Attendance::getTodayCompanyStats($subordinateIds);

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

    public function sheet(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;

        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        if ($year < 2020 || $year > 2035) {
            $year = (int)date('Y');
        }

        $scopedEmployeeIds = [];
        if (Auth::role() === 'manager') {
            $scopedEmployeeIds = Employee::getSubordinateIds((int)Auth::employeeId(), true);
        }

        $matrixData = Attendance::getCompanyMonthlyMatrix($month, $year, $departmentId, $scopedEmployeeIds);
        $departments = (Auth::role() === 'manager') ? [] : Department::getAll();

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportMatrixCsv($matrixData);
            return;
        }

        require_once BASE_PATH . '/views/attendance/monthly_sheet.php';
    }

    public function exportMatrixCsv(array $matrixData): void {
        $month = $matrixData['month'];
        $year = $matrixData['year'];
        $totalDays = $matrixData['total_days'];
        $daysMeta = $matrixData['days_meta'];
        $rows = $matrixData['matrix'];

        $filename = sprintf('advancells_attendance_sheet_%04d_%02d_%s.csv', $year, $month, date('Ymd_His'));

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $out = fopen('php://output', 'w');

        fputcsv($out, ["Advancells Monthly Attendance Sheet - {$matrixData['month_name']} {$year}"]);
        fputcsv($out, [
            "Total Workforce: {$matrixData['summary']['total_employees']}",
            "Working Days: {$matrixData['summary']['working_days']}",
            "Avg Attendance: {$matrixData['summary']['company_avg_attendance']}%"
        ]);
        fputcsv($out, []);

        $header = ['Emp Code', 'Employee Name', 'Department', 'Designation'];
        for ($d = 1; $d <= $totalDays; $d++) {
            $meta = $daysMeta[$d];
            $header[] = sprintf('%02d (%s)', $d, $meta['day_name']);
        }
        $header[] = 'Present (P)';
        $header[] = 'Absent (A)';
        $header[] = 'Half Day (HD)';
        $header[] = 'Leaves (L)';
        $header[] = 'Payable Days';
        $header[] = 'Attendance %';

        fputcsv($out, $header);

        foreach ($rows as $r) {
            $emp = $r['employee'];
            $st = $r['stats'];
            $row = [
                $emp['emp_code'] ?? '',
                trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')),
                $emp['department_name'] ?? 'General',
                $emp['designation_title'] ?? 'N/A'
            ];

            for ($d = 1; $d <= $totalDays; $d++) {
                $row[] = $r['days'][$d]['code'] ?? '-';
            }

            $row[] = $st['present'];
            $row[] = $st['absent'];
            $row[] = $st['half_day'];
            $row[] = $st['leave'];
            $row[] = $st['payable_days'];
            $row[] = $st['attendance_rate'] . '%';

            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }
}

