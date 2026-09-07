<?php
$pageTitle = 'Manager Portal & Team Oversight';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Manager Executive KPI Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Direct Reportees</span>
            <span class="stat-card-icon indigo"><i class="fa-solid fa-users"></i></span>
        </div>
        <div class="stat-card-value"><?= $teamCount ?></div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-circle-check"></i> Active</span>
            <span>Direct team members</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Pending Leaves</span>
            <span class="stat-card-icon rose"><i class="fa-solid fa-clipboard-question"></i></span>
        </div>
        <div class="stat-card-value"><?= count($pendingLeaves) ?></div>
        <div class="stat-card-sub">
            <?php if (count($pendingLeaves) > 0): ?>
                <span class="trend-down" style="color: #e11d48;"><i class="fa-solid fa-clock"></i> Action Required</span>
            <?php else: ?>
                <span class="trend-up"><i class="fa-solid fa-circle-check"></i> All Clear</span>
            <?php endif; ?>
            <span>Awaiting authorization</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Regularizations</span>
            <span class="stat-card-icon amber"><i class="fa-solid fa-clock-rotate-left"></i></span>
        </div>
        <div class="stat-card-value"><?= $pendingRegsCount ?></div>
        <div class="stat-card-sub">
            <?php if ($pendingRegsCount > 0): ?>
                <span class="trend-down" style="color: #d97706;"><i class="fa-solid fa-exclamation-circle"></i> Pending</span>
            <?php else: ?>
                <span class="trend-up"><i class="fa-solid fa-circle-check"></i> Up to Date</span>
            <?php endif; ?>
            <span>Punch adjustments</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">My Shift Status</span>
            <span class="stat-card-icon green"><i class="fa-solid fa-fingerprint"></i></span>
        </div>
        <div class="stat-card-value" style="font-size: 22px;">
            <?= $myTodayAtt && !empty($myTodayAtt['punch_in']) ? format_time($myTodayAtt['punch_in']) : 'Not Punched' ?>
        </div>
        <div class="stat-card-sub">
            <?php if ($myTodayAtt && !empty($myTodayAtt['punch_out'])): ?>
                <span class="trend-up"><i class="fa-solid fa-circle-check"></i> Shift Ended</span>
                <span><?= $myTodayAtt['total_hours'] ?>h logged</span>
            <?php elseif ($myTodayAtt && !empty($myTodayAtt['punch_in'])): ?>
                <span class="trend-up" style="color: #059669;"><i class="fa-solid fa-play"></i> Active Shift</span>
                <span>In progress</span>
            <?php else: ?>
                <span class="trend-down" style="color: #64748b;"><i class="fa-solid fa-clock"></i> Not Clocked In</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Manager Analytics & Executive Approvals Row -->
<div class="grid-2" style="margin-bottom: 24px;">
    <!-- Team Attendance Status Donut Chart with Center Metric -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i>
                    Team Attendance Status
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Shift presence distribution for <?= date('d M Y') ?>
                </div>
            </div>
            <span class="badge badge-info">Today</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 20px;">
            <div class="donut-chart-wrapper" style="max-width: 220px; width: 100%; height: 190px;">
                <canvas id="teamAttPieChart"></canvas>
                <div class="donut-center-metric">
                    <span class="metric-number"><?= ($teamPresent + $teamLate) ?>/<?= $teamCount ?></span>
                    <span class="metric-label">Checked In</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 18px; font-size: 11.5px; flex-wrap: wrap; justify-content: center;">
                <span class="badge badge-success" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> Present: <?= $teamPresent ?></span>
                <span class="badge badge-warning" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> Late: <?= $teamLate ?></span>
                <span class="badge badge-purple" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> On Leave: <?= $teamLeave ?></span>
                <span class="badge badge-secondary" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> Pending: <?= $teamPending ?></span>
            </div>
        </div>
    </div>

    <!-- Manager Approvals & Leadership Console -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-bolt" style="color: var(--primary);"></i>
                    Manager Approvals & Actions
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Pending authorization queue for direct reportees
                </div>
            </div>
            <span class="badge badge-purple">Executive Console</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; padding: 24px;">
            <div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 16px;">
                        <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 4px;">
                            Leave Approvals
                        </div>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <span style="font-size: 24px; font-weight: 700; color: var(--text-main);"><?= count($pendingLeaves) ?></span>
                            <span style="font-size: 12px; color: <?= count($pendingLeaves) > 0 ? '#e11d48' : '#059669' ?>; font-weight: 600;">
                                <?= count($pendingLeaves) > 0 ? 'Action required' : 'All clear' ?>
                            </span>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 16px;">
                        <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 4px;">
                            Regularizations
                        </div>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <span style="font-size: 24px; font-weight: 700; color: var(--text-main);"><?= $pendingRegsCount ?></span>
                            <span style="font-size: 12px; color: <?= $pendingRegsCount > 0 ? '#d97706' : '#059669' ?>; font-weight: 600;">
                                <?= $pendingRegsCount > 0 ? 'Pending review' : 'Up to date' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 20px;">
                    Review team attendance adjustments and leave requests to maintain schedule transparency, compliance, and payroll readiness.
                </p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?= url('leaves/approvals') ?>" class="btn btn-primary">
                    <i class="fa-solid fa-clipboard-check"></i> Review Leaves (<?= count($pendingLeaves) ?>)
                </a>
                <a href="<?= url('attendance/regularize-approvals') ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-clock-rotate-left"></i> Regularizations (<?= $pendingRegsCount ?>)
                </a>
                <a href="<?= url('attendance/punch') ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-fingerprint"></i> My Punch
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Team Attendance Status Today Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa-solid fa-users" style="color: var(--primary);"></i>
            Team Attendance Status Today (<?= date('d M Y') ?>)
        </h3>
        <span class="badge badge-secondary"><?= count($teamAtt) ?> Members</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($teamAtt)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-users-slash" style="font-size: 32px; color: #94a3b8; margin-bottom: 8px;"></i>
                <p>No reportees currently assigned to your team.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Punch In</th>
                            <th>Punch Out</th>
                            <th>Hours Worked</th>
                            <th>Status Today</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamAtt as $member): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 36px; height: 36px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                            <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong style="color: var(--text-main);"><?= e($member['name']) ?></strong><br>
                                            <span style="color: var(--text-muted); font-size: 11px; font-family: monospace;"><?= e($member['emp_code']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $member['punch_in'] ? format_time($member['punch_in']) : '<span style="color:#94a3b8;">--:--</span>' ?></td>
                                <td><?= $member['punch_out'] ? format_time($member['punch_out']) : '<span style="color:#94a3b8;">--:--</span>' ?></td>
                                <td><?= $member['total_hours'] > 0 ? '<strong>' . $member['total_hours'] . '</strong> hrs' : '<span style="color:#94a3b8;">--</span>' ?></td>
                                <td>
                                    <?php if (!empty($member['status'])): ?>
                                        <?= status_badge($member['status']) ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Not Checked In</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Team Attendance Donut Chart with Enterprise Styling
    const teamPieCtx = document.getElementById('teamAttPieChart');
    if (teamPieCtx) {
        new Chart(teamPieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present On-Time', 'Late Entry', 'On Leave', 'Pending Entry'],
                datasets: [{
                    data: [<?= (int)$teamPresent ?>, <?= (int)$teamLate ?>, <?= (int)$teamLeave ?>, <?= (int)$teamPending ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6', '#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 600 },
                        bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                }
            }
        });
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
