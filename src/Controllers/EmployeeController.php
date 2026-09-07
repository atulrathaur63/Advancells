<?php
/**
 * Employee Management Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Leave.php';
require_once __DIR__ . '/../Models/Payroll.php';
require_once __DIR__ . '/../Models/Performance.php';
require_once __DIR__ . '/../Models/Document.php';

class EmployeeController {
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $filters = [
            'department_id' => $_GET['department_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => trim($_GET['search'] ?? '')
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        $employees = Employee::getAll($filters);
        $departments = Department::getAll();

        require_once BASE_PATH . '/views/employees/index.php';
    }

    public function create(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('employees/create');
            }

            // Basic validation
            $email = strtolower(trim($_POST['email'] ?? ''));
            $empCode = strtoupper(trim($_POST['emp_code'] ?? ''));
            $password = $_POST['password'] ?? 'Advancells@123';
            $role = $_POST['role'] ?? 'employee';

            $existingEmail = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existingEmail) {
                flash('danger', 'An account with this email address already exists!');
                redirect('employees/create');
            }

            $existingCode = Database::fetchOne("SELECT id FROM employees WHERE emp_code = ?", [$empCode]);
            if ($existingCode) {
                flash('danger', 'Employee code ' . $empCode . ' is already taken!');
                redirect('employees/create');
            }

            try {
                $newId = Employee::create($_POST, $password, $role);
                flash('success', "Employee {$empCode} created successfully with default login password: {$password}");
                redirect('employees/view?id=' . $newId);
            } catch (Exception $e) {
                flash('danger', 'Failed to create employee: ' . $e->getMessage());
                redirect('employees/create');
            }
        }

        $departments = Department::getAll();
        $designations = Department::getDesignations();
        $managers = Employee::getManagers();

        require_once BASE_PATH . '/views/employees/create.php';
    }

    public function edit(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        $id = (int)($_GET['id'] ?? 0);
        $employee = Employee::findById($id);
        if (!$employee) {
            flash('danger', 'Employee not found!');
            redirect('employees');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('employees/edit?id=' . $id);
            }

            $role = $_POST['role'] ?? null;
            try {
                Employee::update($id, $_POST, $role);

                // Update salary if specified
                if (isset($_POST['basic_salary'])) {
                    Payroll::saveSalaryStructure($id, $_POST);
                }

                flash('success', "Employee details for {$employee['emp_code']} updated successfully!");
                redirect('employees/view?id=' . $id);
            } catch (Exception $e) {
                flash('danger', 'Failed to update employee: ' . $e->getMessage());
                redirect('employees/edit?id=' . $id);
            }
        }

        $departments = Department::getAll();
        $designations = Department::getDesignations();
        $managers = Employee::getManagers();
        $salary = Payroll::getSalaryStructure($id);

        require_once BASE_PATH . '/views/employees/edit.php';
    }

    public function view(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? Auth::employeeId());
        
        // Non-admin can only view self or reportees
        $currentRole = Auth::role();
        if ($currentRole === 'employee' && $id !== Auth::employeeId()) {
            flash('danger', 'You can only view your own profile.');
            redirect('employees/view?id=' . Auth::employeeId());
        }

        $employee = Employee::findById($id);
        if (!$employee) {
            flash('danger', 'Employee not found!');
            redirect('employees');
        }

        $year = (int)date('Y');
        $month = (int)date('m');
        $leaveBalances = Leave::getBalances($id, $year);
        $attendanceSummary = Attendance::getMonthlySummary($id, $month, $year);
        $recentLeaves = Leave::getRequests(null, $id);
        $recentPayrolls = Payroll::getPayrolls(['employee_id' => $id]);
        $goals = Performance::getGoals($id);
        $documents = Document::getByEmployee($id);
        $docCategories = Document::getCategories();
        $compliance = Document::getComplianceSummary($id);

        require_once BASE_PATH . '/views/employees/view.php';
    }

    private function exportCsv(array $filters): void {
        $employees = Employee::getAll($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=advancells_employees_' . date('Ymd_His') . '.csv');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Emp Code', 'Full Name', 'Email', 'Phone', 'Department', 'Designation', 'Joining Date', 'Status', 'Employment Type', 'Bank Name', 'Account Number']);

        foreach ($employees as $e) {
            fputcsv($out, [
                $e['emp_code'],
                $e['first_name'] . ' ' . $e['last_name'],
                $e['email'],
                $e['phone'],
                $e['department_name'] ?? 'N/A',
                $e['designation_title'] ?? 'N/A',
                $e['date_of_joining'],
                $e['status'],
                $e['employment_type'],
                $e['bank_name'] ?? '',
                $e['account_number'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }
}
