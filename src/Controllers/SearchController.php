<?php
/**
 * Global Search Controller - Advancells HRMS
 * Provides fast, unified search strictly filtered by Role-Based Access Control (RBAC).
 * Items that the logged-in user is not authorized to view will NEVER appear in results.
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Models/Employee.php';

class SearchController {
    /**
     * Handle live search API request (JSON)
     */
    public function index(): void {
        Auth::requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        $query = trim($_GET['q'] ?? '');
        $userId = (int)Auth::id();
        $empId = Auth::employeeId() ? (int)Auth::employeeId() : null;
        $role = (string)Auth::role();
        $isHR = Auth::isHR();
        $isManager = ($role === 'manager');

        // 1. Navigation / Quick Actions (Contextual strictly by role)
        $navigationMatches = $this->getNavigationActions($query, $role);

        // If query is empty, return Quick Actions / Navigation suggestions
        if (mb_strlen($query) < 2) {
            echo json_encode([
                'success' => true,
                'query' => $query,
                'is_initial' => true,
                'results' => [
                    'navigation' => [
                        'title' => '⚡ Quick Actions & Navigation',
                        'items' => array_slice($navigationMatches, 0, 8)
                    ]
                ],
                'total' => count(array_slice($navigationMatches, 0, 8))
            ]);
            exit;
        }

        $results = [];
        $totalMatches = 0;

        // Add matching navigation items if any
        if (!empty($navigationMatches)) {
            $results['navigation'] = [
                'title' => '⚡ Quick Actions & Pages',
                'items' => array_slice($navigationMatches, 0, 5)
            ];
            $totalMatches += count($results['navigation']['items']);
        }

        // 2. Search Employees (Strictly filtered by viewing permissions)
        $employeeResults = $this->searchEmployees($query, $isHR, $isManager, $empId, $role);
        if (!empty($employeeResults)) {
            $results['employees'] = [
                'title' => '👥 Employees (' . count($employeeResults) . ')',
                'items' => $employeeResults
            ];
            $totalMatches += count($employeeResults);
        }

        // 3. Search Leaves (Strictly filtered by viewing permissions)
        $leaveResults = $this->searchLeaves($query, $isHR, $isManager, $empId);
        if (!empty($leaveResults)) {
            $results['leaves'] = [
                'title' => '🌴 Leaves & Time-Off (' . count($leaveResults) . ')',
                'items' => $leaveResults
            ];
            $totalMatches += count($leaveResults);
        }

        // 4. Search Payroll & Payslips (Strictly filtered by viewing permissions)
        $payrollResults = $this->searchPayrolls($query, $isHR, $empId);
        if (!empty($payrollResults)) {
            $results['payrolls'] = [
                'title' => '💳 Payroll & Payslips (' . count($payrollResults) . ')',
                'items' => $payrollResults
            ];
            $totalMatches += count($payrollResults);
        }

        // 5. Search Company Assets & Hardware (Strictly filtered by viewing permissions)
        $assetResults = $this->searchAssets($query, $isHR, $isManager, $empId, $role);
        if (!empty($assetResults)) {
            $results['assets'] = [
                'title' => '💻 Assets & Hardware (' . count($assetResults) . ')',
                'items' => $assetResults
            ];
            $totalMatches += count($assetResults);
        }

        // 6. Search Documents (Strictly filtered by viewing permissions)
        $documentResults = $this->searchDocuments($query, $isHR, $isManager, $empId, $role);
        if (!empty($documentResults)) {
            $results['documents'] = [
                'title' => '📄 Documents & Locker (' . count($documentResults) . ')',
                'items' => $documentResults
            ];
            $totalMatches += count($documentResults);
        }

        echo json_encode([
            'success' => true,
            'query' => $query,
            'is_initial' => false,
            'results' => $results,
            'total' => $totalMatches
        ]);
        exit;
    }

    /**
     * Match navigation items and system shortcuts strictly accessible to user's role
     */
    private function getNavigationActions(string $query, string $role): array {
        $actions = [
            // General actions accessible to all authenticated users
            [
                'title' => 'Punch In / Out',
                'subtitle' => 'Live Attendance Clock & Web Punching',
                'keywords' => 'punch in out attendance clock biometric time present mark',
                'url' => url('attendance/punch'),
                'icon' => 'fa-fingerprint',
                'color' => '#0d9488',
                'badge' => 'Self Service',
                'roles' => ['all']
            ],
            [
                'title' => 'Apply for Leave',
                'subtitle' => 'Submit casual, sick, or earned leave application',
                'keywords' => 'apply leave timeoff vacation holiday request sick cl sl el',
                'url' => url('leaves/apply'),
                'icon' => 'fa-plane-departure',
                'color' => '#93206c',
                'badge' => 'Leave',
                'roles' => ['all']
            ],
            [
                'title' => 'My Leave Applications & Balances',
                'subtitle' => 'View remaining leave quotas & application history',
                'keywords' => 'my leaves balance quota status timeoff history',
                'url' => url('leaves/my-leaves'),
                'icon' => 'fa-calendar-check',
                'color' => '#93206c',
                'badge' => 'Leave',
                'roles' => ['all']
            ],
            [
                'title' => 'My Payslips & Compensation',
                'subtitle' => 'Download official monthly payslips & tax breakdowns',
                'keywords' => 'payslip salary payroll payslips payment earnings my payslips slip',
                'url' => url('payroll/my-payslips'),
                'icon' => 'fa-receipt',
                'color' => '#059669',
                'badge' => 'Payroll',
                'roles' => ['all']
            ],
            [
                'title' => 'My Attendance History',
                'subtitle' => 'Review monthly attendance logs, timings & hours',
                'keywords' => 'my attendance logs sheet monthly hours present late',
                'url' => url('attendance/my-attendance'),
                'icon' => 'fa-clock-rotate-left',
                'color' => '#2563eb',
                'badge' => 'Attendance',
                'roles' => ['all']
            ],
            [
                'title' => 'Organization Chart & Hierarchy',
                'subtitle' => 'Interactive company reporting tree & team structures',
                'keywords' => 'org chart organization hierarchy reporting manager tree structure team',
                'url' => url('org-chart'),
                'icon' => 'fa-sitemap',
                'color' => '#7c3aed',
                'badge' => 'Company',
                'roles' => ['all']
            ],
            [
                'title' => 'Holiday Calendar 2026',
                'subtitle' => 'List of mandatory & optional public holidays',
                'keywords' => 'holiday holidays calendar off days festival diwali new year',
                'url' => url('leaves/holidays'),
                'icon' => 'fa-umbrella-beach',
                'color' => '#d97706',
                'badge' => 'Company',
                'roles' => ['all']
            ],
            [
                'title' => 'Company Announcements',
                'subtitle' => 'Official circulars, events & team bulletins',
                'keywords' => 'announcements notice bulletin circular news updates company',
                'url' => url('announcements'),
                'icon' => 'fa-bullhorn',
                'color' => '#ea580c',
                'badge' => 'Bulletin',
                'roles' => ['all']
            ],
            [
                'title' => 'Digital Document Locker',
                'subtitle' => 'Uploaded KYC, degrees, offer letters & NDA forms',
                'keywords' => 'documents locker kyc aadhaar pan passport degrees upload my documents',
                'url' => url('documents/my-documents'),
                'icon' => 'fa-folder-closed',
                'color' => '#475569',
                'badge' => 'Documents',
                'roles' => ['all']
            ],
            [
                'title' => 'My Allocated Assets',
                'subtitle' => 'Hardware, laptops and peripherals assigned to you',
                'keywords' => 'my assets laptop desktop device hardware allocation property',
                'url' => url('my-assets'),
                'icon' => 'fa-laptop-code',
                'color' => '#2563eb',
                'badge' => 'Hardware',
                'roles' => ['all']
            ],
            [
                'title' => 'My Profile & Security Settings',
                'subtitle' => 'Change password, view employment profile & contact info',
                'keywords' => 'profile account password security settings avatar personal',
                'url' => url('profile'),
                'icon' => 'fa-user-shield',
                'color' => '#334155',
                'badge' => 'Settings',
                'roles' => ['all']
            ],

            // Manager & HR Actions
            [
                'title' => 'Pending Leave Approvals',
                'subtitle' => 'Review and action pending team leave applications',
                'keywords' => 'leave approvals pending review approve reject manager hr',
                'url' => url('leaves/approvals'),
                'icon' => 'fa-clipboard-check',
                'color' => '#93206c',
                'badge' => 'Approvals',
                'roles' => ['super_admin', 'hr_admin', 'manager']
            ],
            [
                'title' => 'Attendance Regularization Approvals',
                'subtitle' => 'Review punch-in / punch-out correction requests',
                'keywords' => 'regularize regularizations approvals attendance missed punch',
                'url' => url('attendance/regularize-approvals'),
                'icon' => 'fa-user-clock',
                'color' => '#d97706',
                'badge' => 'Approvals',
                'roles' => ['super_admin', 'hr_admin', 'manager']
            ],
            [
                'title' => 'Employee Directory',
                'subtitle' => 'View team and company workforce',
                'keywords' => 'employees employee directory staff manage list',
                'url' => url('employees'),
                'icon' => 'fa-users',
                'color' => '#2563eb',
                'badge' => 'Management',
                'roles' => ['super_admin', 'hr_admin', 'manager']
            ],
            [
                'title' => 'Company Asset Inventory',
                'subtitle' => 'Master inventory of laptops, desktops & lab equipment',
                'keywords' => 'assets company inventory hardware allocate return devices',
                'url' => url('company-assets'),
                'icon' => 'fa-boxes-stacked',
                'color' => '#7c3aed',
                'badge' => 'Assets',
                'roles' => ['super_admin', 'hr_admin']
            ],
            [
                'title' => 'Document Verification Queue',
                'subtitle' => 'Audit employee uploaded credentials, degrees & KYC',
                'keywords' => 'documents verify verification queue audit approve reject',
                'url' => url('documents'),
                'icon' => 'fa-shield-halved',
                'color' => '#93206c',
                'badge' => 'Compliance',
                'roles' => ['super_admin', 'hr_admin']
            ],

            // HR / Super Admin ONLY Actions
            [
                'title' => 'Add New Employee',
                'subtitle' => 'Onboard new hire with employee code & user account',
                'keywords' => 'add new employee onboard hire create register',
                'url' => url('employees/create'),
                'icon' => 'fa-user-plus',
                'color' => '#059669',
                'badge' => 'HR Admin',
                'roles' => ['super_admin', 'hr_admin']
            ],
            [
                'title' => 'Process Monthly Payroll',
                'subtitle' => 'Calculate salaries, LOP deductions & generate payslips',
                'keywords' => 'payroll process run calculate salary payslips pay sheet',
                'url' => url('payroll'),
                'icon' => 'fa-money-bill-wave',
                'color' => '#059669',
                'badge' => 'HR Admin',
                'roles' => ['super_admin', 'hr_admin']
            ],
            [
                'title' => 'Salary Structure Configuration',
                'subtitle' => 'Configure basic, HRA, allowances, PF, and tax rules',
                'keywords' => 'salary setup structure basic hra allowances ctc deductions',
                'url' => url('payroll/salary-setup'),
                'icon' => 'fa-sliders',
                'color' => '#0891b2',
                'badge' => 'HR Admin',
                'roles' => ['super_admin', 'hr_admin']
            ],
            [
                'title' => 'Master Attendance Sheet',
                'subtitle' => 'Company-wide monthly grid with present, leave & LOP totals',
                'keywords' => 'attendance sheet monthly grid master admin logs export',
                'url' => url('attendance/sheet'),
                'icon' => 'fa-table-cells',
                'color' => '#2563eb',
                'badge' => 'HR Admin',
                'roles' => ['super_admin', 'hr_admin']
            ],
            [
                'title' => 'Departments & Designations',
                'subtitle' => 'Manage corporate business units & job titles',
                'keywords' => 'departments designations org business units roles titles',
                'url' => url('departments'),
                'icon' => 'fa-building-user',
                'color' => '#334155',
                'badge' => 'HR Admin',
                'roles' => ['super_admin', 'hr_admin']
            ]
        ];

        $matched = [];
        $queryLower = mb_strtolower($query);
        $terms = array_filter(explode(' ', $queryLower));

        foreach ($actions as $act) {
            // Strict role check: if user does not possess role, NEVER show
            if (!in_array('all', $act['roles'], true) && !in_array($role, $act['roles'], true)) {
                continue;
            }

            if (empty($query)) {
                $matched[] = $act;
                continue;
            }

            $haystack = mb_strtolower($act['title'] . ' ' . $act['subtitle'] . ' ' . $act['keywords'] . ' ' . $act['badge']);
            $score = 0;
            foreach ($terms as $term) {
                if (str_contains($haystack, $term)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $act['score'] = $score;
                $matched[] = $act;
            }
        }

        if (!empty($query)) {
            usort($matched, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        }

        return $matched;
    }

    /**
     * Search employee records
     * RBAC Policy:
     * - Super Admin & HR Admin: can search all employees.
     * - Manager: can ONLY search themselves and their direct & indirect reportees.
     * - Regular Employee: can ONLY search their own profile (cannot view other employees' profiles).
     */
    private function searchEmployees(string $query, bool $isHR, bool $isManager, ?int $empId, string $role): array {
        $like = '%' . $query . '%';

        $sql = "SELECT e.id, e.emp_code, e.first_name, e.last_name, e.email, e.phone,
                       e.status, d.name AS department_name, des.title AS designation_title,
                       u.avatar, u.role
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE (e.first_name LIKE ? OR e.last_name LIKE ? 
                       OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?
                       OR e.emp_code LIKE ? OR e.email LIKE ? OR e.phone LIKE ?
                       OR d.name LIKE ? OR des.title LIKE ?)";

        $params = [$like, $like, $like, $like, $like, $like, $like, $like];

        if ($isHR) {
            // Super Admin & HR Admin can search all employees
        } elseif ($isManager && $empId) {
            // Manager can ONLY view themselves and their assigned subordinate team members
            $subordinateIds = Employee::getSubordinateIds((int)$empId, true);
            if (!empty($subordinateIds)) {
                $placeholders = implode(',', array_fill(0, count($subordinateIds), '?'));
                $sql .= " AND e.id IN ({$placeholders}) AND e.status = 'active'";
                $params = array_merge($params, $subordinateIds);
            } else {
                return [];
            }
        } elseif ($empId) {
            // Regular employee can ONLY view their own profile
            $sql .= " AND e.id = ? AND e.status = 'active'";
            $params[] = (int)$empId;
        } else {
            return [];
        }

        $sql .= " ORDER BY (CONCAT(e.first_name, ' ', e.last_name) LIKE ?) DESC, e.first_name ASC LIMIT 5";
        $params[] = $query . '%';

        $rows = Database::fetchAll($sql, $params);
        $items = [];

        foreach ($rows as $r) {
            $fullName = trim($r['first_name'] . ' ' . $r['last_name']);
            $initials = strtoupper(substr($r['first_name'] ?? 'U', 0, 1) . substr($r['last_name'] ?? '', 0, 1));
            
            // Generate valid accessible target URL
            if ($empId && (int)$r['id'] === (int)$empId) {
                $targetUrl = url('profile');
            } else {
                $targetUrl = url('employees/view?id=' . $r['id']);
            }

            $items[] = [
                'title' => $fullName,
                'subtitle' => ($r['emp_code'] ? $r['emp_code'] . ' • ' : '') . ($r['designation_title'] ?: 'Staff') . ' (' . ($r['department_name'] ?: 'Advancells') . ')',
                'url' => $targetUrl,
                'avatar' => $r['avatar'] ? url($r['avatar']) : null,
                'initials' => $initials,
                'icon' => 'fa-user',
                'color' => '#2563eb',
                'badge' => ucfirst($r['status']),
                'badge_class' => $r['status'] === 'active' ? 'badge-success' : 'badge-secondary'
            ];
        }

        return $items;
    }

    /**
     * Search leave applications & history
     * RBAC Policy:
     * - Super Admin & HR Admin: can search all leave applications across the company.
     * - Manager: can ONLY search leave applications of themselves and their direct/indirect reportees.
     * - Regular Employee: can ONLY search their own leave applications.
     */
    private function searchLeaves(string $query, bool $isHR, bool $isManager, ?int $empId): array {
        $like = '%' . $query . '%';

        $sql = "SELECT lr.id, lr.employee_id, lr.from_date, lr.to_date, lr.total_days, lr.status, lr.reason,
                       lt.name AS leave_type_name, lt.code AS leave_type_code,
                       e.emp_code, e.first_name, e.last_name
                FROM leave_requests lr
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                JOIN employees e ON lr.employee_id = e.id
                WHERE (lt.name LIKE ? OR lr.reason LIKE ? OR lr.status LIKE ?
                       OR e.first_name LIKE ? OR e.last_name LIKE ?
                       OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?
                       OR e.emp_code LIKE ?)";

        $params = [$like, $like, $like, $like, $like, $like, $like];

        if ($isHR) {
            // HR / Super Admin can see all leaves
        } elseif ($isManager && $empId) {
            // Manager can ONLY see leave requests from themselves or their direct/indirect subordinates
            $subordinateIds = Employee::getSubordinateIds((int)$empId, true);
            if (!empty($subordinateIds)) {
                $placeholders = implode(',', array_fill(0, count($subordinateIds), '?'));
                $sql .= " AND (lr.employee_id IN ({$placeholders}) OR lr.manager_id = ? OR e.manager_id = ?)";
                $params = array_merge($params, $subordinateIds, [(int)$empId, (int)$empId]);
            } else {
                $sql .= " AND lr.employee_id = ?";
                $params[] = (int)$empId;
            }
        } elseif ($empId) {
            // Regular employee only sees their own leave requests
            $sql .= " AND lr.employee_id = ?";
            $params[] = (int)$empId;
        } else {
            return [];
        }

        $sql .= " ORDER BY lr.created_at DESC LIMIT 5";
        $rows = Database::fetchAll($sql, $params);
        $items = [];

        foreach ($rows as $r) {
            $empName = trim($r['first_name'] . ' ' . $r['last_name']);
            $dates = date('d M', strtotime($r['from_date'])) . ($r['from_date'] !== $r['to_date'] ? ' - ' . date('d M Y', strtotime($r['to_date'])) : ' ' . date('Y', strtotime($r['from_date'])));
            
            // If own leave -> my-leaves; else -> approvals
            if ($empId && (int)$r['employee_id'] === (int)$empId) {
                $targetUrl = url('leaves/my-leaves');
            } else {
                $targetUrl = url('leaves/approvals');
            }

            $statusClasses = [
                'approved' => 'badge-success',
                'pending' => 'badge-warning',
                'rejected' => 'badge-danger',
                'cancelled' => 'badge-secondary'
            ];

            $items[] = [
                'title' => $r['leave_type_name'] . ' (' . $r['total_days'] . 'd) — ' . $empName,
                'subtitle' => $dates . ($r['reason'] ? ' • "' . (mb_strlen($r['reason']) > 38 ? mb_substr($r['reason'], 0, 35) . '...' : $r['reason']) . '"' : ''),
                'url' => $targetUrl,
                'icon' => 'fa-calendar-day',
                'color' => '#93206c',
                'badge' => ucfirst($r['status']),
                'badge_class' => $statusClasses[$r['status']] ?? 'badge-secondary'
            ];
        }

        return $items;
    }

    /**
     * Search payroll payslips
     * RBAC Policy:
     * - Super Admin & HR Admin: can search and view all payslips across the organization.
     * - Managers & Regular Employees: can ONLY search and view their own individual payslips.
     * (PayrollController forbids any non-HR user from viewing another person's payslip)
     */
    private function searchPayrolls(string $query, bool $isHR, ?int $empId): array {
        $like = '%' . $query . '%';

        // Check if query is a month name
        $monthNum = null;
        for ($m = 1; $m <= 12; $m++) {
            $monthName = date('F', mktime(0, 0, 0, $m, 1));
            $shortName = date('M', mktime(0, 0, 0, $m, 1));
            if (stripos($monthName, $query) !== false || stripos($shortName, $query) !== false) {
                $monthNum = $m;
                break;
            }
        }

        $sql = "SELECT p.id, p.employee_id, p.month, p.year, p.net_salary, p.payment_status,
                       p.payslip_number, e.emp_code, e.first_name, e.last_name
                FROM payrolls p
                JOIN employees e ON p.employee_id = e.id
                WHERE (p.payslip_number LIKE ? OR p.year LIKE ?
                       OR e.first_name LIKE ? OR e.last_name LIKE ?
                       OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?
                       OR e.emp_code LIKE ?";

        $params = [$like, $like, $like, $like, $like, $like];

        if ($monthNum !== null) {
            $sql .= " OR p.month = ?";
            $params[] = $monthNum;
        }
        $sql .= ")";

        // Non-HR users (including Managers) can ONLY view their own payslips
        if (!$isHR) {
            if (!$empId) {
                return [];
            }
            $sql .= " AND p.employee_id = ?";
            $params[] = (int)$empId;
        }

        $sql .= " ORDER BY p.year DESC, p.month DESC LIMIT 5";
        $rows = Database::fetchAll($sql, $params);
        $items = [];

        foreach ($rows as $r) {
            $empName = trim($r['first_name'] . ' ' . $r['last_name']);
            $monthName = date('F Y', mktime(0, 0, 0, $r['month'], 1, $r['year']));
            $targetUrl = url('payroll/payslip?id=' . $r['id']);

            $items[] = [
                'title' => 'Payslip: ' . $monthName . ' — ' . $empName,
                'subtitle' => ($r['payslip_number'] ? $r['payslip_number'] . ' • ' : '') . 'Net: ' . CURRENCY_SYMBOL . number_format($r['net_salary'], 2),
                'url' => $targetUrl,
                'icon' => 'fa-file-invoice-dollar',
                'color' => '#059669',
                'badge' => ucfirst($r['payment_status']),
                'badge_class' => $r['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning'
            ];
        }

        return $items;
    }

    /**
     * Search company assets
     * RBAC Policy:
     * - Super Admin, HR Admin & Managers: can search company-wide asset inventory.
     * - Regular Employees: can ONLY search hardware assets currently allocated to themselves.
     */
    private function searchAssets(string $query, bool $isHR, bool $isManager, ?int $empId, string $role): array {
        $like = '%' . $query . '%';

        $sql = "SELECT a.id, a.asset_code, a.name, a.category, a.brand, a.model, a.serial_number,
                       a.status, a.current_employee_id,
                       e.first_name, e.last_name, e.emp_code
                FROM assets a
                LEFT JOIN employees e ON a.current_employee_id = e.id
                WHERE (a.asset_code LIKE ? OR a.name LIKE ? OR a.brand LIKE ? OR a.model LIKE ?
                       OR a.serial_number LIKE ? OR a.category LIKE ?
                       OR e.first_name LIKE ? OR e.last_name LIKE ?)";

        $params = [$like, $like, $like, $like, $like, $like, $like, $like];

        // Only HR Admin can search the master company inventory. Managers and employees can only see their own assigned assets
        if (!$isHR) {
            if (!$empId) {
                return [];
            }
            $sql .= " AND a.current_employee_id = ?";
            $params[] = (int)$empId;
        }

        $sql .= " ORDER BY a.id DESC LIMIT 5";
        $rows = Database::fetchAll($sql, $params);
        $items = [];

        $categoryIcons = [
            'laptop' => 'fa-laptop',
            'desktop' => 'fa-desktop',
            'mobile_tablet' => 'fa-tablet-screen-button',
            'monitor' => 'fa-tv',
            'lab_equipment' => 'fa-flask-vial',
            'peripheral' => 'fa-keyboard',
            'access_card' => 'fa-id-badge',
            'other' => 'fa-box-archive'
        ];

        foreach ($rows as $r) {
            $assignedTo = (!empty($r['first_name'])) ? trim($r['first_name'] . ' ' . $r['last_name']) : 'Available';
            $targetUrl = $isHR ? url('company-assets') : url('my-assets');

            $items[] = [
                'title' => $r['name'] . ' (' . $r['asset_code'] . ')',
                'subtitle' => ($r['brand'] ? $r['brand'] . ' ' . $r['model'] . ' • ' : '') . 'Assigned to: ' . $assignedTo,
                'url' => $targetUrl,
                'icon' => $categoryIcons[$r['category']] ?? 'fa-laptop',
                'color' => '#7c3aed',
                'badge' => ucfirst($r['status']),
                'badge_class' => $r['status'] === 'allocated' ? 'badge-primary' : ($r['status'] === 'available' ? 'badge-success' : 'badge-secondary')
            ];
        }

        return $items;
    }

    /**
     * Search uploaded documents
     * RBAC Policy:
     * - Super Admin & HR Admin: can search company-wide compliance documents matrix (route: documents).
     * - Managers & Regular Employees: can ONLY search documents uploaded for their own profile (route: documents/my-documents).
     */
    private function searchDocuments(string $query, bool $isHR, bool $isManager, ?int $empId, string $role): array {
        $like = '%' . $query . '%';

        $sql = "SELECT d.id, d.employee_id, d.title, d.document_type, d.file_name, d.status,
                       e.first_name, e.last_name, e.emp_code
                FROM employee_documents d
                JOIN employees e ON d.employee_id = e.id
                WHERE (d.title LIKE ? OR d.document_type LIKE ? OR d.file_name LIKE ?
                       OR e.first_name LIKE ? OR e.last_name LIKE ?)";

        $params = [$like, $like, $like, $like, $like];

        // Non-HR users (including Managers) can only search their own documents
        if (!$isHR) {
            if (!$empId) {
                return [];
            }
            $sql .= " AND d.employee_id = ?";
            $params[] = (int)$empId;
        }

        $sql .= " ORDER BY d.id DESC LIMIT 5";
        $rows = Database::fetchAll($sql, $params);
        $items = [];

        foreach ($rows as $r) {
            $empName = trim($r['first_name'] . ' ' . $r['last_name']);
            $targetUrl = $isHR ? url('documents') : url('documents/my-documents');

            $items[] = [
                'title' => $r['title'] . ' — ' . $empName,
                'subtitle' => ucwords(str_replace('_', ' ', $r['document_type'])) . ' • ' . $r['file_name'],
                'url' => $targetUrl,
                'icon' => 'fa-file-lines',
                'color' => '#475569',
                'badge' => ucfirst($r['status']),
                'badge_class' => $r['status'] === 'verified' ? 'badge-success' : ($r['status'] === 'rejected' ? 'badge-danger' : 'badge-warning')
            ];
        }

        return $items;
    }
}
