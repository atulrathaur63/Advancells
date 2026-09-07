<?php
$pageTitle = 'Monthly Attendance Sheet';
require_once BASE_PATH . '/views/layouts/header.php';

$month = $matrixData['month'];
$year = $matrixData['year'];
$monthName = $matrixData['month_name'];
$totalDays = $matrixData['total_days'];
$daysMeta = $matrixData['days_meta'];
$matrixRows = $matrixData['matrix'];
$summary = $matrixData['summary'];

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<style>
/* Attendance Matrix Specific Styles */
.matrix-container {
    position: relative;
    max-width: 100%;
    overflow-x: auto;
    background: #ffffff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
}

.matrix-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.matrix-table th, .matrix-table td {
    padding: 6px 4px;
    text-align: center;
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}

/* Sticky Headers & Columns */
.matrix-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    z-index: 10;
    font-weight: 700;
    color: var(--text-main);
    border-bottom: 2px solid var(--border-color);
}

.col-sticky-emp {
    position: sticky;
    left: 0;
    background: #ffffff !important;
    z-index: 5;
    text-align: left !important;
    padding-left: 14px !important;
    min-width: 210px;
    max-width: 240px;
    border-right: 2px solid #e2e8f0 !important;
    box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.04);
}

.matrix-table thead th.col-sticky-emp {
    z-index: 15;
    background: #f8fafc !important;
}

.col-sticky-num {
    position: sticky;
    left: 0;
    background: #ffffff !important;
    z-index: 6;
    width: 40px;
    min-width: 40px;
    border-right: 1px solid #e2e8f0 !important;
}

/* Day Header Styles */
.matrix-day-th {
    min-width: 32px;
    max-width: 36px;
    padding: 6px 2px !important;
    line-height: 1.2;
}

.matrix-day-num {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #334155;
}

.matrix-day-name {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
}

.th-weekend {
    background: #f1f5f9 !important;
    color: #64748b !important;
}
.th-weekend .matrix-day-name {
    color: #dc2626 !important;
}

.th-holiday {
    background: #f0fdfa !important;
    color: #0d9488 !important;
}
.th-holiday .matrix-day-name {
    color: #0d9488 !important;
}

.th-today {
    background: var(--brand-primary-light) !important;
    color: var(--brand-primary) !important;
}
.th-today .matrix-day-num, .th-today .matrix-day-name {
    color: var(--brand-primary) !important;
}

/* Row Hover */
.matrix-table tbody tr:hover td {
    background-color: #faf5ff;
}
.matrix-table tbody tr:hover .col-sticky-emp {
    background-color: #faf5ff !important;
}

/* Matrix Badges */
.m-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 24px;
    border-radius: 4px;
    font-size: 10.5px;
    font-weight: 700;
    cursor: default;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    user-select: none;
}

.m-badge:hover {
    transform: scale(1.18);
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    z-index: 20;
    position: relative;
}

