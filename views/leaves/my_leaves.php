<?php
$pageTitle = 'My Leave Applications & Balances';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Leave Balances Grid -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <?php foreach ($balances as $b): ?>
        <?php if ($b['leave_type_code'] !== 'LOP'): ?>
            <div class="stat-card">
                <div class="stat-icon <?= ($b['leave_type_code'] === 'CL') ? 'green' : (($b['leave_type_code'] === 'SL') ? 'rose' : 'indigo') ?>">
                    <i class="fa-solid <?= ($b['leave_type_code'] === 'CL') ? 'fa-umbrella-beach' : (($b['leave_type_code'] === 'SL') ? 'fa-stethoscope' : 'fa-plane-departure') ?>"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $b['available'] ?> <span style="font-size: 13px; font-weight: 500; color: #94a3b8;">/ <?= $b['total_allocated'] ?></span></div>
                    <div class="stat-label"><?= e($b['leave_type_name']) ?> (<?= e($b['leave_type_code']) ?>)</div>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Used: <?= $b['used'] ?> • Pending: <?= $b['pending'] ?></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="grid-2">
    <!-- Leave Application Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fa-solid fa-calendar-plus" style="color: var(--primary);"></i>
                Apply for Leave
            </h2>
        </div>
        <div class="card-body">
            <form action="<?= url('leaves/apply') ?>" method="POST" id="leaveApplyForm">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="leave_type_id">Leave Category *</label>
                    <select name="leave_type_id" id="leave_type_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> (<?= e($t['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="from_date">From Date *</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="to_date">To Date *</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13.5px; font-weight: 500;">
                        <input type="checkbox" name="is_half_day" id="is_half_day" value="1" onchange="toggleHalfDay(this)">
                        Apply for Half Day
                    </label>
                </div>

                <div class="form-group" id="half_day_select" style="display: none;">
                    <label class="form-label">Half Day Session</label>
                    <select name="half_day_type" class="form-control">
                        <option value="first_half">First Half (Morning)</option>
                        <option value="second_half">Second Half (Afternoon)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Leave *</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Provide reason for time off..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Leave Application
                </button>
            </form>
        </div>
    </div>

    <!-- My Leave Requests History -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: #64748b;"></i>
                My Leave History (<?= count($requests) ?>)
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($requests)): ?>
                <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-calendar-check" style="font-size: 36px; color: #10b981; margin-bottom: 8px;"></i>
                    <p>No leave applications recorded for this year.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($req['leave_type_code']) ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= $req['is_half_day'] ? 'Half Day' : 'Full Day' ?></small>
                                    </td>
                                    <td>
                                        <?= format_date($req['from_date'], 'd M') ?> - <?= format_date($req['to_date'], 'd M') ?><br>
                                        <small><strong><?= $req['total_days'] ?> day(s)</strong></small>
                                    </td>
                                    <td><small><?= e($req['reason']) ?></small></td>
                                    <td>
                                        <?= status_badge($req['status']) ?>
                                        <?php if (!empty($req['approver_remarks'])): ?>
                                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                Note: <?= e($req['approver_remarks']) ?>
                                            </div>
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
</div>

<script>
function toggleHalfDay(checkbox) {
    const halfDayBox = document.getElementById('half_day_select');
    const toDateInput = document.getElementById('to_date');
    if (checkbox.checked) {
        halfDayBox.style.display = 'block';
        toDateInput.readOnly = true;
        toDateInput.style.backgroundColor = '#f1f5f9';
        toDateInput.style.cursor = 'not-allowed';
        toDateInput.value = document.getElementById('from_date').value;
    } else {
        halfDayBox.style.display = 'none';
        toDateInput.readOnly = false;
        toDateInput.style.backgroundColor = '';
        toDateInput.style.cursor = '';
    }
}
document.getElementById('from_date').addEventListener('change', function() {
    if (document.getElementById('is_half_day').checked) {
        document.getElementById('to_date').value = this.value;
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
