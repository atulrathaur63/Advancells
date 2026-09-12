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
require_once __DIR__ . '/../Models/Asset.php';

class EmployeeController {
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $filters = [
            'department_id' => $_GET['department_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => trim($_GET['search'] ?? '')
        ];

        // Managers can only see their assigned reportees and themselves
        if (Auth::role() === 'manager') {
            $managerEmpId = (int)Auth::employeeId();
            $subordinateIds = Employee::getSubordinateIds($managerEmpId, true);
            $filters['employee_ids'] = !empty($subordinateIds) ? $subordinateIds : [-1];
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 15)));

        $totalEmployees = Employee::countAll($filters);
        $pagination = paginate($totalEmployees, $page, $perPage);
        $employees = Employee::getAll($filters, $pagination['limit'], $pagination['offset']);
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

            // Avatar Upload Handling
            $avatarPath = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($ext, $allowed, true) || !in_array($mime, $allowedMimes, true)) {
                    flash('danger', 'Invalid photo format! Only JPG, JPEG, PNG, and WEBP images are permitted.');
                    redirect('employees/create');
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    flash('danger', 'Photo size exceeds 2MB limit! Please upload a smaller image.');
                    redirect('employees/create');
                } else {
                    $dir = 'assets/uploads/avatars';
                    $fullDir = BASE_PATH . '/' . $dir;
                    if (!is_dir($fullDir)) {
                        mkdir($fullDir, 0755, true);
                    }
                    $filename = 'avatar_emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest = $fullDir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $avatarPath = $dir . '/' . $filename;
                    }
                }
            }

            try {
                $postData = $_POST;
                if ($avatarPath) {
                    $postData['avatar'] = $avatarPath;
                }
                $newId = Employee::create($postData, $password, $role);
                flash('success', "Employee {$empCode} created successfully with default login password: {$password}");
                redirect('employees/view?id=' . $newId);
            } catch (Exception $e) {
                if ($avatarPath && file_exists(BASE_PATH . '/' . $avatarPath)) {
                    @unlink(BASE_PATH . '/' . $avatarPath);
                }
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
            $postData = $_POST;
            $removeAvatar = !empty($_POST['remove_avatar']);

            if ($removeAvatar) {
                if (!empty($employee['avatar']) && file_exists(BASE_PATH . '/' . $employee['avatar'])) {
                    @unlink(BASE_PATH . '/' . $employee['avatar']);
                }
                $postData['avatar'] = null;
            } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($ext, $allowed, true) || !in_array($mime, $allowedMimes, true)) {
                    flash('danger', 'Invalid photo format! Only JPG, JPEG, PNG, and WEBP images are permitted.');
                    redirect('employees/edit?id=' . $id);
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    flash('danger', 'Photo size exceeds 2MB limit! Please upload a smaller image.');
                    redirect('employees/edit?id=' . $id);
                } else {
                    $dir = 'assets/uploads/avatars';
                    $fullDir = BASE_PATH . '/' . $dir;
                    if (!is_dir($fullDir)) {
                        mkdir($fullDir, 0755, true);
                    }
                    $filename = 'avatar_emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest = $fullDir . '/' . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        if (!empty($employee['avatar']) && file_exists(BASE_PATH . '/' . $employee['avatar'])) {
                            @unlink(BASE_PATH . '/' . $employee['avatar']);
                        }
                        $postData['avatar'] = $dir . '/' . $filename;
                    }
                }
            }

            try {
                Employee::update($id, $postData, $role);

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
        $currentEmpId = (int)Auth::employeeId();

        if ($currentRole === 'employee' && $id !== $currentEmpId) {
            flash('danger', 'You can only view your own profile.');
            redirect('employees/view?id=' . $currentEmpId);
        }

        if ($currentRole === 'manager') {
            if ($id !== $currentEmpId && !Employee::isSubordinateOf($id, $currentEmpId)) {
                flash('danger', 'Unauthorized access! You can only view profiles of your direct or indirect team reportees.');
                redirect('employees/view?id=' . $currentEmpId);
            }
        }

        $employee = Employee::findById($id);
        if (!$employee) {
            flash('danger', 'Employee not found!');
            redirect(Auth::isManager() ? 'employees' : 'dashboard');
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

        $activeAssets = Asset::getByEmployee($id, true);
        $pastAssets = Asset::getByEmployee($id, false);
        $availableAssets = Asset::getAvailable();
        $assetCategories = Asset::getCategories();
        $assetConditions = Asset::getConditions();

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
