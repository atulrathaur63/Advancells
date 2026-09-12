<?php
$pageTitle = 'Attendance Regularization Approvals';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-list-check text-primary"></i> Regularization Approvals Queue (<?= $pagination['total_items'] ?? count($requests) ?> Requests)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($requests)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-circle-check text-success" style="font-size: 28px; margin-bottom: 8px; display: block;"></i>
                No attendance regularizations pending review!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Requested In / Out</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['employee_name']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= e($r['emp_code']) ?> • <?= e($r['department_name']) ?></small>
                                </td>
                                <td><strong><?= format_date($r['date']) ?></strong></td>
                                <td>
                                    <?= format_time($r['requested_punch_in']) ?> - <?= format_time($r['requested_punch_out']) ?>
                                </td>
                                <td><small><?= e($r['reason']) ?></small></td>
                                <td><?= status_badge($r['status']) ?></td>
                                <td>
                                    <?php
                                        $isSelfReq = Auth::role() !== 'super_admin' && (int)($r['employee_id'] ?? 0) === (int)Auth::employeeId();
                                    ?>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <?php if ($isSelfReq): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 5px; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                                <i class="fa-solid fa-lock" style="font-size: 10px;"></i>
                                                Your Request — Awaiting Super Admin
                                            </span>
                                        <?php else: ?>
                                            <div style="display: flex; gap: 6px;">
                                                <!-- Approve Form -->
                                                <form action="<?= url('attendance/approve-regularization') ?>" method="POST" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="admin_remarks" value="Approved by manager">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirmAction('Approve this attendance regularization?')">
                                                        Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Form -->
                                                <form action="<?= url('attendance/reject-regularization') ?>" method="POST" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="admin_remarks" value="Discrepancy in explanation">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirmAction('Reject this attendance regularization?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted);"><?= e($r['admin_remarks'] ?: 'Actioned') ?></small>
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
