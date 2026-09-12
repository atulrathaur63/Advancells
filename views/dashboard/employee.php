<?php
$pageTitle = 'Employee Self-Service (ESS)';
require_once BASE_PATH . '/views/layouts/header.php';

$totalAvailLeaves = (int)array_sum(array_column(array_filter($leaveBalances, fn($b) => $b['leave_type_code'] !== 'LOP'), 'available'));
$currentHour = (int)date('H');
$greetingText = ($currentHour < 12) ? 'Good morning' : (($currentHour < 17) ? 'Good afternoon' : 'Good evening');
?>

<?php if (!empty($myCelebration)): ?>
    <div class="celebrant-hero-card">
        <div class="celebrant-hero-icon-wrap">
            <i class="fa-solid <?= $myCelebration['type_icon'] ?>"></i>
        </div>
        <div class="celebrant-hero-body">
            <div class="celebrant-hero-meta">
                <span class="badge-celebrant">
                    <i class="fa-solid <?= $myCelebration['type_icon'] ?>"></i> Special Day
                </span>
                <span class="celebrant-date-pill"><?= date('d M Y') ?></span>
            </div>
            <h3 class="celebrant-hero-title">
                Happy <?= $myCelebration['type_label'] ?>, <?= e($user['first_name'] ?? $user['name']) ?>!
            </h3>
            <p class="celebrant-hero-desc">
                The entire Advancells family celebrates your <strong><?= e($myCelebration['milestone_text']) ?></strong>.
                <?php if ($myCelebration['wishes_count'] > 0): ?>
                    You have received <strong><?= $myCelebration['wishes_count'] ?> greeting(s)</strong> from your teammates!
                <?php else: ?>
                    Wishing you wonderful health, happiness, and great success!
                <?php endif; ?>
            </p>
        </div>
        <div class="celebrant-hero-action">
            <button type="button" class="btn-read-greetings" 
                    onclick="openViewWishesModal(<?= (int)$empId ?>, '<?= e($user['first_name'] ?? $user['name']) ?>', '<?= $myCelebration['type'] ?>')">
                <i class="fa-regular fa-envelope-open"></i>
                <span>Read Greetings (<?= $myCelebration['wishes_count'] ?>)</span>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Section 1: Workplace Hub & Daily Attendance -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-id-badge"></i>
        <span>Workplace Hub & Daily Attendance</span>
    </div>
    <span class="dash-section-badge">ESS Terminal</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 20px;">
    <!-- Welcome Executive Card -->
    <div class="card" style="border-left: 4px solid var(--brand-plum); background: radial-gradient(at top left, rgba(164, 36, 122, 0.05), transparent 70%), radial-gradient(at bottom right, rgba(90, 168, 159, 0.06), transparent 70%), #ffffff;">
        <div class="card-body" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge badge-magenta">
                            <i class="fa-solid fa-id-badge" style="margin-right: 4px;"></i> Advancells ESS Portal
                        </span>
                        <span style="font-family: monospace; font-size: 11px; color: var(--text-muted); font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                            <?= e($user['emp_code'] ?? 'ADV-EMP') ?>
                        </span>
                    </div>
                    <span style="font-size: 11px; font-weight: 600; color: var(--text-muted);">
                        <i class="fa-regular fa-calendar" style="margin-right: 4px; color: var(--brand-plum);"></i> <?= date('l, d M Y') ?>
                    </span>
                </div>
                <h2 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 6px; letter-spacing: -0.01em;">
                    <?= $greetingText ?>, <?= e($user['first_name'] ?? $user['name']) ?>!
                </h2>
                <p style="color: var(--text-muted); font-size: 13px; line-height: 1.5; margin-bottom: 22px;">
                    Department: <strong style="color: var(--text-main);"><?= e($user['department_name'] ?? 'Advancells Biotech') ?></strong> • 
                    Role: <strong style="color: var(--text-main);"><?= e($user['designation_title'] ?? 'Specialist') ?></strong>
                </p>
            </div>
            
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                <button type="button" class="btn btn-sm btn-primary" onclick="openQuickApplyLeaveModal()">
                    <i class="fa-solid fa-plus"></i> Apply Leave
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="openQuickRegularizeModal()">
                    <i class="fa-solid fa-bolt"></i> Regularize Punch
                </button>
                <a href="<?= url('attendance/my-attendance') ?>" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-calendar-days"></i> Monthly Timesheet
                </a>
                <a href="<?= url('payroll/my-payslips') ?>" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-receipt"></i> My Payslips
                </a>
            </div>
        </div>
    </div>

    <!-- Interactive Punch Clock Terminal Card -->
    <div class="punch-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #cbd5e1;">
                <i class="fa-solid fa-fingerprint" style="margin-right: 6px; color: var(--brand-teal);"></i> Real-Time Punch Terminal
            </div>
            <div class="date-live" style="font-size: 12.5px; color: var(--brand-cyan); font-weight: 700;">Today</div>
        </div>

        <div style="text-align: center; padding: 12px 0;">
            <div class="punch-timer clock-live">--:--:--</div>
        </div>

        <form action="<?= url('attendance/punch') ?>" method="POST" style="margin-top: auto;">
            <?= csrf_field() ?>

            <?php if (!$myTodayAtt || empty($myTodayAtt['punch_in'])): ?>
                <input type="hidden" name="punch_type" value="in">
                <div style="margin-bottom: 10px;">
                    <input type="text" name="notes" placeholder="Optional punch-in note..." class="form-control" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff;">
                </div>
                <button type="submit" class="btn btn-teal btn-lg" style="width: 100%;">
                    <i class="fa-solid fa-play"></i> Clock In (Start Work Shift)
                </button>
            <?php elseif (empty($myTodayAtt['punch_out'])): ?>
                <input type="hidden" name="punch_type" value="out">
                <div style="background: rgba(255,255,255,0.1); padding: 10px 14px; border-radius: var(--radius-md); margin-bottom: 12px; font-size: 13px;">
                    <i class="fa-solid fa-circle-check" style="color: var(--brand-teal); margin-right: 4px;"></i> Punched in at <strong><?= format_time($myTodayAtt['punch_in']) ?></strong> (<?= ucfirst($myTodayAtt['status']) ?>)
                </div>
                <div style="margin-bottom: 10px;">
                    <input type="text" name="notes" placeholder="Optional punch-out note..." class="form-control" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff;">
                </div>
                <button type="submit" class="btn btn-danger btn-lg" style="width: 100%;">
                    <i class="fa-solid fa-stop"></i> Clock Out (End Work Shift)
                </button>
            <?php else: ?>
                <div style="background: rgba(255,255,255,0.1); padding: 12px 14px; border-radius: var(--radius-md); font-size: 13px; text-align: center;">
                    <i class="fa-solid fa-circle-check" style="color: var(--brand-teal); font-size: 18px; margin-bottom: 4px;"></i><br>
                    Completed shift: In <strong><?= format_time($myTodayAtt['punch_in']) ?></strong> • Out <strong><?= format_time($myTodayAtt['punch_out']) ?></strong><br>
                    Total Hours Worked: <strong><?= $myTodayAtt['total_hours'] ?> hrs</strong>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Section 2: Attendance & Leave Analytics -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-chart-line"></i>
        <span>Monthly Attendance & Leave Analytics</span>
    </div>
    <span class="dash-section-badge"><?= date('F Y') ?></span>
