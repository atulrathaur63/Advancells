<?php
$pageTitle = 'Executive Analytics Dashboard';
require_once BASE_PATH . '/views/layouts/header.php';
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
                Happy <?= $myCelebration['type_label'] ?>, <?= e(Auth::user()['name']) ?>!
            </h3>
            <p class="celebrant-hero-desc">
                The entire Advancells family celebrates your <strong><?= e($myCelebration['milestone_text']) ?></strong>.
                <?php if ($myCelebration['wishes_count'] > 0): ?>
                    You have received <strong><?= $myCelebration['wishes_count'] ?> greeting(s)</strong> from your teammates!
                <?php else: ?>
                    Wishing you inspiring leadership, good health, and continued success!
                <?php endif; ?>
            </p>
        </div>
        <div class="celebrant-hero-action">
            <button type="button" class="btn-read-greetings" 
                    onclick="openViewWishesModal(<?= (int)$empId ?>, '<?= e(Auth::user()['name']) ?>', '<?= $myCelebration['type'] ?>')">
                <i class="fa-regular fa-envelope-open"></i>
                <span>Read Greetings (<?= $myCelebration['wishes_count'] ?>)</span>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Section 1: Organization Pulse & Workforce Metrics -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Organization Pulse & Workforce Metrics</span>
    </div>
    <span class="dash-section-badge">Live Company KPIs</span>
</div>

<!-- Executive KPI Tiles -->
<div class="stats-grid">
    <!-- Tile 1: Total Workforce -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Active Workforce</span>
            <div class="stat-card-icon magenta">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <div class="stat-card-value"><?= $stats['total'] ?></div>
        <div class="stat-card-sub">
            <span class="badge badge-success" style="font-size: 10px; padding: 2px 6px;">
                <i class="fa-solid fa-arrow-trend-up"></i> 100% Active
            </span>
            <span>across <?= $stats['departments'] ?> divisions</span>
        </div>
    </div>

    <!-- Tile 2: Today Attendance -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Present Today</span>
            <div class="stat-card-icon green">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>
        <div class="stat-card-value">
            <?= $attToday['present'] + $attToday['late'] ?>
            <span style="font-size: 13px; font-weight: 600; color: #10b981;">(<?= $attToday['attendance_rate'] ?>%)</span>
        </div>
        <div class="stat-card-sub">
            <span style="color: #059669; font-weight: 600;"><?= $attToday['present'] ?> on time</span>
            <span>•</span>
            <span style="color: #d97706;"><?= $attToday['late'] ?> late entry</span>
        </div>
    </div>

    <!-- Tile 3: On Approved Leave -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">On Leave Today</span>
            <div class="stat-card-icon amber">
                <i class="fa-solid fa-umbrella-beach"></i>
            </div>
        </div>
        <div class="stat-card-value"><?= $attToday['leave'] ?></div>
        <div class="stat-card-sub">
            <span>Approved scheduled time-off</span>
        </div>
    </div>

    <!-- Tile 4: Pending Leaves -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Leave Approvals</span>
            <div class="stat-card-icon rose">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </div>
        <div class="stat-card-value"><?= count($pendingLeaves) ?></div>
        <div class="stat-card-sub">
            <?php if (count($pendingLeaves) > 0): ?>
                <span style="color: #e11d48; font-weight: 600;">Requires manager review</span>
            <?php else: ?>
                <span style="color: #059669;">All applications cleared</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tile 5: Regularizations -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Reg. Requests</span>
            <div class="stat-card-icon blue">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>
        <div class="stat-card-value"><?= $pendingRegsCount ?></div>
        <div class="stat-card-sub">
            <span>Pending missed punches</span>
        </div>
    </div>

    <!-- Tile 6: Departments -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Specialized Units</span>
            <div class="stat-card-icon purple">
                <i class="fa-solid fa-sitemap"></i>
            </div>
        </div>
        <div class="stat-card-value"><?= $stats['departments'] ?></div>
        <div class="stat-card-sub">
            <span>Stem cell research & therapy</span>
        </div>
    </div>
</div>

<!-- Operations Command Bar -->
<div class="command-bar">
    <div class="command-bar-title">
        <i class="fa-solid fa-sliders text-primary"></i>
        <span>Workforce Operations & Executive Quick Actions</span>
    </div>
    <div class="command-bar-actions">
        <a href="<?= url('employees/create') ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> New Employee
        </a>
        <a href="<?= url('payroll') ?>" class="btn btn-success btn-sm">
            <i class="fa-solid fa-bolt"></i> Process Payroll
        </a>
        <a href="<?= url('leaves/approvals') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-clipboard-check"></i> Leave Queue (<?= count($pendingLeaves) ?>)
        </a>
        <a href="<?= url('announcements') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-bullhorn"></i> Post Notice
        </a>
    </div>
</div>

