<?php
$currentUser = Auth::user();
$currentRole = Auth::role();
$currentRoute = trim($_GET['route'] ?? '', '/');
if (empty($currentRoute)) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $currentRoute = trim(substr($uri, strlen($scriptDir)), '/');
}

require_once BASE_PATH . '/src/Models/Leave.php';
require_once BASE_PATH . '/src/Models/Attendance.php';
require_once BASE_PATH . '/src/Models/Document.php';

// Counts for badges
$pendingLeavesCount = 0;
$pendingRegsCount = 0;
if (Auth::isManager()) {
    $mId = ($currentRole === 'manager') ? Auth::employeeId() : null;
    $pReqs = Leave::getRequests($mId, null, ['status' => 'pending']);
    $pendingLeavesCount = count($pReqs);

    $pRegs = Attendance::getRegularizationRequests($mId);
    $pendingRegsCount = count(array_filter($pRegs, fn($r) => $r['status'] === 'pending'));
}

$pendingDocsCount = 0;
if (Auth::isHR()) {
    $pendingDocsCount = Document::getPendingCount();
}
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= url('dashboard') ?>" class="sidebar-brand-link" style="text-decoration: none;">
            <img src="<?= url('assets/img/logo.png') ?>" alt="Advancells Group">
        </a>
        <div class="sidebar-tagline">
            <span>HRMS ENTERPRISE</span>
            <span style="display:inline-flex; align-items:center; gap:5px; color:#10b981; font-size:10.5px; font-weight:700;">
                <span class="pulse-dot" style="width:6px; height:6px;"></span> LIVE
            </span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Core Management</div>
        <a href="<?= url('dashboard') ?>" class="nav-item <?= ($currentRoute === '' || $currentRoute === 'dashboard') ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-chart-pie"></i></span>
            <span>Dashboard</span>
        </a>

        <?php if (Auth::isManager()): ?>
        <a href="<?= url('employees') ?>" class="nav-item <?= str_starts_with($currentRoute, 'employees') ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-users"></i></span>
            <span>Employee Directory</span>
        </a>
        <a href="<?= url('org-chart') ?>" class="nav-item <?= ($currentRoute === 'org-chart' || str_starts_with($currentRoute, 'organization')) ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-network-wired"></i></span>
            <span>Org Chart</span>
        </a>
        <?php if (Auth::isHR()): ?>
        <a href="<?= url('departments') ?>" class="nav-item <?= $currentRoute === 'departments' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-sitemap"></i></span>
            <span>Departments</span>
        </a>
        <a href="<?= url('documents') ?>" class="nav-item <?= $currentRoute === 'documents' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-folder-tree"></i></span>
            <span>Document Locker</span>
            <?php if ($pendingDocsCount > 0): ?>
                <span class="badge badge-warning"><?= $pendingDocsCount ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php else: ?>
        <a href="<?= url('org-chart') ?>" class="nav-item <?= ($currentRoute === 'org-chart' || str_starts_with($currentRoute, 'organization')) ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-network-wired"></i></span>
            <span>Org Chart</span>
        </a>
        <?php endif; ?>

        <div class="nav-section-title">Time & Attendance</div>
        <a href="<?= url('attendance/punch') ?>" class="nav-item <?= $currentRoute === 'attendance/punch' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-fingerprint"></i></span>
            <span>Web Punch Console</span>
        </a>
        <a href="<?= url('attendance/my-attendance') ?>" class="nav-item <?= $currentRoute === 'attendance/my-attendance' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-calendar-check"></i></span>
            <span>My Attendance</span>
        </a>
        <a href="<?= url('attendance/regularize') ?>" class="nav-item <?= $currentRoute === 'attendance/regularize' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
            <span>Regularize Punch</span>
        </a>
        <?php if (Auth::isManager()): ?>
        <a href="<?= url('attendance/regularize-approvals') ?>" class="nav-item <?= $currentRoute === 'attendance/regularize-approvals' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-circle-check"></i></span>
            <span>Reg. Approvals</span>
            <?php if ($pendingRegsCount > 0): ?>
                <span class="badge badge-warning"><?= $pendingRegsCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= url('attendance/admin-logs') ?>" class="nav-item <?= $currentRoute === 'attendance/admin-logs' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-clipboard-list"></i></span>
            <span>Master Logs</span>
        </a>
        <?php endif; ?>

        <div class="nav-section-title">Leave & Holidays</div>
        <a href="<?= url('leaves/my-leaves') ?>" class="nav-item <?= $currentRoute === 'leaves/my-leaves' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-umbrella-beach"></i></span>
            <span>My Leaves & Apply</span>
        </a>
        <?php if (Auth::isManager()): ?>
        <a href="<?= url('leaves/approvals') ?>" class="nav-item <?= $currentRoute === 'leaves/approvals' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-clipboard-check"></i></span>
            <span>Leave Approvals</span>
            <?php if ($pendingLeavesCount > 0): ?>
                <span class="badge badge-warning"><?= $pendingLeavesCount ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <a href="<?= url('leaves/holidays') ?>" class="nav-item <?= $currentRoute === 'leaves/holidays' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-calendar-days"></i></span>
            <span>Holiday Calendar</span>
        </a>

        <div class="nav-section-title">Payroll & Finance</div>
        <?php if (Auth::isHR()): ?>
        <a href="<?= url('payroll') ?>" class="nav-item <?= ($currentRoute === 'payroll' || str_starts_with($currentRoute, 'payroll/salary-setup')) ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
            <span>Payroll Manager</span>
        </a>
        <?php endif; ?>
        <a href="<?= url('payroll/my-payslips') ?>" class="nav-item <?= $currentRoute === 'payroll/my-payslips' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-receipt"></i></span>
            <span>My Payslips</span>
        </a>

        <div class="nav-section-title">Performance & Growth</div>
        <a href="<?= url('performance/goals') ?>" class="nav-item <?= $currentRoute === 'performance/goals' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-bullseye"></i></span>
            <span>Goals & OKRs</span>
        </a>
        <a href="<?= url('performance/reviews') ?>" class="nav-item <?= $currentRoute === 'performance/reviews' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-star"></i></span>
            <span>Appraisals</span>
        </a>

        <div class="nav-section-title">Company Portal</div>
        <a href="<?= url('announcements') ?>" class="nav-item <?= $currentRoute === 'announcements' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-bullhorn"></i></span>
            <span>Notice Board</span>
        </a>
        <a href="<?= url('documents/my-documents') ?>" class="nav-item <?= $currentRoute === 'documents/my-documents' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-file-shield"></i></span>
            <span>My Documents</span>
        </a>
        <?php if (Auth::isManager()): ?>
        <a href="<?= url('resignations') ?>" class="nav-item <?= $currentRoute === 'resignations' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-door-open"></i></span>
            <span>Exit Management</span>
        </a>
        <?php else: ?>
        <a href="<?= url('resignations/apply') ?>" class="nav-item <?= $currentRoute === 'resignations/apply' ? 'active' : '' ?>">
            <span class="icon"><i class="fa-solid fa-door-open"></i></span>
            <span>Resignation</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= url('profile') ?>" class="user-card" style="text-decoration: none;">
            <div class="user-avatar-wrap">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="online-dot"></div>
            </div>
            <div class="user-details">
                <div class="name"><?= e($currentUser['name'] ?? 'User') ?></div>
                <div class="role"><?= ucwords(str_replace('_', ' ', $currentUser['role'] ?? 'Employee')) ?></div>
            </div>
        </a>
    </div>
</aside>
