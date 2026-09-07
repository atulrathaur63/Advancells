<?php
/**
 * Advancells HRMS - Master Front Controller & Router
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';

// Controllers
require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Controllers/DashboardController.php';
require_once __DIR__ . '/src/Controllers/EmployeeController.php';
require_once __DIR__ . '/src/Controllers/DepartmentController.php';
require_once __DIR__ . '/src/Controllers/AttendanceController.php';
require_once __DIR__ . '/src/Controllers/LeaveController.php';
require_once __DIR__ . '/src/Controllers/PayrollController.php';
require_once __DIR__ . '/src/Controllers/PerformanceController.php';
require_once __DIR__ . '/src/Controllers/ResignationController.php';
require_once __DIR__ . '/src/Controllers/AnnouncementController.php';
require_once __DIR__ . '/src/Controllers/DocumentController.php';
require_once __DIR__ . '/src/Controllers/OrganizationController.php';
require_once __DIR__ . '/src/Controllers/NotificationController.php';

// Extract Route
$route = $_GET['route'] ?? '';
if (empty($route)) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $route = trim(substr($uri, strlen($scriptDir)), '/');
}
$route = trim($route, '/');
if ($route === 'index.php') {
    $route = '';
}

// Dispatch Map
switch ($route) {
    case '':
    case 'dashboard':
        (new DashboardController())->index();
        break;

    // Authentication
    case 'login':
        (new AuthController())->login();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'profile':
        (new AuthController())->profile();
        break;

    // Employees
    case 'employees':
        (new EmployeeController())->index();
        break;
    case 'employees/create':
        (new EmployeeController())->create();
        break;
    case 'employees/edit':
        (new EmployeeController())->edit();
        break;
    case 'employees/view':
        (new EmployeeController())->view();
        break;

    // Departments & Designations
    case 'departments':
        (new DepartmentController())->index();
        break;


    // Organization Chart & Hierarchy
    case 'org-chart':
    case 'organization':
    case 'organization/chart':
        (new OrganizationController())->index();
        break;
    case 'organization/update-manager':
        (new OrganizationController())->updateManager();
        break;

    // Attendance
    case 'attendance/punch':
        (new AttendanceController())->punch();
        break;
    case 'attendance/my-attendance':
        (new AttendanceController())->myAttendance();
        break;
    case 'attendance/regularize':
        (new AttendanceController())->regularize();
        break;
    case 'attendance/regularize-approvals':
        (new AttendanceController())->regularizeApprovals();
        break;
    case 'attendance/approve-regularization':
        (new AttendanceController())->approveRegularization();
        break;
    case 'attendance/reject-regularization':
        (new AttendanceController())->rejectRegularization();
        break;
    case 'attendance/admin-logs':
        (new AttendanceController())->adminLogs();
        break;

    // Leaves
    case 'leaves/my-leaves':
        (new LeaveController())->myLeaves();
        break;
    case 'leaves/apply':
        (new LeaveController())->apply();
        break;
    case 'leaves/approvals':
        (new LeaveController())->approvals();
        break;
    case 'leaves/approve':
        (new LeaveController())->approve();
        break;
    case 'leaves/reject':
        (new LeaveController())->reject();
        break;
    case 'leaves/holidays':
        (new LeaveController())->holidays();
        break;

    // Payroll
    case 'payroll':
        (new PayrollController())->index();
        break;
    case 'payroll/process':
        (new PayrollController())->process();
        break;
    case 'payroll/salary-setup':
        (new PayrollController())->salarySetup();
        break;
    case 'payroll/payslip':
        (new PayrollController())->payslip();
        break;
    case 'payroll/my-payslips':
        (new PayrollController())->myPayslips();
        break;
    case 'payroll/mark-paid':
        (new PayrollController())->markPaid();
        break;

    // Performance
    case 'performance/goals':
        (new PerformanceController())->goals();
        break;
    case 'performance/reviews':
        (new PerformanceController())->reviews();
        break;

    // Resignations
    case 'resignations':
        (new ResignationController())->index();
        break;
    case 'resignations/apply':
        (new ResignationController())->apply();
        break;
    case 'resignations/update':
        (new ResignationController())->update();
        break;

    // Announcements
    case 'announcements':
        (new AnnouncementController())->index();
        break;

    // Document Management & Digital Locker
    case 'documents':
        (new DocumentController())->index();
        break;
    case 'documents/my-documents':
        (new DocumentController())->myDocuments();
        break;
    case 'documents/upload':
        (new DocumentController())->upload();
        break;
    case 'documents/verify':
        (new DocumentController())->verify();
        break;
    case 'documents/reject':
        (new DocumentController())->reject();
        break;
    case 'documents/download':
        (new DocumentController())->download();
        break;
    case 'documents/delete':
        (new DocumentController())->delete();
        break;

    // Notifications
    case 'notifications/mark-read':
        (new NotificationController())->markRead();
        break;
    case 'notifications/mark-all-read':
        (new NotificationController())->markAllRead();
        break;
    case 'notifications/unread-count':
        (new NotificationController())->unreadCount();
        break;

    default:
        http_response_code(404);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested page <code>/" . htmlspecialchars($route) . "</code> does not exist.</p>";
        echo "<p><a href='" . url('dashboard') . "' style='display:inline-block; padding:10px 20px; background:#2563eb; color:#fff; text-decoration:none; border-radius:6px;'>Return to Dashboard</a></p>";
        echo "</div>";
        break;
}
