<?php
/**
 * Leave Management Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Leave.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Notification.php';

class LeaveController {
    public function myLeaves(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();
        $year = (int)($_GET['year'] ?? date('Y'));

        $balances = Leave::getBalances($empId, $year);
        $requests = Leave::getRequests(null, $empId, ['year' => $year]);
        $types = Leave::getTypes();

        require_once BASE_PATH . '/views/leaves/my_leaves.php';
    }

    public function apply(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();
        $user = Auth::user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('leaves/my-leaves');
            }

            $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
            $fromDate = $_POST['from_date'] ?? '';
            $toDate = $_POST['to_date'] ?? '';
            $isHalfDay = !empty($_POST['is_half_day']);
            $halfDayType = $_POST['half_day_type'] ?? null;
            $reason = trim($_POST['reason'] ?? '');

            if ($isHalfDay && empty($toDate)) {
                $toDate = $fromDate;
            }

            if (empty($leaveTypeId) || empty($fromDate) || empty($toDate) || empty($reason)) {
                flash('danger', 'Please provide leave type, dates, and a valid reason.');
                redirect('leaves/my-leaves');
            }

            $res = Leave::apply($empId, $leaveTypeId, $fromDate, $toDate, $reason, $isHalfDay, $halfDayType, $user['manager_id']);
            if ($res['success']) {
                if (!empty($user['manager_id'])) {
                    $mgr = Employee::findById((int)$user['manager_id']);
                    if ($mgr && !empty($mgr['user_id'])) {
                        Notification::send((int)$mgr['user_id'], 'leave', 'New Leave Request', "{$user['name']} applied for leave ({$fromDate} to {$toDate})", 'leaves/approvals', 'fa-calendar-minus', '#e11d48');
                    }
                }
            }
            flash($res['success'] ? 'success' : 'danger', $res['message']);
            redirect('leaves/my-leaves');
        }

        redirect('leaves/my-leaves');
    }

    public function approvals(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        $role = Auth::role();
        $empId = Auth::employeeId();

        $managerId = ($role === 'manager') ? $empId : null;
        $status = $_GET['status'] ?? 'pending';

        $filters = ['status' => $status];
        if ($role === 'manager') {
            $subordinateIds = Employee::getSubordinateIds((int)$empId, false);
            $filters['employee_ids'] = $subordinateIds;
        }

        $requests = Leave::getRequests($managerId, null, $filters);

        require_once BASE_PATH . '/views/leaves/approvals.php';
    }

    public function approve(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $reqId = (int)($_POST['request_id'] ?? 0);
            $remarks = trim($_POST['approver_remarks'] ?? '');
            $approverEmpId = (int)Auth::employeeId();

            $leaveReq = Database::fetchOne("SELECT lr.*, e.user_id, e.first_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.id = ?", [$reqId]);
            if (!$leaveReq) {
                flash('danger', 'Leave request record not found.');
                redirect('leaves/approvals');
            }

            if (Auth::role() === 'manager') {
                if ((int)$leaveReq['manager_id'] !== $approverEmpId && !Employee::isSubordinateOf((int)$leaveReq['employee_id'], $approverEmpId)) {
                    flash('danger', 'Unauthorized! You can only approve leave requests for your own team members.');
                    redirect('leaves/approvals');
                }
            }

            if (Leave::approve($reqId, $approverEmpId, $remarks)) {
                if ($leaveReq && !empty($leaveReq['user_id'])) {
                    Notification::send((int)$leaveReq['user_id'], 'leave', 'Leave Approved', "Your leave from {$leaveReq['from_date']} to {$leaveReq['to_date']} was approved.", 'leaves/my-leaves', 'fa-calendar-check', '#16a34a');
                }
                flash('success', 'Leave application approved successfully!');
            } else {
                flash('danger', 'Could not approve leave application.');
            }
        }
        redirect('leaves/approvals');
    }

    public function reject(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $reqId = (int)($_POST['request_id'] ?? 0);
            $remarks = trim($_POST['approver_remarks'] ?? '');
            $approverEmpId = (int)Auth::employeeId();

            $leaveReq = Database::fetchOne("SELECT lr.*, e.user_id, e.first_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.id = ?", [$reqId]);
            if (!$leaveReq) {
                flash('danger', 'Leave request record not found.');
                redirect('leaves/approvals');
            }

            if (Auth::role() === 'manager') {
                if ((int)$leaveReq['manager_id'] !== $approverEmpId && !Employee::isSubordinateOf((int)$leaveReq['employee_id'], $approverEmpId)) {
                    flash('danger', 'Unauthorized! You can only reject leave requests for your own team members.');
                    redirect('leaves/approvals');
                }
            }

            if (Leave::reject($reqId, $approverEmpId, $remarks)) {
                if ($leaveReq && !empty($leaveReq['user_id'])) {
                    Notification::send((int)$leaveReq['user_id'], 'leave', 'Leave Rejected', "Your leave from {$leaveReq['from_date']} to {$leaveReq['to_date']} was rejected." . ($remarks ? " Reason: {$remarks}" : ""), 'leaves/my-leaves', 'fa-calendar-xmark', '#dc2626');
                }
                flash('warning', 'Leave application has been rejected.');
            } else {
                flash('danger', 'Could not reject leave application.');
            }
        }
        redirect('leaves/approvals');
    }

    public function holidays(): void {
        Auth::requireLogin();
        $year = (int)($_GET['year'] ?? date('Y'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isHR()) {
            if (validate_csrf()) {
                Leave::addHoliday($_POST);
                flash('success', 'New holiday added to the calendar!');
                redirect('leaves/holidays?year=' . $year);
            }
        }

        $holidays = Leave::getHolidays($year);
        require_once BASE_PATH . '/views/leaves/holidays.php';
    }
}
