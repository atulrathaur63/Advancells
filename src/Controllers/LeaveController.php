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

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 10)));

        $balances = Leave::getBalances($empId, $year);
        $totalRequests = Leave::countRequests(null, $empId, ['year' => $year]);
        $pagination = paginate($totalRequests, $page, $perPage);
        $requests = Leave::getRequests(null, $empId, ['year' => $year], $pagination['limit'], $pagination['offset']);
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
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Please provide leave type, dates, and a valid reason.'], 422);
                }
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

            if (is_ajax()) {
                json_response([
                    'success' => $res['success'],
                    'message' => $res['message']
                ], $res['success'] ? 200 : 422);
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

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 15)));

        $totalRequests = Leave::countRequests($managerId, null, $filters);
        $pagination = paginate($totalRequests, $page, $perPage);
        $requests = Leave::getRequests($managerId, null, $filters, $pagination['limit'], $pagination['offset']);

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
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Leave request record not found.'], 404);
                }
                flash('danger', 'Leave request record not found.');
                redirect('leaves/approvals');
            }

            // Prevent self-approval (HR Admin / Manager cannot approve their own leave)
            if ($approverEmpId > 0 && (int)$leaveReq['employee_id'] === $approverEmpId && Auth::role() !== 'super_admin') {
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Unauthorized! You cannot approve your own leave application. It must be approved by your reporting manager or Super Admin.'], 403);
                }
                flash('danger', 'Unauthorized! You cannot approve your own leave application. It must be approved by your reporting manager or Super Admin.');
                redirect('leaves/approvals');
            }

            if (Auth::role() === 'manager') {
                if ((int)$leaveReq['manager_id'] !== $approverEmpId && !Employee::isSubordinateOf((int)$leaveReq['employee_id'], $approverEmpId)) {
                    if (is_ajax()) {
                        json_response(['success' => false, 'message' => 'Unauthorized! You can only approve leave requests for your own team members.'], 403);
                    }
                    flash('danger', 'Unauthorized! You can only approve leave requests for your own team members.');
                    redirect('leaves/approvals');
                }
            }

            if (Leave::approve($reqId, $approverEmpId, $remarks)) {
                if ($leaveReq && !empty($leaveReq['user_id'])) {
                    Notification::send((int)$leaveReq['user_id'], 'leave', 'Leave Approved', "Your leave from {$leaveReq['from_date']} to {$leaveReq['to_date']} was approved.", 'leaves/my-leaves', 'fa-calendar-check', '#16a34a');
                }
                if (is_ajax()) {
                    json_response(['success' => true, 'message' => 'Leave application approved successfully!']);
                }
                flash('success', 'Leave application approved successfully!');
            } else {
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Could not approve leave application.'], 500);
                }
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
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Leave request record not found.'], 404);
                }
                flash('danger', 'Leave request record not found.');
                redirect('leaves/approvals');
            }

            // Prevent self-rejection
            if ($approverEmpId > 0 && (int)$leaveReq['employee_id'] === $approverEmpId && Auth::role() !== 'super_admin') {
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Unauthorized! You cannot reject your own leave application. It must be actioned by your reporting manager or Super Admin.'], 403);
                }
                flash('danger', 'Unauthorized! You cannot reject your own leave application. It must be actioned by your reporting manager or Super Admin.');
                redirect('leaves/approvals');
            }

            if (Auth::role() === 'manager') {
                if ((int)$leaveReq['manager_id'] !== $approverEmpId && !Employee::isSubordinateOf((int)$leaveReq['employee_id'], $approverEmpId)) {
                    if (is_ajax()) {
                        json_response(['success' => false, 'message' => 'Unauthorized! You can only reject leave requests for your own team members.'], 403);
                    }
                    flash('danger', 'Unauthorized! You can only reject leave requests for your own team members.');
                    redirect('leaves/approvals');
                }
            }

            if (Leave::reject($reqId, $approverEmpId, $remarks)) {
                if ($leaveReq && !empty($leaveReq['user_id'])) {
                    Notification::send((int)$leaveReq['user_id'], 'leave', 'Leave Rejected', "Your leave from {$leaveReq['from_date']} to {$leaveReq['to_date']} was rejected." . ($remarks ? " Reason: {$remarks}" : ""), 'leaves/my-leaves', 'fa-calendar-xmark', '#dc2626');
                }
                if (is_ajax()) {
                    json_response(['success' => true, 'message' => 'Leave application rejected.']);
                }
                flash('warning', 'Leave application has been rejected.');
            } else {
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'Could not reject leave application.'], 500);
                }
                flash('danger', 'Could not reject leave application.');
            }
        }
        redirect('leaves/approvals');
    }

    public function cancel(): void {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                if (is_ajax()) {
                    json_response(['success' => false, 'message' => 'CSRF verification failed.'], 403);
                }
                flash('danger', 'Security validation failed.');
                redirect('leaves/my-leaves');
            }

            $reqId = (int)($_POST['request_id'] ?? 0);
            $reason = trim($_POST['cancel_reason'] ?? $_POST['reason'] ?? 'Cancelled by applicant');
            $cancellerEmpId = (int)Auth::employeeId();

            $res = Leave::cancel($reqId, $cancellerEmpId, $reason);

            if (is_ajax()) {
                json_response($res, $res['success'] ? 200 : 422);
            }

            flash($res['success'] ? 'success' : 'danger', $res['message']);

            // Redirect back to approvals if actioned from approvals queue
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referer, 'leaves/approvals') !== false && (Auth::isHR() || Auth::role() === 'manager')) {
                redirect('leaves/approvals');
            }
            redirect('leaves/my-leaves');
        }
        redirect('leaves/my-leaves');
    }

    public function calculateDays(): void {
        Auth::requireLogin();
        $empId = (int)Auth::employeeId();
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $isHalfDay = !empty($_GET['is_half_day']);

        if (empty($fromDate)) {
            json_response(['success' => false, 'message' => 'From date is required.'], 422);
        }

        if (!$isHalfDay && empty($toDate)) {
            $toDate = $fromDate;
        }

        $calc = Leave::calculateSandwichDays($empId, $fromDate, $toDate, $isHalfDay);
        json_response($calc, $calc['success'] ? 200 : 422);
    }

    public function holidays(): void {
        Auth::requireLogin();
        $year = (int)($_GET['year'] ?? date('Y'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isHR()) {
            if (!validate_csrf()) {
                redirect('leaves/holidays?year=' . $year);
            }

            $action = $_POST['action'] ?? 'add';
            if ($action === 'delete_holiday') {
                $this->deleteHoliday();
                return;
            }

            Leave::addHoliday($_POST);
            flash('success', 'New holiday added to the calendar!');
            redirect('leaves/holidays?year=' . $year);
        }

        $holidays = Leave::getHolidays($year);
        require_once BASE_PATH . '/views/leaves/holidays.php';
    }

    public function deleteHoliday(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('leaves/holidays');
            }

            $id = (int)($_POST['id'] ?? 0);
            $year = (int)($_POST['year'] ?? date('Y'));

            if (Leave::deleteHoliday($id)) {
                flash('success', 'Holiday removed from calendar.');
            } else {
                flash('danger', 'Could not delete holiday.');
            }
            redirect('leaves/holidays?year=' . $year);
        }
        redirect('leaves/holidays');
    }

    public function syncBalances(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $year = (int)($_POST['year'] ?? date('Y'));
            Leave::syncMonthlyAccrual(null, $year);
            flash('success', "Monthly leave accruals synchronized successfully for all active employees for {$year}!");
        }
        redirect('leaves/approvals');
    }

    // Edit Leave Balance

    public function editBalances(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        $year = (int)($_GET['year'] ?? date('Y'));
        $filterEmpId = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        // POST: update balances
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $balanceId = (int)($_POST['balance_id'] ?? 0);
            $newAllocated = (float)($_POST['total_allocated'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            $postYear = (int)($_POST['year'] ?? date('Y'));

            // Security: prevent self-edit
            if ($balanceId > 0) {
                $targetBalance = Database::fetchOne("SELECT employee_id FROM leave_balances WHERE id = ?", [$balanceId]);
                if ($targetBalance && Auth::role() !== 'super_admin') {
                    $currentEmpId = (int)Auth::employeeId();
                    if ((int)$targetBalance['employee_id'] === $currentEmpId) {
                        flash('danger', 'Unauthorized! You cannot edit your own leave balance. It must be adjusted by Super Admin.');
                        redirect('leaves/edit-balances?year=' . $postYear);
                    }
                }

                if (Leave::updateBalanceAllocation($balanceId, $newAllocated, $reason)) {
                    flash('success', 'Leave balance updated successfully.');
                } else {
                    flash('danger', 'Could not update leave balance. Record not found.');
                }
            }

            redirect('leaves/edit-balances?year=' . $postYear . ($filterEmpId ? '&employee_id=' . $filterEmpId : ''));
        }

        // GET: show editor
        $data = Leave::getAllEmployeeBalances($year, $filterEmpId);
        $allEmployees = Employee::getAll(['status' => 'active']);
        $currentEmpId = (int)Auth::employeeId();
        $isSuperAdmin = Auth::role() === 'super_admin';

        require_once BASE_PATH . '/views/leaves/edit_balances.php';
    }
}
