<?php
/**
 * Organization Chart & Hierarchy Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Department.php';

class OrganizationController {
    /**
     * Display the Interactive Organization Chart
     */
    public function index(): void {
        Auth::requireLogin();

        $departmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' 
            ? (int)$_GET['department_id'] 
            : null;

        $orgData = Employee::getOrgHierarchy($departmentId);
        $tree = $orgData['tree'];
        $flat = $orgData['flat'];
        $departments = Department::getAll();
        $stats = Employee::getOrgStats();
        $allManagers = Employee::getManagers();

        // AJAX JSON endpoint
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'tree' => $tree,
                'flat' => $flat
            ]);
            exit;
        }

        require_once BASE_PATH . '/views/organization/index.php';
    }

    /**
     * Reassign reporting manager (HR Admin / Super Admin)
     */
    public function updateManager(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid or expired request.');
            redirect('org-chart');
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

        if (!$employeeId) {
            flash('danger', 'Employee identifier is missing.');
            redirect('org-chart');
        }

        try {
            Employee::updateManager($employeeId, $managerId);
            flash('success', 'Reporting manager updated successfully in organization hierarchy!');
        } catch (Exception $e) {
            flash('danger', 'Failed to update reporting manager: ' . $e->getMessage());
        }

        redirect('org-chart');
    }
}
