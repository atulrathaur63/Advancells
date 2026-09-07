<?php
$pageTitle = 'Company Attendance Logs';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Today Overview Statistics -->
<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayStats['present'] ?></div>
            <div class="stat-label">Present On Time</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayStats['late'] ?></div>
            <div class="stat-label">Late Arrivals</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-plane-departure"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayStats['leave'] ?></div>
            <div class="stat-label">On Approved Leave</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon rose"><i class="fa-solid fa-user-xmark"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayStats['not_logged'] ?></div>
            <div class="stat-label">Pending / Unlogged</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <h2 class="card-title"><i class="fa-solid fa-calendar-days text-primary"></i> Attendance Master Logs</h2>
        <div style="display: flex; gap: 10px;">
            <a href="<?= url('attendance/admin-logs?export=csv' . (!empty($filters['date']) ? '&date=' . $filters['date'] : '') . (!empty($filters['department_id']) ? '&department_id=' . $filters['department_id'] : '') . (!empty($filters['status']) ? '&status=' . $filters['status'] : '')) ?>" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-file-arrow-down"></i> Export Log CSV
            </a>
            <a href="<?= url('attendance/regularize-approvals') ?>" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-clock-rotate-left"></i> Review Regularizations
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="padding: 16px 24px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
        <form method="GET" action="<?= url('attendance/admin-logs') ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="width: 180px;">
                <label class="form-label" style="margin-bottom: 4px; font-size: 11px;">Select Date</label>
                <input type="date" name="date" class="form-control" value="<?= e($filters['date']) ?>">
            </div>

            <div style="width: 200px;">
                <label class="form-label" style="margin-bottom: 4px; font-size: 11px;">Department</label>
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] == $d['id']) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width: 160px;">
                <label class="form-label" style="margin-bottom: 4px; font-size: 11px;">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="present" <?= ($filters['status'] === 'present') ? 'selected' : '' ?>>Present</option>
                    <option value="late" <?= ($filters['status'] === 'late') ? 'selected' : '' ?>>Late</option>
                    <option value="half_day" <?= ($filters['status'] === 'half_day') ? 'selected' : '' ?>>Half Day</option>
                    <option value="leave" <?= ($filters['status'] === 'leave') ? 'selected' : '' ?>>On Leave</option>
                    <option value="absent" <?= ($filters['status'] === 'absent') ? 'selected' : '' ?>>Absent</option>
                </select>
            </div>

            <div style="align-self: flex-end;">
                <button type="submit" class="btn btn-secondary">Filter Logs</button>
                <a href="<?= url('attendance/admin-logs') ?>" class="btn btn-sm btn-secondary" style="padding: 9px 12px;">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (empty($logs)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                No attendance logs found for the selected criteria.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Punch In</th>
                            <th>Punch Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Regularized</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><strong><?= format_date($log['date']) ?></strong></td>
                                <td>
                                    <strong><?= e($log['employee_name']) ?></strong><br>
                                    <small style="color:var(--text-muted); font-family:monospace;"><?= e($log['emp_code']) ?></small>
                                </td>
                                <td><?= e($log['department_name'] ?? 'General') ?></td>
                                <td><?= $log['punch_in'] ? format_time($log['punch_in']) : '<span style="color:#94a3b8;">--:--</span>' ?></td>
                                <td><?= $log['punch_out'] ? format_time($log['punch_out']) : '<span style="color:#94a3b8;">--:--</span>' ?></td>
                                <td><strong><?= $log['total_hours'] > 0 ? $log['total_hours'] . 'h' : '--' ?></strong></td>
                                <td><?= status_badge($log['status']) ?></td>
                                <td>
                                    <?php if ($log['is_regularized']): ?>
                                        <span class="badge badge-purple">Yes</span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><small style="color:var(--text-muted);"><?= e($log['notes'] ?? '') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
