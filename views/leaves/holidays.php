<?php
$pageTitle = 'Company Holiday Calendar';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="grid-2">
    <!-- Holiday List -->
    <div class="card" style="grid-column: <?= Auth::isHR() ? 'span 1' : 'span 2' ?>;">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i>
                Advancells Holidays (<?= $year ?>)
            </h2>
            <span class="badge badge-info"><?= count($holidays) ?> Holidays</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Occasion</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Category</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holidays as $h): 
                            $hDate = strtotime($h['holiday_date']);
                            $isPast = $hDate < strtotime(date('Y-m-d'));
                        ?>
                            <tr style="<?= $isPast ? 'opacity: 0.6;' : '' ?>">
                                <td>
                                    <strong><?= e($h['title']) ?></strong>
                                    <?php if (!$isPast && $hDate <= strtotime('+14 days')): ?>
                                        <span class="badge badge-success" style="font-size: 10px; margin-left: 6px;">Upcoming</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d F Y', $hDate) ?></td>
                                <td style="color: var(--text-muted);"><?= date('l', $hDate) ?></td>
                                <td><?= status_badge($h['type']) ?></td>
                                <td><small style="color:var(--text-muted);"><?= e($h['description']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Holiday Form (HR only) -->
    <?php if (Auth::isHR()): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-plus" style="color: #059669;"></i>
                Add New Holiday
            </h3>
        </div>
        <div class="card-body">
            <form action="<?= url('leaves/holidays') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="title">Holiday Name / Occasion *</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Raksha Bandhan" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="holiday_date">Date of Holiday *</label>
                    <input type="date" name="holiday_date" id="holiday_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="type">Classification</label>
                    <select name="type" id="type" class="form-control">
                        <option value="mandatory">Mandatory (Gazetted National Holiday)</option>
                        <option value="optional">Optional / Restricted Holiday</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description / Notes</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Brief note about the holiday..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-calendar-plus"></i> Add Holiday to Calendar
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
