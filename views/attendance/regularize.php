<?php
$pageTitle = 'Attendance Regularization Request';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="grid-2">
    <!-- Regularization Application Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-calendar-plus text-primary"></i> Request Attendance Regularization</h2>
        </div>
        <div class="card-body">
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                Use this form if you forgot to punch in/out, experienced a biometric machine glitch, or were travelling on company business.
            </p>

            <form action="<?= url('attendance/regularize') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="date">Date to Regularize *</label>
                    <input type="date" name="date" id="date" class="form-control" max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="requested_punch_in">Requested Punch In *</label>
                        <input type="time" name="requested_punch_in" id="requested_punch_in" class="form-control" value="09:00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="requested_punch_out">Requested Punch Out *</label>
                        <input type="time" name="requested_punch_out" id="requested_punch_out" class="form-control" value="18:00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Regularization *</label>
                    <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Detailed explanation (e.g. Onsite client meeting / Lab bio-reactor emergency / Network outage)..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Submit Regularization Request <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- My Regularization Requests History -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left text-primary"></i> My Submitted Requests (<?= count($regularizations) ?>)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($regularizations)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                    No regularization requests submitted yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Requested Times</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($regularizations as $reg): ?>
                                <tr>
                                    <td><strong><?= format_date($reg['date']) ?></strong></td>
                                    <td>
                                        <?= format_time($reg['requested_punch_in']) ?> - <?= format_time($reg['requested_punch_out']) ?>
                                    </td>
                                    <td><small><?= e($reg['reason']) ?></small></td>
                                    <td>
                                        <?= status_badge($reg['status']) ?>
                                        <?php if (!empty($reg['admin_remarks'])): ?>
                                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                Note: <?= e($reg['admin_remarks']) ?>
                                            </div>
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
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
