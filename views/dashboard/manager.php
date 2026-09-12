<?php
$pageTitle = 'Manager Portal & Team Oversight';
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

<!-- Section 1: Team Oversight & Direct Reportees -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-users-viewfinder"></i>
        <span>Team Oversight & Direct Reportees</span>
    </div>
    <span class="dash-section-badge">Management Pulse</span>
</div>

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

<!-- Section 2: Team Attendance & Shift Coverage Radar -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Team Attendance & Shift Coverage Operations</span>
    </div>
    <span class="dash-section-badge">Shift Operations</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Team Attendance Status Donut Chart with Center Metric -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i>
                    Team Attendance Status Today
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Shift presence distribution for <?= date('d M Y') ?>
                </div>
            </div>
            <span class="badge badge-info">Today</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 22px 20px;">
            <div class="donut-chart-wrapper" style="max-width: 220px; width: 100%; height: 180px;">
                <canvas id="teamAttPieChart"></canvas>
                <div class="donut-center-metric">
                    <span class="metric-number"><?= ($teamPresent + $teamLate) ?>/<?= $teamCount ?></span>
                    <span class="metric-label">Checked In</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 16px; font-size: 11.5px; flex-wrap: wrap; justify-content: center;">
                <span class="badge badge-success" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> Present: <?= $teamPresent ?></span>
                <span class="badge badge-warning" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> Late: <?= $teamLate ?></span>
                <span class="badge badge-magenta" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> On Leave: <?= $teamLeave ?></span>
                <span class="badge badge-secondary" style="font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i> Pending: <?= $teamPending ?></span>
            </div>
        </div>
    </div>

    <!-- 7-Day Team Availability & Coverage Radar Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-calendar-week" style="color: var(--primary);"></i>
                    7-Day Team Coverage Radar
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Forecasted reportee availability & scheduled leaves
                </div>
            </div>
            <span class="badge badge-teal">Workforce Radar</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; padding: 18px 20px;">
            <div class="team-radar-grid">
                <?php foreach ($teamCoverage7Days as $day): 
                    $covClass = $day['coverage_percent'] >= 100 ? 'full' : ($day['coverage_percent'] >= 60 ? 'partial' : 'low');
                ?>
                    <div class="radar-day-card <?= $day['is_today'] ? 'is-today' : '' ?>">
                        <?php if ($day['is_today']): ?>
                            <span class="radar-today-flag">Today</span>
                        <?php endif; ?>
                        <span class="radar-day-name"><?= $day['day_name'] ?></span>
                        <span class="radar-day-date"><?= $day['display_date'] ?></span>
                        <div class="radar-gauge-wrap <?= $covClass ?>">
                            <?= $day['coverage_percent'] ?>%
                        </div>
                        <span class="radar-day-status <?= $covClass ?>">
                            <?= $day['available_count'] ?>/<?= $teamCount ?><br>Active
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalScheduledLeavesCount > 0): ?>
                <div class="radar-summary-strip alert">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 15px; color: #d97706; flex-shrink: 0;"></i>
                    <div style="line-height: 1.4;">
                        <strong>Staffing Notice:</strong> <?= $totalScheduledLeavesCount ?> scheduled leave instance(s) detected across your direct reportees over the next 7 days.
                    </div>
                </div>
            <?php else: ?>
                <div class="radar-summary-strip good">
                    <i class="fa-solid fa-circle-check" style="font-size: 15px; color: #16a34a; flex-shrink: 0;"></i>
                    <div style="line-height: 1.4;">
                        <strong>Full Workforce Coverage:</strong> 100% team capacity expected across the upcoming 7 days.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Section 3: Fast-Track Authorizations & Team Celebrations -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-bolt"></i>
        <span>Fast-Track Authorizations & Workplace Celebrations</span>
    </div>
    <span class="dash-section-badge">1-Click Fast Track</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Dual Quick Authorization Hub Widget for Direct Reportees -->
    <?php require_once BASE_PATH . '/views/widgets/quick_authorizations.php'; ?>

    <!-- Team Celebrations & Milestones Widget -->
    <?php require_once BASE_PATH . '/views/widgets/celebrations.php'; ?>
</div>

<!-- Section 4: Public Holidays & Company Circulars -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-bullhorn"></i>
        <span>Company Circulars & Upcoming Holidays</span>
    </div>
    <span class="dash-section-badge">Advancells Organization</span>
</div>

<div class="grid-2 grid-equal-height" style="margin-bottom: 22px;">
    <!-- Company Circulars Stream -->
    <div class="card">
        <div class="card-header card-header-compact">
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">
                <i class="fa-solid fa-bullhorn" style="color: #f59e0b;"></i>
                Company Circulars & Notices
            </h3>
            <span class="badge badge-secondary"><?= count($announcements) ?> Total</span>
        </div>
        <div class="card-body" style="padding: 12px 16px;">
            <?php if (empty($announcements)): ?>
                <div class="dash-empty-scroll-box" style="height: 330px;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 26px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">No circulars posted.</p>
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

    <!-- Upcoming Public Holidays Widget -->
    <?php require_once BASE_PATH . '/views/widgets/holidays.php'; ?>
</div>

<!-- Section 5: Team Daily Attendance Roster -->
<div class="dash-section-header">
    <div class="dash-section-title">
        <i class="fa-solid fa-clipboard-user"></i>
        <span>Team Daily Attendance Roster</span>
    </div>
    <span class="dash-section-badge"><?= date('d M Y') ?></span>
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
                    backgroundColor: ['#5aa89f', '#f59e0b', '#a4247a', '#e2e8f0'],
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
