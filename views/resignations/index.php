<?php
$pageTitle = 'Exit & Resignation Management';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-door-open text-primary"></i> Exit & Resignation Pipeline (<?= count($resignations) ?> Records)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($resignations)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-circle-check text-success" style="font-size: 28px; margin-bottom: 8px; display: block;"></i>
                No active resignations or exit clearances in progress.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Submitted On</th>
                            <th>Desired Last Day</th>
                            <th>Approved Last Day</th>
                            <th>Assets Clearance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resignations as $r): ?>
                            <?php $pendingCount = count($r['pending_assets'] ?? []); ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['employee_name']) ?></strong><br>
                                    <small style="color:var(--text-muted); font-family:monospace;"><?= e($r['emp_code']) ?> • <?= e($r['department_name']) ?></small>
                                </td>
                                <td><?= format_date($r['resignation_date']) ?></td>
                                <td><strong><?= format_date($r['desired_last_working_day']) ?></strong></td>
                                <td><?= $r['approved_last_working_day'] ? format_date($r['approved_last_working_day']) : '<span style="color:#94a3b8;">Pending Approval</span>' ?></td>
                                <td>
                                    <?php if ($pendingCount === 0): ?>
                                        <span class="badge badge-success" style="font-size: 11px;">
                                            <i class="fa-solid fa-circle-check"></i> No Assets Held
                                        </span>
                                    <?php else: ?>
                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span class="badge badge-danger" style="font-size: 11px; align-self: flex-start;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> <?= $pendingCount ?> Asset(s) Pending
                                            </span>
                                            <div style="font-size: 11px; color: #dc2626; max-width: 170px; line-height: 1.3;">
                                                <?php foreach (array_slice($r['pending_assets'], 0, 2) as $pa): ?>
                                                    • <?= e($pa['name']) ?><br>
                                                <?php endforeach; ?>
                                                <?php if ($pendingCount > 2): ?>
                                                    <span style="color: #64748b;">+<?= $pendingCount - 2 ?> more...</span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="<?= url('employees/view?id=' . $r['employee_id'] . '&tab=assets') ?>" style="font-size: 11px; color: #2563eb; text-decoration: underline; font-weight: 600;">
                                                Manage Returns &rarr;
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= status_badge($r['status']) ?></td>
                                <td>
                                    <?php if ($r['status'] !== 'completed' && $r['status'] !== 'rejected'): ?>
                                        <?php if (!Auth::isHR() && in_array($r['status'], ['manager_approved', 'hr_approved'], true)): ?>
                                            <small style="color: var(--text-muted);"><i class="fa-solid fa-clock"></i> Awaiting HR</small>
                                        <?php else: ?>
                                            <form action="<?= url('resignations/update') ?>" method="POST" style="display: flex; gap: 4px; align-items: center;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">

                                                <select name="status" class="form-control" style="padding: 4px 8px; font-size: 12px; width: 130px;">
                                                    <option value="manager_approved" <?= ($r['status'] === 'manager_approved') ? 'selected' : '' ?>>Manager OK</option>
                                                    <?php if (Auth::isHR()): ?>
                                                        <option value="hr_approved" <?= ($r['status'] === 'hr_approved') ? 'selected' : '' ?>>HR Clearance</option>
                                                        <option value="completed" <?= ($r['status'] === 'completed') ? 'selected' : '' ?>>Settled/Exited</option>
                                                    <?php endif; ?>
                                                    <option value="rejected" <?= ($r['status'] === 'rejected') ? 'selected' : '' ?>>Reject</option>
                                                </select>

                                                <input type="hidden" name="approved_last_working_day" value="<?= $r['desired_last_working_day'] ?>">

                                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmAction('Update status for this exit request?')">
                                                    Update
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted);">Process Closed</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (isset($pagination)): ?>
                <?= render_pagination($pagination) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
