<?php
$pageTitle = 'Leave Requests Approval Queue';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <h2 class="card-title">
            <i class="fa-solid fa-clipboard-check" style="color: var(--primary);"></i>
            Leave Approvals Management (<?= count($requests) ?> Applications)
        </h2>
        <div style="display: flex; gap: 8px;">
            <a href="<?= url('leaves/approvals?status=pending') ?>" class="btn btn-sm <?= ($status === 'pending') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-clock"></i> Pending (<?= ($status === 'pending') ? count($requests) : '' ?>)
            </a>
            <a href="<?= url('leaves/approvals?status=approved') ?>" class="btn btn-sm <?= ($status === 'approved') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-circle-check"></i> Approved
            </a>
            <a href="<?= url('leaves/approvals?status=rejected') ?>" class="btn btn-sm <?= ($status === 'rejected') ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="fa-solid fa-circle-xmark"></i> Rejected
            </a>
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
                                    <?php if ($req['status'] === 'pending'): ?>
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
                                    <?php else: ?>
                                        <small style="color: var(--text-muted);"><?= e($req['approver_remarks'] ?: 'Actioned') ?></small>
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

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
