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
<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['present'] ?></div>
            <div class="stat-label">Days Present</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['late'] ?></div>
            <div class="stat-label">Late Arrivals</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon rose"><i class="fa-solid fa-circle-half-stroke"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['half_day'] ?></div>
            <div class="stat-label">Half-Days</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-plane-departure"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['leave'] ?></div>
            <div class="stat-label">Approved Leaves</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-stopwatch"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['total_hours'] ?>h</div>
            <div class="stat-label">Total Logged Hours</div>
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
                        $isWeekend = ($dayOfWeek === 'Sat' || $dayOfWeek === 'Sun');
                        $record = $recordsByDate[$dateStr] ?? null;
                    ?>
                        <tr style="<?= $isWeekend ? 'background: #f8fafc;' : '' ?>">
                            <td><strong><?= date('d M Y', strtotime($dateStr)) ?></strong></td>
                            <td style="color: var(--text-muted);"><?= $dayOfWeek ?></td>
                            <td><?= (!empty($record['punch_in'])) ? format_time($record['punch_in']) : ($isWeekend ? '<span style="color:#94a3b8;">Weekend</span>' : '--:--') ?></td>
                            <td><?= (!empty($record['punch_out'])) ? format_time($record['punch_out']) : ($isWeekend ? '<span style="color:#94a3b8;">Weekend</span>' : '--:--') ?></td>
                            <td><?= (!empty($record['total_hours']) && $record['total_hours'] > 0) ? $record['total_hours'] . ' hrs' : '--' ?></td>
                            <td>
                                <?php if ($record): ?>
                                    <?= status_badge($record['status']) ?>
                                    <?php if ($record['is_regularized']): ?>
                                        <span class="badge badge-purple" title="Regularized">Reg.</span>
                                    <?php endif; ?>
                                <?php elseif ($isWeekend): ?>
                                    <span class="badge badge-secondary">Weekend</span>
                                <?php elseif (strtotime($dateStr) < strtotime(date('Y-m-d'))): ?>
                                    <span class="badge badge-danger">Absent</span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Upcoming</span>
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