</div>

<!-- Attendance Monthly Stats for Employee -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Days Present</span>
            <span class="stat-card-icon teal"><i class="fa-solid fa-calendar-check"></i></span>
        </div>
        <div class="stat-card-value"><?= $attSummary['present'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-up" style="color: var(--brand-teal);"><i class="fa-solid fa-check"></i> On Duty</span>
            <span>This calendar month</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Late Arrivals</span>
            <span class="stat-card-icon amber"><i class="fa-solid fa-clock"></i></span>
        </div>
        <div class="stat-card-value"><?= $attSummary['late'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-down" style="color: <?= $attSummary['late'] > 0 ? '#d97706' : '#059669' ?>;">
                <i class="fa-solid <?= $attSummary['late'] > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
                <?= $attSummary['late'] > 0 ? 'Late check-in' : 'Punctual' ?>
            </span>
            <span>After 09:15 AM</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Leaves Taken</span>
            <span class="stat-card-icon magenta"><i class="fa-solid fa-umbrella-beach"></i></span>
        </div>
        <div class="stat-card-value"><?= $attSummary['leave'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-calendar-minus"></i> Days</span>
            <span>Approved leaves</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Total Logged Hours</span>
            <span class="stat-card-icon cyan"><i class="fa-solid fa-business-time"></i></span>
        </div>
        <div class="stat-card-value"><?= $attSummary['total_hours'] ?>h</div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-hourglass-half"></i> Cumulative</span>
            <span>Monthly shift total</span>
        </div>
    </div>
</div>

<!-- ESS Visual Charts: Weekly Hours Graph & Leave Balances Donut Chart -->
<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Weekly Hours Graph Chart -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-simple" style="color: var(--primary);"></i>
                    Last 7 Days Working Hours
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Daily productivity & shift duration
                </div>
            </div>
            <span class="badge badge-info">Hours Trend</span>
        </div>
        <div class="card-body" style="padding: 20px 22px;">
            <div style="height: 210px; width: 100%;">
                <canvas id="myHoursChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Leave Balances Donut Chart -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: var(--brand-cyan);"></i>
                    Annual Leave Quotas (<?= date('Y') ?>)
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Available balances by policy category
                </div>
            </div>
            <span class="badge badge-magenta">Entitlements</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
            <div class="donut-chart-wrapper" style="max-width: 220px; width: 100%; height: 180px;">
                <canvas id="myLeaveDonutChart"></canvas>
                <div class="donut-center-metric">
                    <span class="metric-number"><?= $totalAvailLeaves ?></span>
                    <span class="metric-label">Days Left</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 14px; font-size: 11.5px; flex-wrap: wrap; justify-content: center;">
                <?php foreach ($leaveBalances as $b): ?>
                    <?php if ($b['leave_type_code'] !== 'LOP'): ?>
                        <span class="badge badge-secondary" style="font-weight: 600;">
                            <strong><?= e($b['leave_type_code']) ?>:</strong> <?= $b['available'] ?> left
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Applications & Compensation -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-clipboard-check"></i>
        <span>My Applications & Compensation</span>
    </div>
    <span class="dash-section-badge">ESS Records</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- My Recent Leave Applications -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-clipboard-list" style="color: var(--primary);"></i>
                My Recent Leave Applications
            </h3>
            <a href="<?= url('leaves/my-leaves') ?>" class="btn btn-sm btn-secondary">Apply Leave</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($myRecentLeaves)): ?>
                <div style="padding: 32px 20px; text-align: center; color: var(--text-muted);">
                    <i class="fa-regular fa-folder-open" style="font-size: 26px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    No leave requests submitted yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Dates</th>
                                <th>Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRecentLeaves as $rl): ?>
                                <tr>
                                    <td><strong><?= e($rl['leave_type_code']) ?></strong></td>
                                    <td><?= format_date($rl['from_date'], 'd M') ?> - <?= format_date($rl['to_date'], 'd M') ?></td>
                                    <td><strong><?= $rl['total_days'] ?></strong></td>
                                    <td><?= status_badge($rl['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Latest Payslip / Compensation Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-file-invoice-dollar" style="color: #10b981;"></i>
                Latest Salary Statement
            </h3>
            <a href="<?= url('payroll/my-payslips') ?>" class="btn btn-sm btn-secondary">All Slips</a>
        </div>
        <div class="card-body" style="padding: 18px 20px;">
            <?php if ($latestPayroll): ?>
                <div class="ess-salary-hub">
                    <div class="ess-salary-box">
                        <div class="ess-salary-month">
                            <?= date('F Y', mktime(0, 0, 0, $latestPayroll['month'], 1, $latestPayroll['year'])) ?> Payroll
                        </div>
                        <div class="ess-salary-figure">
                            <?= format_currency($latestPayroll['net_salary']) ?>
                        </div>
                        <div class="ess-salary-sub">
                            <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
                            <span>Net Disbursed Take-Home</span>
                            <span>•</span>
                            <?= status_badge($latestPayroll['payment_status']) ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: auto;">
                        <a href="<?= url('payroll/payslip?id=' . $latestPayroll['id']) ?>" class="btn btn-sm btn-success" style="flex: 1; justify-content: center;">
                            <i class="fa-solid fa-print"></i> View / Print Statement
                        </a>
                        <a href="<?= url('payroll/my-payslips') ?>" class="btn btn-sm btn-secondary">
                            History
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding: 32px 20px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-receipt" style="font-size: 26px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    No generated salary statement found yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Section 4: Workplace Community & Culture -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-cake-candles"></i>
        <span>Workplace Community, Holidays & Circulars</span>
    </div>
    <span class="dash-section-badge">Advancells Pulse</span>
</div>

<div class="grid-3 grid-equal-height" style="margin-bottom: 24px;">
    <!-- Team Celebrations & Milestones Widget -->
    <?php require_once BASE_PATH . '/views/widgets/celebrations.php'; ?>

    <!-- Upcoming Public Holidays Widget -->
    <?php require_once BASE_PATH . '/views/widgets/holidays.php'; ?>

    <!-- Company Circulars & Notices -->
    <div class="card">
        <div class="card-header card-header-compact">
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">
                <i class="fa-solid fa-bullhorn" style="color: #f59e0b;"></i>
                Company Notices & Circulars
            </h3>
            <span class="badge badge-secondary"><?= count($announcements) ?> Total</span>
        </div>
        <div class="card-body" style="padding: 12px 16px;">
            <?php if (empty($announcements)): ?>
                <div class="dash-empty-scroll-box" style="height: 330px;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 26px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">No official notices posted right now.</p>
                </div>
            <?php else: ?>
                <div class="dash-stream-scroll-wrap">
                    <div class="announcement-stream-list">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="announcement-stream-item">
                                <div class="announcement-stream-header">
                                    <span class="announcement-stream-title"><?= e($ann['title']) ?></span>
                                    <?= status_badge($ann['priority']) ?>
                                </div>
                                <p class="announcement-stream-body"><?= nl2br(e(substr($ann['content'], 0, 110))) ?>...</p>
                                <div class="announcement-stream-footer">
                                    <span><i class="fa-solid fa-user-pen"></i> <?= e($ann['author_name']) ?></span>
                                    <span><i class="fa-regular fa-clock"></i> <?= format_date($ann['created_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Section 5: Custody Assets & Hardware Equipment -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-laptop-code"></i>
        <span>My Custody Assets & Assigned Equipment</span>
    </div>
    <span class="dash-section-badge">IT Inventory</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 24px;">
    <!-- Allocated Hardware List -->
    <div class="card">
        <div class="card-header card-header-compact">
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">
                <i class="fa-solid fa-microchip" style="color: var(--primary);"></i>
                Assigned IT & Lab Equipment
            </h3>
            <span class="badge badge-purple"><?= count($myAssets) ?> Item(s)</span>
        </div>
        <div class="card-body" style="padding: 14px 18px;">
            <?php if (empty($myAssets)): ?>
                <div class="dash-empty-scroll-box" style="height: 180px;">
                    <i class="fa-solid fa-laptop-medical" style="font-size: 28px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">No equipment or custody hardware assigned to your profile.</p>
                </div>
            <?php else: ?>
                <div class="asset-stream-list">
                    <?php foreach ($myAssets as $ast): 
                        $catIcon = 'fa-laptop';
                        if ($ast['category'] === 'access_card') $catIcon = 'fa-id-badge';
                        elseif ($ast['category'] === 'mobile') $catIcon = 'fa-mobile-screen-button';
                        elseif ($ast['category'] === 'machinery' || $ast['category'] === 'lab_equipment') $catIcon = 'fa-flask-vial';
                    ?>
                        <div class="asset-stream-item">
                            <div class="asset-stream-left">
                                <div class="asset-icon-box">
                                    <i class="fa-solid <?= $catIcon ?>"></i>
                                </div>
                                <div class="asset-info-wrap">
                                    <div class="asset-name"><?= e($ast['name']) ?></div>
                                    <div class="asset-meta-line">
                                        <span style="font-family: monospace; font-weight: 700;"><?= e($ast['asset_code']) ?></span>
                                        • <?= e($ast['brand']) ?> <?= e($ast['model']) ?>
                                        <?php if (!empty($ast['serial_number'])): ?>
                                            • S/N: <span style="font-family: monospace;"><?= e($ast['serial_number']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-success" style="font-size: 10px; text-transform: capitalize;">
                                    <?= str_replace('_', ' ', e($ast['condition'] ?? 'Active')) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- IT Custody & Policy Compliance Card -->
    <div class="card">
        <div class="card-header card-header-compact">
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">
                <i class="fa-solid fa-shield-halved" style="color: #10b981;"></i>
                Hardware Care & Custody Standards
            </h3>
            <span class="badge badge-secondary">Policy & Support</span>
        </div>
        <div class="card-body" style="padding: 16px 20px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <p style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    All assigned devices, laptops, cleanroom tokens, and equipment are registered under Advancells IT Asset Custody policy.
                </p>
                <ul style="padding-left: 18px; margin: 0 0 16px 0; font-size: 12px; color: #475569; line-height: 1.6;">
                    <li>Maintain secure device encryption and cleanroom badge custody at all times.</li>
                    <li>Hardware issues or accidental damages must be reported to IT within 24 hours.</li>
                    <li>Do not transfer or loan assigned biometric access cards to other employees.</li>
                </ul>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="mailto:it-support@advancells.com" class="btn btn-sm btn-secondary" style="font-size: 11.5px;">
                    <i class="fa-solid fa-headset"></i> IT Helpdesk (helpdesk@advancells.com)
                </a>
                <a href="<?= url('assets/my-assets') ?>" class="btn btn-sm btn-outline-primary" style="font-size: 11.5px;">
                    <i class="fa-solid fa-list-check"></i> Asset History
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Apply Leave Modal -->
<div class="modal-backdrop-custom" id="quickApplyLeaveModal" style="display: none;" onclick="if(event.target === this) closeQuickApplyLeaveModal()">
    <div class="modal-dialog-custom modal-dialog-compact" style="max-width: 480px;">
        <div class="modal-header-custom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="modal-header-icon" style="background: #fdf2f8; color: var(--brand-plum);">
                    <i class="fa-solid fa-umbrella-beach"></i>
                </div>
                <div>
                    <h4 class="modal-title-custom">Quick Apply Leave</h4>
                    <span class="modal-subtitle-custom">Fast-Track ESS Leave Request</span>
                </div>
            </div>
            <button type="button" class="modal-close-custom" onclick="closeQuickApplyLeaveModal()">&times;</button>
        </div>
        <form id="quickApplyLeaveForm" onsubmit="submitQuickApplyLeave(event)">
            <?= csrf_field() ?>
            <div class="modal-body-custom" style="padding: 16px 20px;">
                <div id="quickLeaveAlert" style="display: none; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin-bottom: 12px;"></div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Leave Policy Category</label>
                    <select name="leave_type_id" class="form-control" required style="font-size: 12.5px;">
                        <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= $lt['id'] ?>"><?= e($lt['name']) ?> (<?= e($lt['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-d') ?>" required style="font-size: 12px;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d') ?>" required style="font-size: 12px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" name="is_half_day" id="quickIsHalfDay" value="1" onchange="toggleQuickHalfDay(this.checked)">
                        <span style="font-weight: 600;">Half Day Request</span>
                    </label>
                    <div id="quickHalfDayTypeWrap" style="display: none; margin-top: 6px;">
                        <select name="half_day_type" class="form-control" style="font-size: 12px;">
                            <option value="first_half">First Half (Morning Shift)</option>
                            <option value="second_half">Second Half (Afternoon Shift)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Reason / Purpose</label>
                    <textarea name="reason" rows="2" class="form-control" required placeholder="State briefly why leave is needed..." style="font-size: 12px;"></textarea>
                </div>
            </div>
            <div class="modal-footer-custom" style="padding: 12px 20px; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeQuickApplyLeaveModal()">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitQuickLeave">
                    <i class="fa-solid fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Regularize Modal -->
<div class="modal-backdrop-custom" id="quickRegularizeModal" style="display: none;" onclick="if(event.target === this) closeQuickRegularizeModal()">
    <div class="modal-dialog-custom modal-dialog-compact" style="max-width: 480px;">
        <div class="modal-header-custom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="modal-header-icon" style="background: #fffbeb; color: #d97706;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h4 class="modal-title-custom">Regularize Punch</h4>
                    <span class="modal-subtitle-custom">Request Missed Shift Punch Adjustment</span>
                </div>
            </div>
            <button type="button" class="modal-close-custom" onclick="closeQuickRegularizeModal()">&times;</button>
        </div>
        <form id="quickRegularizeForm" onsubmit="submitQuickRegularize(event)">
            <?= csrf_field() ?>
            <div class="modal-body-custom" style="padding: 16px 20px;">
                <div id="quickRegAlert" style="display: none; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin-bottom: 12px;"></div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Incident Date</label>
                    <input type="date" name="date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required style="font-size: 12px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Correct In-Time</label>
                        <input type="time" name="requested_punch_in" class="form-control" value="09:00" required style="font-size: 12px;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Correct Out-Time</label>
                        <input type="time" name="requested_punch_out" class="form-control" value="18:00" required style="font-size: 12px;">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 12px;">
                    <label style="font-size: 11.5px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Reason for Missed Punch</label>
                    <textarea name="reason" rows="2" class="form-control" required placeholder="e.g. Biometric scanner offline, field client visit..." style="font-size: 12px;"></textarea>
                </div>
            </div>
            <div class="modal-footer-custom" style="padding: 12px 20px; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeQuickRegularizeModal()">Cancel</button>
                <button type="submit" class="btn btn-sm btn-warning" id="btnSubmitQuickReg">
                    <i class="fa-solid fa-paper-plane"></i> Send Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openQuickApplyLeaveModal() {
    document.getElementById('quickApplyLeaveModal').style.display = 'flex';
}
function closeQuickApplyLeaveModal() {
    document.getElementById('quickApplyLeaveModal').style.display = 'none';
    const alertBox = document.getElementById('quickLeaveAlert');
    if (alertBox) alertBox.style.display = 'none';
}
function toggleQuickHalfDay(checked) {
    document.getElementById('quickHalfDayTypeWrap').style.display = checked ? 'block' : 'none';
}
function submitQuickApplyLeave(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitQuickLeave');
    const alertBox = document.getElementById('quickLeaveAlert');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(e.target);
    fetch('<?= url('leaves/apply') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Application';
        alertBox.style.display = 'block';
        if (data.success) {
            alertBox.style.background = '#dcfce7';
            alertBox.style.color = '#166534';
            alertBox.style.border = '1px solid #86efac';
            alertBox.textContent = data.message || 'Leave applied successfully!';
            setTimeout(() => {
                closeQuickApplyLeaveModal();
                window.location.reload();
            }, 1000);
        } else {
            alertBox.style.background = '#fee2e2';
            alertBox.style.color = '#991b1b';
            alertBox.style.border = '1px solid #fca5a5';
            alertBox.textContent = data.message || 'Failed to submit leave.';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Application';
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = 'Server error occurred. Please try again.';
    });
}

function openQuickRegularizeModal() {
    document.getElementById('quickRegularizeModal').style.display = 'flex';
}
function closeQuickRegularizeModal() {
    document.getElementById('quickRegularizeModal').style.display = 'none';
    const alertBox = document.getElementById('quickRegAlert');
    if (alertBox) alertBox.style.display = 'none';
}
function submitQuickRegularize(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitQuickReg');
    const alertBox = document.getElementById('quickRegAlert');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(e.target);
    fetch('<?= url('attendance/regularize') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Request';
        alertBox.style.display = 'block';
        if (data.success) {
            alertBox.style.background = '#dcfce7';
            alertBox.style.color = '#166534';
            alertBox.style.border = '1px solid #86efac';
            alertBox.textContent = data.message || 'Regularization submitted successfully!';
            setTimeout(() => {
                closeQuickRegularizeModal();
                window.location.reload();
            }, 1000);
        } else {
            alertBox.style.background = '#fee2e2';
            alertBox.style.color = '#991b1b';
            alertBox.style.border = '1px solid #fca5a5';
            alertBox.textContent = data.message || 'Failed to submit request.';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Request';
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = 'Server error occurred. Please try again.';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. My Hours Bar Graph
    const hoursCtx = document.getElementById('myHoursChart');
    if (hoursCtx) {
        new Chart(hoursCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($last7DaysHours, 'day')) ?>,
                datasets: [{
                    label: 'Hours Worked',
                    data: <?= json_encode(array_column($last7DaysHours, 'hours')) ?>,
                    backgroundColor: 'rgba(164, 36, 122, 0.88)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 8, cornerRadius: 6 }
                },
                scales: {
                    y: { beginAtZero: true, max: 12, grid: { color: '#f1f5f9' }, ticks: { stepSize: 2 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. My Leave Donut Chart
    const leaveDonutCtx = document.getElementById('myLeaveDonutChart');
    if (leaveDonutCtx) {
        new Chart(leaveDonutCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column(array_filter($leaveBalances, fn($b) => $b['leave_type_code'] !== 'LOP'), 'leave_type_name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('floatval', array_column(array_filter($leaveBalances, fn($b) => $b['leave_type_code'] !== 'LOP'), 'available'))) ?>,
                    backgroundColor: ['#5aa89f', '#269fc8', '#a4247a', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 8, cornerRadius: 6 }
                }
            }
        });
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>

