<?php
$pageTitle = 'Performance Goals & OKRs';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="grid-2">
    <!-- Goals List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-bullseye text-primary"></i> My Key Goals & Objectives (<?= count($goals) ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($goals)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                    No performance goals registered yet. Add your first OKR using the form on the right!
                </div>
            <?php else: ?>
                <?php foreach ($goals as $g): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <strong style="font-size: 15px; color: var(--text-main);"><?= e($g['title']) ?></strong>
                            <?= status_badge($g['status']) ?>
                        </div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;"><?= e($g['description']) ?></p>
                        
                        <div style="font-size: 12px; color: var(--text-light); margin-bottom: 8px;">
                            Target Completion: <strong><?= format_date($g['target_date']) ?></strong>
                        </div>

                        <!-- Progress Slider / Update Form -->
                        <form action="<?= url('performance/goals') ?>" method="POST" style="display: flex; align-items: center; gap: 12px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_progress">
                            <input type="hidden" name="goal_id" value="<?= $g['id'] ?>">

                            <div style="flex: 1;">
                                <input type="range" name="progress" min="0" max="100" value="<?= $g['progress'] ?>" style="width: 100%; cursor: pointer;" oninput="this.nextElementSibling.textContent = this.value + '%'">
                                <span style="font-size: 12px; font-weight: 700; color: var(--primary); margin-left: 6px;"><?= $g['progress'] ?>%</span>
                            </div>

                            <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Goal Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-circle-plus text-primary"></i> Add New Performance Goal / OKR</h3>
        </div>
        <div class="card-body">
            <form action="<?= url('performance/goals') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_goal">

                <div class="form-group">
                    <label class="form-label" for="title">Goal Objective / Title *</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Publish Research Paper on Stem Cell Viability" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Key Results & Metrics</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Measurable milestones and deliverables..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="target_date">Target Completion Date *</label>
                        <input type="date" name="target_date" id="target_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Goal</button>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
