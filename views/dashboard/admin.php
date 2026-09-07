<?php
$pageTitle = 'Executive Analytics Dashboard';
require_once BASE_PATH . '/views/layouts/header.php';
?>

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

<!-- Analytics Grid 1: Attendance Trend & Dept Distribution -->
<div class="grid-2" style="margin-bottom: 22px;">
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

    <!-- Chart 2: Department Headcount Donut Chart with Center Metric -->
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
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="donut-chart-wrapper">
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
</div>

<!-- Analytics Grid 2: Today Attendance Status & Leave Utilization -->
<div class="grid-2" style="margin-bottom: 22px;">
    <!-- Chart 3: Today's Status Donut Chart with Center Metric -->
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
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="donut-chart-wrapper">
                <canvas id="todayAttendancePieChart"></canvas>
                <div class="donut-center-metric">
                    <div class="donut-center-value"><?= $attToday['attendance_rate'] ?>%</div>
                    <div class="donut-center-label">Present Rate</div>
                </div>
            </div>
            <div style="display: flex; gap: 14px; margin-top: 14px; font-size: 12px; flex-wrap: wrap; justify-content: center;">
                <span><i class="fa-solid fa-circle" style="color: #10b981; font-size: 8px;"></i> Present: <strong><?= $attToday['present'] ?></strong></span>
                <span><i class="fa-solid fa-circle" style="color: #f59e0b; font-size: 8px;"></i> Late: <strong><?= $attToday['late'] ?></strong></span>
                <span><i class="fa-solid fa-circle" style="color: #8b5cf6; font-size: 8px;"></i> On Leave: <strong><?= $attToday['leave'] ?></strong></span>
                <span><i class="fa-solid fa-circle" style="color: #cbd5e1; font-size: 8px;"></i> Unlogged: <strong><?= $attToday['not_logged'] ?></strong></span>
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

<!-- Pending Approvals & Activity Logs Table -->
<div class="grid-2">
    <!-- Pending Leaves Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-clipboard-list text-primary"></i>
                Pending Leave Queue (<?= count($pendingLeaves) ?>)
            </h3>
            <a href="<?= url('leaves/approvals') ?>" class="btn btn-sm btn-secondary">Review Portal</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($pendingLeaves)): ?>
                <div style="padding: 36px 20px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-circle-check" style="font-size: 28px; color: #10b981; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px;">All leave applications have been reviewed!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Policy</th>
                                <th>Dates</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pendingLeaves, 0, 5) as $l): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 32px; height: 32px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">
                                                <?= strtoupper(substr($l['employee_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= e($l['employee_name']) ?></strong><br>
                                                <small style="color:var(--text-muted);"><?= e($l['emp_code']) ?> • <?= e($l['department_name']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-purple"><?= e($l['leave_type_code']) ?></span></td>
                                    <td>
                                        <?= format_date($l['from_date'], 'd M') ?> - <?= format_date($l['to_date'], 'd M') ?><br>
                                        <small><strong><?= $l['total_days'] ?> day(s)</strong></small>
                                    </td>
                                    <td>
                                        <a href="<?= url('leaves/approvals') ?>" class="btn btn-sm btn-primary">Review</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Company Circulars Stream -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-bullhorn" style="color: #f59e0b;"></i>
                Company Circulars & Notices
            </h3>
            <a href="<?= url('announcements') ?>" class="btn btn-sm btn-secondary">Manage</a>
        </div>
        <div class="card-body">
            <?php if (empty($announcements)): ?>
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">
                    No circulars posted.
                </div>
            <?php else: ?>
                <?php foreach (array_slice($announcements, 0, 3) as $ann): ?>
                    <div style="padding: 14px 16px; background: #f8fafc; border-radius: var(--radius-md); border-left: 4px solid var(--primary); margin-bottom: 12px; box-shadow: var(--shadow-xs);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <strong style="font-size: 14px; color: var(--text-main);"><?= e($ann['title']) ?></strong>
                            <?= status_badge($ann['priority']) ?>
                        </div>
                        <p style="font-size: 13px; color: #475569; margin-bottom: 6px; line-height: 1.5;"><?= nl2br(e(substr($ann['content'], 0, 130))) ?>...</p>
                        <small style="font-size: 11px; color: var(--text-light);"><i class="fa-regular fa-clock"></i> Posted on <?= format_date($ann['created_at']) ?> by <?= e($ann['author_name']) ?></small>
                    </div>
                <?php endforeach; ?>
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
        const gradMagenta = ctx.createLinearGradient(0, 0, 0, 220);
        gradMagenta.addColorStop(0, 'rgba(147, 32, 108, 0.22)');
        gradMagenta.addColorStop(1, 'rgba(147, 32, 108, 0.0)');

        const gradTeal = ctx.createLinearGradient(0, 0, 0, 220);
        gradTeal.addColorStop(0, 'rgba(2, 132, 199, 0.15)');
        gradTeal.addColorStop(1, 'rgba(2, 132, 199, 0.0)');

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($trendDays, 'date')) ?>,
                datasets: [
                    {
                        label: 'Present Staff',
                        data: <?= json_encode(array_column($trendDays, 'present')) ?>,
                        borderColor: '#93206c',
                        backgroundColor: gradMagenta,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: '#93206c',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'On Leave',
                        data: <?= json_encode(array_column($trendDays, 'leave')) ?>,
                        borderColor: '#0284c7',
                        backgroundColor: gradTeal,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: '#0284c7',
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
                    backgroundColor: ['#93206c', '#0284c7', '#0d9488', '#10b981', '#f59e0b', '#7c3aed', '#64748b'],
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
                    backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6', '#e2e8f0'],
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
                    backgroundColor: ['#0d9488', '#e11d48', '#0284c7', '#8b5cf6', '#64748b'],
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
