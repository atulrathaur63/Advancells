<?php
/**
 * Department & Designation Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Models/Employee.php';

class DepartmentController {
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('departments');
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'create_department') {
                Department::create($_POST);
                flash('success', 'Department created successfully!');
            } elseif ($action === 'create_designation') {
                Department::createDesignation($_POST);
                flash('success', 'Designation added successfully!');
            }
            redirect('departments');
        }

        $departments = Department::getAll();
        $designations = Department::getDesignations();
        $managers = Employee::getManagers();

        require_once BASE_PATH . '/views/departments/index.php';
    }
}