<!-- Section 2: Attendance Trends & Real-Time Presence -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-chart-line"></i>
        <span>Attendance Trends & Real-Time Presence</span>
    </div>
    <span class="dash-section-badge">Shift Analytics</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Chart 1: Attendance Trend Graph -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-line text-primary"></i>
                    7-Day Attendance Trend Analysis
                </h3>
                <span class="card-subtitle">Daily present staff vs staff on leave</span>
            </div>
            <span class="badge badge-info">Area Graph</span>
        </div>
        <div class="card-body">
            <div style="height: 230px; position: relative;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Today's Status Donut Chart with Center Metric -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: #10b981;"></i>
                    Today's Attendance Proportion
                </h3>
                <span class="card-subtitle">Real-time status breakdown for <?= date('d M Y') ?></span>
            </div>
            <span class="badge badge-success">Live Status</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 18px 20px;">
            <div class="donut-chart-wrapper" style="height: 180px;">
                <canvas id="todayAttendancePieChart"></canvas>
                <div class="donut-center-metric">
                    <div class="donut-center-value"><?= $attToday['attendance_rate'] ?>%</div>
                    <div class="donut-center-label">Present Rate</div>
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 14px; font-size: 11.5px; flex-wrap: wrap; justify-content: center;">
                <span><i class="fa-solid fa-circle" style="color: #10b981; font-size: 8px;"></i> Present: <strong><?= $attToday['present'] ?></strong></span>
                <span><i class="fa-solid fa-circle" style="color: #f59e0b; font-size: 8px;"></i> Late: <strong><?= $attToday['late'] ?></strong></span>
                <span><i class="fa-solid fa-circle" style="color: #8b5cf6; font-size: 8px;"></i> On Leave: <strong><?= $attToday['leave'] ?></strong></span>
                <span><i class="fa-solid fa-circle" style="color: #cbd5e1; font-size: 8px;"></i> Unlogged: <strong><?= $attToday['not_logged'] ?></strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Department Analytics & Leave Utilization -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-sitemap"></i>
        <span>Department Analytics & Leave Policy Utilization</span>
    </div>
    <span class="dash-section-badge">Demographics</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Chart 3: Department Headcount Donut Chart with Center Metric -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: #0284c7;"></i>
                    Department Headcount Distribution
                </h3>
                <span class="card-subtitle">Workforce across <?= count($deptData) ?> divisions</span>
            </div>
            <span class="badge badge-purple">Donut Chart</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 18px 20px;">
            <div class="donut-chart-wrapper" style="height: 180px;">
                <canvas id="deptDistChart"></canvas>
                <div class="donut-center-metric">
                    <div class="donut-center-value"><?= $stats['total'] ?></div>
                    <div class="donut-center-label">Workforce</div>
                </div>
            </div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 12px; text-align: center;">
                Clinical research, lab engineering, quality, and administrative staff
            </div>
        </div>
    </div>

    <!-- Chart 4: Leave Consumption Bar Chart -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-column" style="color: #f43f5e;"></i>
                    Annual Leave Consumption by Policy
                </h3>
                <span class="card-subtitle">Cumulative days utilized in <?= date('Y') ?></span>
            </div>
            <span class="badge badge-secondary">Policy Usage</span>
        </div>
        <div class="card-body">
            <div style="height: 230px; position: relative;">
                <canvas id="leaveUtilizationChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Section 4: Authorizations & Workplace Milestones -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-clipboard-check"></i>
        <span>Authorizations & Workplace Milestones</span>
    </div>
    <span class="dash-section-badge">Action Hub</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Dual Quick Authorization Hub (Leaves + Regularizations) -->
    <?php require_once BASE_PATH . '/views/widgets/quick_authorizations.php'; ?>

    <!-- Team Celebrations & Milestones Widget -->
    <?php require_once BASE_PATH . '/views/widgets/celebrations.php'; ?>
</div>

<!-- Section 5: Official Circulars, Holidays & System Audit -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-bullhorn"></i>
        <span>Official Circulars, Holidays & System Audit Trail</span>
    </div>
    <span class="dash-section-badge">Communications & Logs</span>
</div>

