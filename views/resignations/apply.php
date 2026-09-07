<?php
$pageTitle = 'Submit Resignation';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-door-open text-primary"></i> Formal Resignation Submission</h2>
    </div>
    <div class="card-body">
        <?php if ($existing): ?>
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 8px; color: #f59e0b;"><i class="fa-solid fa-hourglass-half"></i></div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">Resignation Request Under Process</h3>
                <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 16px;">
                    Your formal resignation was submitted on <strong><?= format_date($existing['resignation_date']) ?></strong>.
                </p>
                <div style="margin-bottom: 12px;">
                    Current Status: <?= status_badge($existing['status']) ?>
                </div>
                <p style="font-size: 13px; color: var(--text-muted);">
                    Desired Last Working Day: <strong><?= format_date($existing['desired_last_working_day']) ?></strong>
                    <?php if (!empty($existing['approved_last_working_day'])): ?>
                        <br>Approved Last Working Day: <strong style="color: #10b981;"><?= format_date($existing['approved_last_working_day']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                Please note that submitting this form initiates the standard notice period as per your employment contract. Your reporting manager and HR department will be notified immediately.
            </p>

            <form action="<?= url('resignations/apply') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="desired_last_working_day">Desired Last Working Day *</label>
                    <input type="date" name="desired_last_working_day" id="desired_last_working_day" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Resignation *</label>
                    <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Personal reasons, higher studies, career growth, relocation..." required></textarea>
                </div>

                <button type="submit" class="btn btn-danger" style="width: 100%; padding: 12px;" onclick="return confirmAction('Are you certain you wish to tender your formal resignation?')">
                    Submit Formal Resignation
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
