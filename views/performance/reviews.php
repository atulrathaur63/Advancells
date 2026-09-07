<?php
$pageTitle = 'Performance Appraisals & Reviews';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="grid-2">
    <!-- Reviews List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-award text-primary"></i> Appraisal Records (<?= count($reviews) ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($reviews)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                    No appraisal cycles recorded yet.
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <div>
                                <strong style="font-size: 15px;"><?= e($rev['employee_name']) ?></strong>
                                <span style="font-size: 12px; color: var(--text-muted);">(<?= e($rev['emp_code']) ?> • <?= e($rev['department_name']) ?>)</span>
                            </div>
                            <?= status_badge($rev['status']) ?>
                        </div>

                        <div style="font-size: 13px; margin-bottom: 10px; color: var(--text-muted);">
                            Review Period: <strong><?= e($rev['review_period']) ?></strong> • Reviewer: <strong><?= e($rev['reviewer_name']) ?></strong>
                        </div>

                        <div class="grid-2" style="background: #ffffff; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 8px;">
                            <div>
                                <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Self Assessment</span><br>
                                <strong><?= $rev['self_rating'] ? $rev['self_rating'] . ' / 5 <i class="fa-solid fa-star text-warning"></i>' : 'Not rated' ?></strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= e($rev['self_comments']) ?></p>
                            </div>
                            <div>
                                <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Manager Rating</span><br>
                                <strong><?= $rev['manager_rating'] ? $rev['manager_rating'] . ' / 5 <i class="fa-solid fa-star text-warning"></i>' : 'Pending' ?></strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= e($rev['manager_comments']) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($rev['final_rating'])): ?>
                            <div style="text-align: right; font-size: 13px; font-weight: 700; color: var(--primary);">
                                Final Normalized Rating: <?= $rev['final_rating'] ?> / 5.0 <i class="fa-solid fa-star text-warning"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submit Review Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-pen-to-square text-primary"></i> Start / Submit Performance Review</h3>
        </div>
        <div class="card-body">
            <form action="<?= url('performance/reviews') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="employee_id">Employee *</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        <?php if (Auth::isManager()): ?>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_code']) ?>)</option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="<?= Auth::employeeId() ?>" selected><?= e(Auth::user()['name']) ?> (Self)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reviewer_id">Reviewer / Manager *</label>
                    <select name="reviewer_id" id="reviewer_id" class="form-control" required>
                        <?php foreach (Employee::getManagers() as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['designation']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="review_period">Appraisal Cycle *</label>
                    <select name="review_period" id="review_period" class="form-control" required>
                        <option value="Q1 <?= date('Y') ?>">Q1 <?= date('Y') ?></option>
                        <option value="Q2 <?= date('Y') ?>">Q2 <?= date('Y') ?></option>
                        <option value="Q3 <?= date('Y') ?>" selected>Q3 <?= date('Y') ?></option>
                        <option value="Q4 <?= date('Y') ?>">Q4 <?= date('Y') ?></option>
                        <option value="Annual <?= date('Y') ?>">Annual Review <?= date('Y') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="self_rating">Performance Rating (1 to 5 Stars)</label>
                    <select name="self_rating" id="self_rating" class="form-control">
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Far Exceeds Expectations</option>
                        <option value="4" selected>⭐⭐⭐⭐ 4 - Consistently Exceeds Expectations</option>
                        <option value="3">⭐⭐⭐ 3 - Meets All Expectations</option>
                        <option value="2">⭐⭐ 2 - Needs Improvement</option>
                        <option value="1">⭐ 1 - Unsatisfactory</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="self_comments">Achievements & Key Milestones</label>
                    <textarea name="self_comments" id="self_comments" class="form-control" rows="3" placeholder="Key projects delivered, milestones achieved, research outcomes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Appraisal</button>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
