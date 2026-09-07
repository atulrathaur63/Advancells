<?php
$pageTitle = 'Web Attendance Punch';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div style="max-width: 680px; margin: 0 auto;">
    <div class="card punch-card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="badge" style="background: rgba(255,255,255,0.15); color: #fdf4ff;">
                    <i class="fa-solid fa-business-time" style="margin-right: 4px;"></i> Daily Shift Tracker
                </span>
                <h2 style="font-family: var(--font-heading); font-size: 20px; font-weight: 800; margin-top: 8px;">
                    Advancells Attendance Console
                </h2>
            </div>
            <div class="date-live" style="font-size: 13px; font-weight: 700; color: #a5f3fc;">Today</div>
        </div>

        <div style="text-align: center; padding: 20px 0;">
            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #cbd5e1; margin-bottom: 6px;">
                Current System Time
            </div>
            <div class="punch-timer clock-live" style="font-size: 52px; font-weight: 900;">--:--:--</div>
        </div>

        <form action="<?= url('attendance/punch') ?>" method="POST" id="mainPunchForm">
            <?= csrf_field() ?>

            <?php if (!$todayRecord || empty($todayRecord['punch_in'])): ?>
                <input type="hidden" name="punch_type" value="in">
                <div class="form-group">
                    <label style="color: #cbd5e1; font-size: 13px; margin-bottom: 6px; display: block;">Punch-In Note (Optional):</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Lab morning round / Clinical trial setup..." style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff;">
                </div>
                <button type="submit" class="btn btn-success btn-lg" style="width: 100%; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-play"></i> Clock In (Start Work Shift)
                </button>
            <?php elseif (empty($todayRecord['punch_out'])): ?>
                <input type="hidden" name="punch_type" value="out">
                <div style="background: rgba(255,255,255,0.08); padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Clocked In At:</span>
                        <strong style="color: #6ee7b7; font-size: 15px;"><?= format_time($todayRecord['punch_in']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Status:</span>
                        <span><?= status_badge($todayRecord['status']) ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label style="color: #cbd5e1; font-size: 13px; margin-bottom: 6px; display: block;">Punch-Out Summary (Optional):</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Completed trial data entry & sample storage..." style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff;">
                </div>
                <button type="submit" class="btn btn-danger btn-lg" style="width: 100%; font-size: 16px; font-weight: 700;">
                    <i class="fa-solid fa-stop"></i> Clock Out (End Work Shift)
                </button>
            <?php else: ?>
                <div style="background: rgba(255,255,255,0.08); padding: 20px; border-radius: var(--radius-md); text-align: center;">
                    <i class="fa-solid fa-circle-check" style="font-size: 36px; color: #10b981; margin-bottom: 8px;"></i>
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 6px;">Day Shift Completed!</h3>
                    <p style="color: #cbd5e1; font-size: 14px;">
                        Clocked In: <strong><?= format_time($todayRecord['punch_in']) ?></strong> • Clocked Out: <strong><?= format_time($todayRecord['punch_out']) ?></strong>
                    </p>
                    <div style="margin-top: 10px; font-size: 16px; font-weight: 800; color: #38bdf8;">
                        Total Logged Hours: <?= $todayRecord['total_hours'] ?> hrs
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Helpful guidelines -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i>
                Attendance Policy Reminders
            </h3>
        </div>
        <div class="card-body">
            <ul style="padding-left: 20px; color: var(--text-muted); font-size: 13.5px; line-height: 1.8;">
                <li>Standard workday timing is <strong>09:00 AM to 06:00 PM</strong>.</li>
                <li>Grace arrival time is permitted until <strong>09:15 AM</strong>. Logins after 09:15 AM are marked as <em>Late</em>.</li>
                <li>Working under 4.5 hours is automatically classified as a <em>Half-Day</em>.</li>
                <li>Forgot to punch? You can apply for a punch regularization in the <a href="<?= url('attendance/regularize') ?>" style="color: var(--primary); font-weight: 700;">Regularization portal</a>.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
