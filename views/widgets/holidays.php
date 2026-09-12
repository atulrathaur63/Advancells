<?php
/**
 * Upcoming Holidays Widget
 * Displays countdown to next public holiday and mini-timeline of upcoming festive breaks
 */
$upcomingHolidays = $upcomingHolidays ?? [];
$nextHoliday = !empty($upcomingHolidays) ? $upcomingHolidays[0] : null;

$daysUntilNext = null;
if ($nextHoliday) {
    $now = new DateTime('today');
    $hDate = new DateTime($nextHoliday['holiday_date']);
    $diff = $now->diff($hDate);
    $daysUntilNext = (int)$diff->format('%r%a');
}
?>

<div class="card holiday-compact-card" id="holidaysWidget">
    <div class="card-header card-header-compact">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calendar-days text-primary" style="font-size: 15px;"></i>
            <h3 class="card-title" style="font-size: 13.5px; margin: 0;">Upcoming Holidays</h3>
            <?php if ($daysUntilNext !== null): ?>
                <span class="badge badge-purple" style="font-size: 10px; font-weight: 700; padding: 1.5px 7px;">
                    Next in <?= $daysUntilNext === 0 ? 'Today!' : ($daysUntilNext === 1 ? 'Tomorrow' : "{$daysUntilNext} days") ?>
                </span>
            <?php endif; ?>
        </div>
        <a href="<?= url('leaves/holidays') ?>" class="btn btn-sm btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">Full Calendar</a>
    </div>

    <div class="card-body holiday-compact-body" style="padding: 12px 16px;">
        <?php if (empty($upcomingHolidays)): ?>
            <div class="dash-empty-scroll-box" style="height: 250px;">
                <i class="fa-regular fa-calendar-check" style="font-size: 28px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">No upcoming public holidays for this year.</p>
            </div>
        <?php else: ?>
            <div class="dash-stream-scroll-wrap" style="height: 250px; max-height: 250px;">
                <!-- Next Holiday Hero Banner -->
                <?php if ($nextHoliday): ?>
                    <div class="holiday-hero-banner">
                        <div class="holiday-hero-left">
                            <div class="holiday-date-square">
                                <span class="h-month"><?= strtoupper(date('M', strtotime($nextHoliday['holiday_date']))) ?></span>
                                <span class="h-day"><?= date('d', strtotime($nextHoliday['holiday_date'])) ?></span>
                            </div>
                            <div class="holiday-hero-info">
                                <div class="holiday-hero-title-row">
                                    <h4 class="holiday-hero-title"><?= e($nextHoliday['title']) ?></h4>
                                    <span class="badge-today-mini"><?= $daysUntilNext === 0 ? 'Today!' : ($daysUntilNext === 1 ? 'Tomorrow' : "In {$daysUntilNext}d") ?></span>
                                </div>
                                <span class="holiday-hero-sub">
                                    <?= date('l', strtotime($nextHoliday['holiday_date'])) ?> • <?= e($nextHoliday['description'] ?: 'Gazetted Holiday') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Next Upcoming Holidays Stream -->
                <div class="holiday-stream-list">
                    <?php foreach (array_slice($upcomingHolidays, 1) as $h): 
                        $dObj = new DateTime($h['holiday_date']);
                        $diffH = (new DateTime('today'))->diff($dObj);
                        $dCount = (int)$diffH->format('%r%a');
                    ?>
                        <div class="holiday-stream-item">
                            <div class="holiday-stream-date-box">
                                <span class="h-sm-month"><?= strtoupper(date('M', strtotime($h['holiday_date']))) ?></span>
                                <span class="h-sm-day"><?= date('d', strtotime($h['holiday_date'])) ?></span>
                            </div>
                            <div class="holiday-stream-details">
                                <div class="holiday-stream-name-row">
                                    <strong class="holiday-stream-title"><?= e($h['title']) ?></strong>
                                    <span class="holiday-stream-countdown">In <?= $dCount ?> days</span>
                                </div>
                                <span class="holiday-stream-sub">
                                    <?= date('l', strtotime($h['holiday_date'])) ?> • <?= e($h['description'] ?: 'Gazetted Holiday') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