<div class="grid-3 grid-equal-height" style="margin-bottom: 24px;">
    <!-- Company Circulars Stream -->
    <div class="card">
        <div class="card-header card-header-compact">
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">
                <i class="fa-solid fa-bullhorn" style="color: #f59e0b;"></i>
                Company Circulars
            </h3>
            <a href="<?= url('announcements') ?>" class="btn btn-sm btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">Manage</a>
        </div>
        <div class="card-body" style="padding: 12px 16px;">
            <?php if (empty($announcements)): ?>
                <div class="dash-empty-scroll-box" style="height: 250px;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 26px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">No circulars posted.</p>
                </div>
            <?php else: ?>
                <div class="dash-stream-scroll-wrap" style="height: 250px; max-height: 250px;">
                    <div class="announcement-stream-list">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="announcement-stream-item">
                                <div class="announcement-stream-header">
                                    <span class="announcement-stream-title"><?= e($ann['title']) ?></span>
                                    <?= status_badge($ann['priority']) ?>
                                </div>
                                <p class="announcement-stream-body"><?= nl2br(e(substr($ann['content'], 0, 95))) ?>...</p>
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

    <!-- Upcoming Holidays Widget -->
    <?php require_once BASE_PATH . '/views/widgets/holidays.php'; ?>

    <!-- Recent System Activity Logs -->
    <div class="card">
        <div class="card-header card-header-compact">
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">
                <i class="fa-solid fa-clock-rotate-left" style="color: #6366f1;"></i>
                Recent Activity
            </h3>
            <span class="badge badge-secondary" style="font-size: 11px;">Audit Trail</span>
        </div>
        <div class="card-body" style="padding: 12px 16px;">
            <?php if (empty($recentActivities)): ?>
                <div class="dash-empty-scroll-box" style="height: 250px;">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 26px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">No recent system logs.</p>
                </div>
            <?php else: ?>
                <div class="dash-stream-scroll-wrap" style="height: 250px; max-height: 250px;">
                    <div class="activity-audit-list">
                        <?php foreach ($recentActivities as $act): ?>
                            <div class="activity-audit-item">
                                <div style="min-width: 0;">
                                    <span class="activity-audit-user"><?= e($act['user_name'] ?? 'System') ?></span>
                                    <span class="activity-audit-action">: <?= e($act['action'] ?? '') ?></span>
                                </div>
                                <span class="activity-audit-time"><i class="fa-regular fa-clock"></i> <?= format_time($act['created_at']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Global Chart.js Enterprise Styling
    Chart.defaults.font.family = "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.95)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.boxPadding = 4;
    Chart.defaults.plugins.tooltip.titleFont = { weight: 'bold', size: 12 };

    // 1. Attendance Trend Line / Area Chart
    const trendCanvas = document.getElementById('attendanceTrendChart');
    if (trendCanvas) {
        const ctx = trendCanvas.getContext('2d');
        const gradPlum = ctx.createLinearGradient(0, 0, 0, 220);
        gradPlum.addColorStop(0, 'rgba(164, 36, 122, 0.28)');
        gradPlum.addColorStop(1, 'rgba(164, 36, 122, 0.0)');

        const gradCyan = ctx.createLinearGradient(0, 0, 0, 220);
        gradCyan.addColorStop(0, 'rgba(38, 159, 200, 0.22)');
        gradCyan.addColorStop(1, 'rgba(38, 159, 200, 0.0)');

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($trendDays, 'date')) ?>,
                datasets: [
                    {
                        label: 'Present Staff',
                        data: <?= json_encode(array_column($trendDays, 'present')) ?>,
                        borderColor: '#a4247a',
                        backgroundColor: gradPlum,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: '#a4247a',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'On Leave',
                        data: <?= json_encode(array_column($trendDays, 'leave')) ?>,
                        borderColor: '#269fc8',
                        backgroundColor: gradCyan,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: '#269fc8',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 10, font: { weight: '600', size: 11 } } }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9', borderDash: [4, 4] }, 
                        ticks: { stepSize: 1, font: { size: 11 } } 
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }

    // 2. Department Headcount Donut Chart
    const deptCtx = document.getElementById('deptDistChart');
    if (deptCtx) {
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($deptData, 'name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($deptData, 'count')) ?>,
                    backgroundColor: ['#a4247a', '#269fc8', '#5aa89f', '#10b981', '#f59e0b', '#7c3aed', '#64748b'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 3. Today Attendance Breakdown Donut Chart
    const todayPieCtx = document.getElementById('todayAttendancePieChart');
    if (todayPieCtx) {
        new Chart(todayPieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present On-Time', 'Late Entry', 'On Leave', 'Unlogged'],
                datasets: [{
                    data: [<?= $attToday['present'] ?>, <?= $attToday['late'] ?>, <?= $attToday['leave'] ?>, <?= $attToday['not_logged'] ?>],
                    backgroundColor: ['#5aa89f', '#f59e0b', '#a4247a', '#e2e8f0'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 4. Leave Utilization by Policy Bar Chart
    const leaveCtx = document.getElementById('leaveUtilizationChart');
    if (leaveCtx) {
        new Chart(leaveCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($leaveTypeDist, 'code')) ?>,
                datasets: [{
                    label: 'Days Taken (<?= date('Y') ?>)',
                    data: <?= json_encode(array_column($leaveTypeDist, 'used')) ?>,
                    backgroundColor: ['#5aa89f', '#e11d48', '#269fc8', '#a4247a', '#64748b'],
                    borderRadius: 6,
                    maxBarThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9', borderDash: [4, 4] }, 
                        ticks: { stepSize: 1, font: { size: 11 } } 
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