.badge-present { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.badge-late { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.badge-halfday { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
.badge-leave { background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; }
.badge-absent { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
.badge-weekend { background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; font-weight: 500; }
.badge-holiday { background: #f0fdfa; color: #0d9488; border: 1px solid #99f6e4; }
.badge-nj { background: #f8fafc; color: #cbd5e1; border: 1px dashed #e2e8f0; font-size: 9px; }
.badge-future { background: transparent; color: #cbd5e1; font-weight: 400; }

.td-weekend {
    background-color: #fbfcfe;
}
.td-holiday {
    background-color: #f6fefc;
}

/* Summary Totals Columns */
.col-summary-th {
    background: #f1f5f9 !important;
    font-weight: 700;
    color: #1e293b;
    min-width: 44px;
}
.col-payable-th {
    background: #e0e7ff !important;
    color: #3730a3 !important;
    min-width: 60px;
}
.col-payable-td {
    background: #f5f7ff;
    font-weight: 800;
    color: #3730a3;
    font-size: 13px;
}
.col-rate-td {
    font-weight: 700;
}

/* Matrix Legend */
.matrix-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 16px 20px;
    align-items: center;
    margin-bottom: 24px;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: #475569;
}

/* Print Styling */
@media print {
    body {
        background: #ffffff !important;
    }
    .sidebar, .topbar, .tabs-nav, .filter-card, .btn-print-hide, .stats-grid {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .matrix-container {
        border: none !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    .matrix-table {
        font-size: 9px !important;
    }
    .matrix-table th, .matrix-table td {
        padding: 2px 1px !important;
    }
    .m-badge {
        width: 18px !important;
        height: 16px !important;
        font-size: 8px !important;
    }
    .col-sticky-emp {
        min-width: 120px !important;
        max-width: 140px !important;
        box-shadow: none !important;
    }
}
</style>

<!-- Attendance Top Switcher Tabs -->
<div class="tabs-nav btn-print-hide" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; align-items: center; flex-wrap: wrap;">
    <a href="<?= url('attendance/admin-logs') ?>" class="btn btn-sm btn-secondary" style="font-weight: 600;">
        <i class="fa-solid fa-clipboard-list"></i> Daily Attendance Logs
    </a>
    <a href="<?= url('attendance/sheet') ?>" class="btn btn-sm btn-primary" style="font-weight: 600;">
        <i class="fa-solid fa-table-cells"></i> Monthly Attendance Sheet
    </a>
    <a href="<?= url('attendance/regularize-approvals') ?>" class="btn btn-sm btn-secondary" style="font-weight: 600; margin-left: auto;">
        <i class="fa-solid fa-clock-rotate-left"></i> Regularizations
    </a>
</div>

<!-- Header & Quick Actions -->
<div class="page-header btn-print-hide" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-bottom: 4px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-table-cells" style="color: var(--brand-primary);"></i>
            Monthly Attendance Grid Sheet
        </h1>
        <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
            Comprehensive month-wide presence matrix, employee work records, and payroll payable days.
        </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-secondary" title="Print this monthly sheet">
            <i class="fa-solid fa-print"></i> Print Sheet
        </button>
        <a href="<?= url('attendance/sheet?export=csv&month=' . $month . '&year=' . $year . (!empty($departmentId) ? '&department_id=' . $departmentId : '')) ?>" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-file-excel"></i> Export Matrix CSV
        </a>
    </div>
</div>

<!-- Monthly Summary Stat Cards -->
<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fdf2f8; color:var(--brand-primary);">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['total_employees'] ?></div>
            <div class="stat-label">Active Workforce</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fa-solid fa-business-time"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['working_days'] ?> Days</div>
            <div class="stat-label">Working Days in Month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $summary['company_avg_attendance'] ?>%</div>
            <div class="stat-label">Avg Attendance Rate</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($summary['total_hours_logged'], 1) ?>h</div>
            <div class="stat-label">Total Hours Logged</div>
        </div>
    </div>
</div>

<!-- Filters Toolbar -->
<div class="card filter-card btn-print-hide" style="margin-bottom: 20px;">
    <div style="padding: 16px 20px; background: #ffffff; border-radius: var(--radius-lg);">
        <form method="GET" action="<?= url('attendance/sheet') ?>" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
            <div style="min-width: 140px;">
                <label class="form-label" style="margin-bottom: 6px; font-size: 11px; font-weight: 700; color: #475569;">MONTH</label>
                <select name="month" class="form-control" style="font-weight: 600;">
                    <?php foreach ($monthNames as $num => $name): ?>
                        <option value="<?= $num ?>" <?= ($month == $num) ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="min-width: 110px;">
                <label class="form-label" style="margin-bottom: 6px; font-size: 11px; font-weight: 700; color: #475569;">YEAR</label>
                <select name="year" class="form-control" style="font-weight: 600;">
                    <?php for ($y = date('Y') + 1; $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div style="min-width: 200px;">
                <label class="form-label" style="margin-bottom: 6px; font-size: 11px; font-weight: 700; color: #475569;">DEPARTMENT</label>
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($departmentId == $d['id']) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                    <i class="fa-solid fa-filter"></i> Apply Filter
                </button>
                <a href="<?= url('attendance/sheet') ?>" class="btn btn-secondary" title="Reset to Current Month">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>

            <div style="margin-left: auto; align-self: center; font-size: 13px; font-weight: 700; color: var(--brand-primary); background: var(--brand-primary-light); padding: 8px 14px; border-radius: 8px;">
                <i class="fa-regular fa-calendar-check"></i> Showing: <?= $monthName ?> <?= $year ?>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Matrix Grid -->
<div class="matrix-container">
    <table class="matrix-table">
        <thead>
            <tr>
                <th class="col-sticky-emp" style="vertical-align: middle;">
                    Employee Info
                </th>
                <?php for ($d = 1; $d <= $totalDays; $d++): 
                    $meta = $daysMeta[$d];
                    $thClass = '';
                    if ($meta['is_today']) $thClass = 'th-today';
                    elseif ($meta['is_holiday']) $thClass = 'th-holiday';
                    elseif ($meta['is_weekend']) $thClass = 'th-weekend';
                ?>
                    <th class="matrix-day-th <?= $thClass ?>" title="<?= $meta['date'] ?> (<?= $meta['day_name'] ?>)<?= $meta['holiday_title'] ? ' - ' . e($meta['holiday_title']) : '' ?>">
                        <span class="matrix-day-num"><?= sprintf('%02d', $d) ?></span>
                        <span class="matrix-day-name"><?= $meta['day_char'] ?></span>
                    </th>
                <?php endfor; ?>

                <!-- Totals Columns -->
                <th class="col-summary-th" title="Total Present Days">P</th>
                <th class="col-summary-th" title="Total Absent Days">A</th>
                <th class="col-summary-th" title="Total Half Days">HD</th>
                <th class="col-summary-th" title="Approved Leaves">L</th>
                <th class="col-payable-th" title="Calculated Payable Days for Payroll">Payable Days</th>
                <th class="col-summary-th" title="Effective Attendance Percentage">Att %</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($matrixRows)): ?>
                <tr>
                    <td colspan="<?= $totalDays + 7 ?>" style="padding: 40px; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-user-slash" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                        No active employees found for this department or period.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($matrixRows as $row): 
                    $emp = $row['employee'];
                    $st = $row['stats'];
                    $fullName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
                    $avatar = $emp['avatar'] ? url('uploads/avatars/' . $emp['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=fdf2f8&color=93206c&bold=true';
                ?>
                    <tr>
                        <td class="col-sticky-emp">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?= $avatar ?>" alt="<?= e($fullName) ?>" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                <div style="overflow: hidden; text-overflow: ellipsis; line-height: 1.2;">
                                    <div style="font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <a href="<?= url('employees/view?id=' . $emp['id']) ?>" style="color: inherit; text-decoration: none;" hover="text-decoration: underline;">
                                            <?= e($fullName) ?>
                                        </a>
                                    </div>
                                    <div style="font-size: 10px; color: #64748b; display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                                        <span style="font-weight: 600; color: #93206c;"><?= e($emp['emp_code']) ?></span>
                                        <span>•</span>
                                        <span><?= e($emp['department_name'] ?? 'Gen') ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <?php for ($d = 1; $d <= $totalDays; $d++): 
                            $meta = $daysMeta[$d];
                            $cell = $row['days'][$d];
                            $tdClass = '';
                            if ($meta['is_weekend']) $tdClass = 'td-weekend';
                            elseif ($meta['is_holiday']) $tdClass = 'td-holiday';
                        ?>
                            <td class="<?= $tdClass ?>" title="<?= e($cell['tooltip']) ?>">
                                <span class="m-badge <?= e($cell['badge']) ?>">
                                    <?= e($cell['code']) ?>
                                </span>
                            </td>
                        <?php endfor; ?>

                        <!-- Employee Totals -->
                        <td style="font-weight: 700; color: #059669;"><?= $st['present'] ?></td>
                        <td style="font-weight: 700; color: #e11d48;"><?= $st['absent'] ?></td>
                        <td style="font-weight: 700; color: #ea580c;"><?= $st['half_day'] ?></td>
                        <td style="font-weight: 700; color: #4f46e5;"><?= $st['leave'] ?></td>
                        <td class="col-payable-td"><?= $st['payable_days'] ?></td>
                        <td class="col-rate-td">
                            <?php 
                                $pct = $st['attendance_rate'];
                                $pctColor = ($pct >= 85) ? '#059669' : (($pct >= 70) ? '#d97706' : '#e11d48');
                            ?>
                            <span style="color: <?= $pctColor ?>;"><?= $pct ?>%</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Color Legend & Formula Note -->
<div class="matrix-legend btn-print-hide">
    <div style="font-weight: 800; font-size: 12px; color: #0f172a; margin-right: 8px;">
        <i class="fa-solid fa-circle-info text-primary"></i> MATRIX LEGEND:
    </div>
    <div class="legend-item">
        <span class="m-badge badge-present">P</span> Present (1.0)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-late">P</span> Late Punch (1.0)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-halfday">HD</span> Half Day (0.5)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-leave">L</span> Approved Leave (1.0)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-holiday">H</span> Gazetted Holiday (1.0)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-weekend">W</span> Weekend (1.0)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-absent">A</span> Absent (0.0)
    </div>
    <div class="legend-item">
        <span class="m-badge badge-nj">NJ</span> Not Yet Joined
    </div>
    <div style="margin-left: auto; font-size: 11.5px; color: #64748b; background: #f8fafc; padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
        <strong style="color: #3730a3;"><i class="fa-solid fa-calculator"></i> Payroll Payable Days Rule:</strong> 
        Present + Leaves + Holidays + Weekends + (Half-Day &times; 0.5)
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
