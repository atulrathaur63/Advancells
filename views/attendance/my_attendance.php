<?php
$pageTitle = 'My Monthly Attendance & Timesheet';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Month Selector Card -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 20px; font-weight: 700;">
                <i class="fa-solid fa-calendar-check text-primary"></i> Timesheet for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>
            </h2>
            <p style="color: var(--text-muted); font-size: 13px;">Review your daily punches, hours tracked, and regularization requests.</p>
        </div>

        <form method="GET" action="<?= url('attendance/my-attendance') ?>" style="display: flex; gap: 8px;">
            <select name="month" class="form-control" style="width: 140px;">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="year" class="form-control" style="width: 100px;">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            <a href="<?= url('attendance/regularize') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Regularize Missed Punch</a>
        </form>
    </div>
</div>

<!-- Monthly Summary Stats & Chart -->
<div class="stats-grid" style="margin-bottom: 22px;">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Days Present</span>
            <span class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></span>
        </div>
        <div class="stat-card-value"><?= $summary['present'] ?></div>
        <div class="stat-card-sub">
            <span style="color: var(--brand-teal); font-weight: 600;"><i class="fa-solid fa-check"></i> Verified</span>
            <span>Current month</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Late Arrivals</span>
            <span class="stat-card-icon amber"><i class="fa-solid fa-clock"></i></span>
        </div>
        <div class="stat-card-value"><?= $summary['late'] ?></div>
        <div class="stat-card-sub">
            <span style="color: <?= $summary['late'] > 0 ? '#d97706' : '#059669' ?>; font-weight: 600;">
                <i class="fa-solid <?= $summary['late'] > 0 ? 'fa-clock' : 'fa-circle-check' ?>"></i>
                <?= $summary['late'] > 0 ? 'After 09:15 AM' : 'Punctual' ?>
            </span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Half-Days</span>
            <span class="stat-card-icon rose"><i class="fa-solid fa-circle-half-stroke"></i></span>
        </div>
        <div class="stat-card-value"><?= $summary['half_day'] ?></div>
        <div class="stat-card-sub">
            <span>&lt; 4.5 working hrs</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Approved Leaves</span>
            <span class="stat-card-icon purple"><i class="fa-solid fa-plane-departure"></i></span>
        </div>
        <div class="stat-card-value"><?= $summary['leave'] ?></div>
        <div class="stat-card-sub">
            <span style="color: var(--brand-plum); font-weight: 600;"><i class="fa-solid fa-calendar-check"></i> Logged</span>
            <span>Month total</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Total Logged Hours</span>
            <span class="stat-card-icon blue"><i class="fa-solid fa-stopwatch"></i></span>
        </div>
        <div class="stat-card-value"><?= $summary['total_hours'] ?>h</div>
        <div class="stat-card-sub">
            <span style="color: var(--brand-cyan); font-weight: 600;"><i class="fa-solid fa-hourglass-half"></i> Cumulative</span>
            <span>Shift duration</span>
        </div>
    </div>
</div>

<!-- Daily Log Table for the Month -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list-check text-primary"></i> Daily Punch Log</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Punch In</th>
                        <th>Punch Out</th>
                        <th>Total Hours</th>
                        <th>Status</th>
                        <th>Notes / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    for ($d = 1; $d <= $daysInMonth; $d++):
                        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $dayOfWeek = date('D', strtotime($dateStr));
                        $isWeekend = is_weekend($dateStr);
                        $dayLabel = $dayOfWeek;
                        if ($dayOfWeek === 'Sat') {
                            $satNum = (int)ceil($d / 7);
                            $ordinal = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th'][$satNum] ?? ($satNum . 'th');
                            $dayLabel = "Sat ({$ordinal})";
                        }
                        $record = $recordsByDate[$dateStr] ?? null;
                    ?>
                        <tr style="<?= $isWeekend ? 'background: #f8fafc;' : '' ?>">
                            <td><strong style="color: var(--text-main);"><?= date('d M Y', strtotime($dateStr)) ?></strong></td>
                            <td style="color: var(--text-muted); font-weight: 500;"><?= $dayLabel ?></td>
                            <td><?= (!empty($record['punch_in'])) ? '<strong style="color: var(--brand-teal);">' . format_time($record['punch_in']) . '</strong>' : ($isWeekend ? '<span style="color:#cbd5e1;">--:--</span>' : '<span style="color:#94a3b8;">--:--</span>') ?></td>
                            <td><?= (!empty($record['punch_out'])) ? '<strong style="color: #64748b;">' . format_time($record['punch_out']) . '</strong>' : ($isWeekend ? '<span style="color:#cbd5e1;">--:--</span>' : '<span style="color:#94a3b8;">--:--</span>') ?></td>
                            <td><?= (!empty($record['total_hours']) && $record['total_hours'] > 0) ? '<strong style="color: var(--brand-cyan); font-weight: 700;">' . $record['total_hours'] . ' hrs</strong>' : '<span style="color:#cbd5e1;">--</span>' ?></td>
                            <td>
                                <?php if ($record): ?>
                                    <?= status_badge($record['status']) ?>
                                    <?php if ($record['is_regularized']): ?>
                                        <span class="badge badge-purple" title="Regularized Missed Punch">Reg.</span>
                                    <?php endif; ?>
                                <?php elseif ($isWeekend): ?>
                                    <span class="badge badge-secondary">Week Off</span>
                                <?php elseif (strtotime($dateStr) < strtotime(date('Y-m-d'))): ?>
                                    <span class="badge badge-danger">Absent</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="opacity: 0.65;">Upcoming</span>
                                <?php endif; ?>
                            </td>
                            <td><small style="color:var(--text-muted);"><?= e($record['notes'] ?? '') ?></small></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
