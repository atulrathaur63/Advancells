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
                $deptId = Department::create($_POST);
                if (!empty($_POST['head_id'])) {
                    $headEmp = Employee::findById((int)$_POST['head_id']);
                    if ($headEmp) {
                        Database::query("UPDATE users SET role = 'manager' WHERE id = ? AND role = 'employee'", [$headEmp['user_id']]);
                    }
                }
                Database::logActivity(Auth::id(), 'CREATE', 'DEPARTMENTS', "Created department '" . trim($_POST['name'] ?? '') . "'");
                flash('success', 'Department created successfully!');
            } elseif ($action === 'update_department') {
                $id = (int)($_POST['id'] ?? 0);
                Department::update($id, $_POST);
                if (!empty($_POST['head_id'])) {
                    $headEmp = Employee::findById((int)$_POST['head_id']);
                    if ($headEmp) {
                        Database::query("UPDATE users SET role = 'manager' WHERE id = ? AND role = 'employee'", [$headEmp['user_id']]);
                    }
                }
                Database::logActivity(Auth::id(), 'UPDATE', 'DEPARTMENTS', "Updated department '" . trim($_POST['name'] ?? '') . "'");
                flash('success', 'Department updated successfully!');
            } elseif ($action === 'create_designation') {
                Department::createDesignation($_POST);
                Database::logActivity(Auth::id(), 'CREATE', 'DEPARTMENTS', "Created designation '" . trim($_POST['title'] ?? '') . "'");
                flash('success', 'Designation added successfully!');
            } elseif ($action === 'update_designation') {
                $id = (int)($_POST['id'] ?? 0);
                Department::updateDesignation($id, $_POST);
                Database::logActivity(Auth::id(), 'UPDATE', 'DEPARTMENTS', "Updated designation '" . trim($_POST['title'] ?? '') . "'");
                flash('success', 'Designation updated successfully!');
            } elseif ($action === 'delete_department') {
                $this->delete();
                return;
            } elseif ($action === 'delete_designation') {
                $this->deleteDesignation();
                return;
            }
            redirect('departments');
        }

        $departments = Department::getAll();
        $designations = Department::getDesignations();
        $managers = Employee::getManagers();

        require_once BASE_PATH . '/views/departments/index.php';
    }

    public function delete(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('departments');
            }

            $id = (int)($_POST['id'] ?? 0);
            $check = Department::canDelete($id);
            if (!$check['can_delete']) {
                flash('danger', $check['reason']);
                redirect('departments');
            }

            if (Department::delete($id)) {
                Database::logActivity(Auth::id(), 'DELETE', 'DEPARTMENTS', "Deleted department #{$id}");
                flash('success', 'Department deleted successfully!');
            } else {
                flash('danger', 'Could not delete department.');
            }
        }
        redirect('departments');
    }

    public function deleteDesignation(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('departments');
            }

            $id = (int)($_POST['id'] ?? 0);
            $check = Department::canDeleteDesignation($id);
            if (!$check['can_delete']) {
                flash('danger', $check['reason']);
                redirect('departments');
            }

            if (Department::deleteDesignation($id)) {
                Database::logActivity(Auth::id(), 'DELETE', 'DEPARTMENTS', "Deleted designation #{$id}");
                flash('success', 'Designation deleted successfully!');
            } else {
                flash('danger', 'Could not delete designation.');
            }
        }
        redirect('departments');
    }
}
