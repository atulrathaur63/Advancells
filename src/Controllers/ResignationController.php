<?php
/**
 * Resignation & Exit Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Resignation.php';
require_once __DIR__ . '/../Models/Employee.php';

class ResignationController {
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);
        $resignations = Resignation::getAll();
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
            $id = (int)$_POST['id'];
            $status = $_POST['status'];
            $approvedDate = !empty($_POST['approved_last_working_day']) ? $_POST['approved_last_working_day'] : null;
            $exitNotes = trim($_POST['exit_interview_notes'] ?? '');

            Resignation::updateStatus($id, $status, $approvedDate, $exitNotes);
            flash('success', 'Resignation status updated successfully!');
        }
        redirect('resignations');
    }
}
