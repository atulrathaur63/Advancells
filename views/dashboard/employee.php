<?php
$pageTitle = 'Employee Self-Service (ESS)';
require_once BASE_PATH . '/views/layouts/header.php';

$totalAvailLeaves = (int)array_sum(array_column(array_filter($leaveBalances, fn($b) => $b['leave_type_code'] !== 'LOP'), 'available'));
?>

<!-- Welcome Banner & Quick Punch Card -->
<div class="grid-2" style="margin-bottom: 24px;">
    <!-- Welcome Executive Card -->
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; padding: 24px;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span class="badge badge-purple">
                        <i class="fa-solid fa-id-badge" style="margin-right: 4px;"></i> Advancells ESS Portal
                    </span>
                    <span style="font-family: monospace; font-size: 11.5px; color: var(--text-muted); font-weight: 700; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
                        <?= e($user['emp_code'] ?? 'ADV-EMP') ?>
                    </span>
                </div>
                <h2 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    Welcome back, <?= e($user['first_name'] ?? $user['name']) ?>!
                </h2>
                <p style="color: var(--text-muted); font-size: 13.5px; line-height: 1.5; margin-bottom: 20px;">
                    Department: <strong style="color: var(--text-main);"><?= e($user['department_name'] ?? 'Advancells Biotech') ?></strong> • 
                    Role: <strong style="color: var(--text-main);"><?= e($user['designation_title'] ?? 'Specialist') ?></strong>
                </p>
            </div>
            
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= url('leaves/my-leaves') ?>" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-umbrella-beach"></i> Apply for Leave
                </a>
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
                <i class="fa-solid fa-fingerprint" style="margin-right: 6px; color: #f43f5e;"></i> Real-Time Punch Terminal
            </div>
            <div class="date-live" style="font-size: 12.5px; color: #a5f3fc; font-weight: 700;">Today</div>
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
                <button type="submit" class="btn btn-success btn-lg" style="width: 100%;">
                    <i class="fa-solid fa-play"></i> Clock In (Start Work Shift)
                </button>
            <?php elseif (empty($myTodayAtt['punch_out'])): ?>
                <input type="hidden" name="punch_type" value="out">
                <div style="background: rgba(255,255,255,0.1); padding: 10px 14px; border-radius: var(--radius-md); margin-bottom: 12px; font-size: 13px;">
                    <i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 4px;"></i> Punched in at <strong><?= format_time($myTodayAtt['punch_in']) ?></strong> (<?= ucfirst($myTodayAtt['status']) ?>)
                </div>
                <div style="margin-bottom: 10px;">
                    <input type="text" name="notes" placeholder="Optional punch-out note..." class="form-control" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff;">
                </div>
                <button type="submit" class="btn btn-danger btn-lg" style="width: 100%;">
                    <i class="fa-solid fa-stop"></i> Clock Out (End Work Shift)
                </button>
            <?php else: ?>
                <div style="background: rgba(255,255,255,0.1); padding: 12px 14px; border-radius: var(--radius-md); font-size: 13px; text-align: center;">
                    <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 18px; margin-bottom: 4px;"></i><br>
                    Completed shift: In <strong><?= format_time($myTodayAtt['punch_in']) ?></strong> • Out <strong><?= format_time($myTodayAtt['punch_out']) ?></strong><br>
                    Total Hours Worked: <strong><?= $myTodayAtt['total_hours'] ?> hrs</strong>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Attendance Monthly Stats for Employee -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Days Present</span>
            <span class="stat-card-icon green"><i class="fa-solid fa-calendar-check"></i></span>
        </div>
        <div class="stat-card-value"><?= $attSummary['present'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-check"></i> On Duty</span>
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
            <span class="stat-card-icon purple"><i class="fa-solid fa-umbrella-beach"></i></span>
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
            <span class="stat-card-icon blue"><i class="fa-solid fa-business-time"></i></span>
        </div>
        <div class="stat-card-value"><?= $attSummary['total_hours'] ?>h</div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-hourglass-half"></i> Cumulative</span>
            <span>Monthly shift total</span>
        </div>
    </div>
</div>

<!-- ESS Visual Charts: Weekly Hours Graph & Leave Balances Donut Chart -->
<div class="grid-2" style="margin-bottom: 24px;">
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
                    <i class="fa-solid fa-chart-pie" style="color: #0284c7;"></i>
                    Annual Leave Quotas (<?= date('Y') ?>)
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Available balances by policy category
                </div>
            </div>
            <span class="badge badge-purple">Entitlements</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 20px;">
            <div class="donut-chart-wrapper" style="max-width: 220px; width: 100%; height: 190px;">
                <canvas id="myLeaveDonutChart"></canvas>
                <div class="donut-center-metric">
                    <span class="metric-number"><?= $totalAvailLeaves ?></span>
                    <span class="metric-label">Days Left</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 18px; font-size: 11.5px; flex-wrap: wrap; justify-content: center;">
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

<!-- Latest Payslip Card & Recent Requests -->
<div class="grid-2">
    <!-- Latest Payslip Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-file-invoice-dollar" style="color: #10b981;"></i>
                Latest Salary Statement
            </h3>
            <a href="<?= url('payroll/my-payslips') ?>" class="btn btn-sm btn-secondary">All Slips</a>
        </div>
        <div class="card-body">
            <?php if ($latestPayroll): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md);">
                    <div>
                        <div style="font-size: 11.5px; font-weight: 700; color: #047857; text-transform: uppercase;">
                            <?= date('F Y', mktime(0, 0, 0, $latestPayroll['month'], 1, $latestPayroll['year'])) ?>
                        </div>
                        <div style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: #065f46; margin: 4px 0;">
                            <?= format_currency($latestPayroll['net_salary']) ?>
                        </div>
                        <div style="font-size: 12px; color: #047857;">
                            Net Disbursed Take-Home • <?= status_badge($latestPayroll['payment_status']) ?>
                        </div>
                    </div>
                    <a href="<?= url('payroll/payslip?id=' . $latestPayroll['id']) ?>" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-print"></i> View / Print
                    </a>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); padding: 10px 0;">No generated payslip found for your account yet.</p>
            <?php endif; ?>
        </div>
    </div>

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
                <div style="padding: 24px; text-align: center; color: var(--text-muted);">
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
</div>

<script>
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
                    backgroundColor: 'rgba(147, 32, 108, 0.85)',
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
                    backgroundColor: ['#0d9488', '#e11d48', '#0284c7', '#8b5cf6'],
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
