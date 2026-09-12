<?php
/**
 * Resignation & Exit Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Resignation.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Notification.php';
require_once __DIR__ . '/../Models/Asset.php';

class ResignationController {
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 15)));

        // Managers only see resignations for their reportees
        $subIds = !Auth::isHR() ? Employee::getSubordinateIds(Auth::employeeId(), false) : null;
        $totalResignations = Resignation::countAll($subIds);
        $pagination = paginate($totalResignations, $page, $perPage);
        $resignations = Resignation::getAll($subIds, $pagination['limit'], $pagination['offset']);

        // Attach pending unreturned assets for each resigning employee
        foreach ($resignations as &$r) {
            $r['pending_assets'] = Asset::getPendingExitAssets((int)$r['employee_id']);
        }
        unset($r);

        require_once BASE_PATH . '/views/resignations/index.php';
    }

    public function apply(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();

        $existing = Resignation::getByEmployeeId($empId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('resignations/apply');
            }

            if ($existing) {
                flash('danger', 'You already have an active resignation request.');
                redirect('resignations/apply');
            }

            $reason = trim($_POST['reason'] ?? '');
            $lastDay = $_POST['desired_last_working_day'] ?? '';

            if (empty($reason) || empty($lastDay)) {
                flash('danger', 'Please provide a reason and desired last working day.');
                redirect('resignations/apply');
            }

            Resignation::submit($empId, $reason, $lastDay);
            flash('success', 'Your resignation request has been submitted to your reporting manager and HR.');
            redirect('resignations/apply');
        }

        require_once BASE_PATH . '/views/resignations/apply.php';
    }

    public function update(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $approvedDate = !empty($_POST['approved_last_working_day']) ? $_POST['approved_last_working_day'] : null;
            $exitNotes = trim($_POST['exit_interview_notes'] ?? '');

            $resignation = Resignation::findById($id);
            if (!$resignation) {
                flash('danger', 'Resignation record not found.');
                redirect('resignations');
                return;
            }

            // If manager (and not HR), verify reportee relationship and restrict allowed statuses
            if (!Auth::isHR()) {
                $mgrEmpId = Auth::employeeId();
                if (!$mgrEmpId || !Employee::isSubordinateOf($resignation['employee_id'], $mgrEmpId)) {
                    flash('danger', 'Unauthorized access! You can only manage resignations for your reporting team.');
                    redirect('resignations');
                    return;
                }

                if (!in_array($status, ['manager_approved', 'rejected'], true)) {
                    flash('danger', 'Unauthorized action! Only HR can provide clearance or finalize exit.');
                    redirect('resignations');
                    return;
                }
            }

            // Hardware Clearance Check: Prevent completing exit if employee has active company assets
            if ($status === 'completed') {
                $pendingAssets = Asset::getPendingExitAssets((int)$resignation['employee_id']);
                if (!empty($pendingAssets)) {
                    $assetNames = implode(', ', array_map(fn($a) => $a['name'] . ' (' . $a['asset_code'] . ')', $pendingAssets));
                    flash('danger', "Cannot finalize exit! {$resignation['employee_name']} still holds " . count($pendingAssets) . " unreturned company asset(s): {$assetNames}. Please record device returns in Asset Management before completing separation.");
                    redirect('resignations');
                    return;
                }
            }

            Resignation::updateStatus($id, $status, $approvedDate, $exitNotes);

            // Audit log
            Database::logActivity(
                Auth::id(),
                $status === 'completed' ? 'EXIT_COMPLETED' : 'RESIGNATION_UPDATE',
                'RESIGNATION',
                "Updated resignation #{$id} status to {$status}"
            );

            // Notify employee if user account is linked
            if (!empty($resignation['user_id'])) {
                if ($status === 'completed') {
                    Notification::send(
                        $resignation['user_id'],
                        'resignation',
                        'Exit Process Completed',
                        'Your exit formalities and clearance have been completed. Your employee account has been deactivated.',
                        'dashboard',
                        'fa-door-open',
                        '#64748b'
                    );
                } elseif ($status === 'manager_approved') {
                    Notification::send(
                        $resignation['user_id'],
                        'resignation',
                        'Resignation Manager Approved',
                        'Your reporting manager has approved your resignation request. It is now forwarded to HR for clearance.',
                        'resignations/apply',
                        'fa-clipboard-check',
                        '#2563eb'
                    );
                } elseif ($status === 'hr_approved') {
                    Notification::send(
                        $resignation['user_id'],
                        'resignation',
                        'HR Clearance Granted',
                        'HR has approved your exit clearance. Final settlement is being processed.',
                        'resignations/apply',
                        'fa-user-check',
                        '#16a34a'
                    );
                } elseif ($status === 'rejected') {
                    Notification::send(
                        $resignation['user_id'],
                        'resignation',
                        'Resignation Request Rejected',
                        'Your resignation request was rejected. Please contact your manager or HR.',
                        'resignations/apply',
                        'fa-xmark',
                        '#dc2626'
                    );
                }
            }

            flash('success', 'Resignation status updated successfully!');
        }
        redirect('resignations');
    }
}
