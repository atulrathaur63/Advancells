<?php
$pageTitle = 'Leave Requests Approval Queue';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <h2 class="card-title">
            <i class="fa-solid fa-clipboard-check" style="color: var(--primary);"></i>
            Leave Approvals Management (<?= $pagination['total_items'] ?? count($requests) ?> Applications)
        </h2>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="<?= url('leaves/approvals?status=pending') ?>" class="btn btn-sm <?= ($status === 'pending') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-clock"></i> Pending <?= ($status === 'pending') ? '(' . ($pagination['total_items'] ?? count($requests)) . ')' : '' ?>
            </a>
            <a href="<?= url('leaves/approvals?status=approved') ?>" class="btn btn-sm <?= ($status === 'approved') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-circle-check"></i> Approved <?= ($status === 'approved') ? '(' . ($pagination['total_items'] ?? count($requests)) . ')' : '' ?>
            </a>
            <a href="<?= url('leaves/approvals?status=rejected') ?>" class="btn btn-sm <?= ($status === 'rejected') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-circle-xmark"></i> Rejected <?= ($status === 'rejected') ? '(' . ($pagination['total_items'] ?? count($requests)) . ')' : '' ?>
            </a>
            <a href="<?= url('leaves/approvals?status=cancelled') ?>" class="btn btn-sm <?= ($status === 'cancelled') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-ban"></i> Cancelled <?= ($status === 'cancelled') ? '(' . ($pagination['total_items'] ?? count($requests)) . ')' : '' ?>
            </a>

            <?php if (Auth::isHR()): ?>
                <form action="<?= url('leaves/sync-balances') ?>" method="POST" style="display: inline-block; margin: 0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="year" value="<?= date('Y') ?>">
                    <button type="submit" class="btn btn-sm btn-secondary" title="Auto-recalculate & credit leaves for all active employees for current month">
                        <i class="fa-solid fa-arrows-rotate"></i> Sync Monthly Accruals
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (empty($requests)): ?>
            <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-clipboard-check" style="font-size: 38px; color: #10b981; margin-bottom: 10px;"></i>
                <h3>No leave applications found</h3>
                <p style="font-size: 13px; margin-top: 4px;">There are no leave requests currently marked as <strong><?= ucfirst($status) ?></strong>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Category</th>
                            <th>Date Range</th>
                            <th>Total Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                            <?= strtoupper(substr($req['employee_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= e($req['employee_name']) ?></strong><br>
                                            <small style="color:var(--text-muted); font-family:monospace;"><?= e($req['emp_code']) ?> • <?= e($req['department_name']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-purple"><?= e($req['leave_type_name']) ?></span><br>
                                    <small style="color:var(--text-muted);"><?= $req['is_half_day'] ? 'Half Day' : 'Full Day' ?></small>
                                </td>
                                <td>
                                    <strong><?= format_date($req['from_date']) ?></strong> to <strong><?= format_date($req['to_date']) ?></strong>
                                </td>
                                <td>
                                    <strong style="color: var(--primary); font-size: 15px;"><?= $req['total_days'] ?></strong> day(s)
                                </td>
                                <td>
                                    <div style="max-width: 250px; font-size: 13px;"><?= e($req['reason']) ?></div>
                                </td>
                                <td>
                                    <?= status_badge($req['status']) ?>
                                    <?php if (!empty($req['approver_name'])): ?>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                            By <?= e($req['approver_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $isSelfRequest = Auth::role() !== 'super_admin' && (int)($req['employee_id'] ?? 0) === (int)Auth::employeeId();
                                    ?>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <?php if ($isSelfRequest): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 5px; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 700; white-space: nowrap;">
                                                <i class="fa-solid fa-lock" style="font-size: 10px;"></i>
                                                Your Request — Awaiting Super Admin
                                            </span>
                                        <?php else: ?>
                                            <div style="display: flex; gap: 6px;">
                                                <!-- Approve Button Form -->
                                                <form action="<?= url('leaves/approve') ?>" method="POST" style="display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="approver_remarks" value="Approved">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirmAction('Approve this leave request?')">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Button Form -->
                                                <form action="<?= url('leaves/reject') ?>" method="POST" style="display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="approver_remarks" value="Operational requirements">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirmAction('Reject this leave request?')">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($req['status'] === 'approved' && (Auth::isHR() || Auth::role() === 'super_admin')): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                            <small style="color: var(--text-muted); font-size: 11px;"><?= e($req['approver_remarks'] ?: 'Approved') ?></small>
                                            <form action="<?= url('leaves/cancel') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Cancel this approved leave and refund <?= $req['total_days'] ?> day(s)?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="reason" value="Cancelled by HR/Management">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 3px 7px; font-size: 10.5px; font-weight: 700;" title="Cancel approved leave and restore balance">
                                                    <i class="fa-solid fa-ban"></i> Cancel
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted); font-size: 12px;"><?= e($req['approver_remarks'] ?: ucfirst($req['status'])) ?></small>
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
