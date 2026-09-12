<?php
$pageTitle = 'Performance Appraisals & Reviews';
require_once BASE_PATH . '/views/layouts/header.php';

$authEmpId = Auth::employeeId();
$isHR = Auth::isHR();
$isManager = (Auth::role() === 'manager');
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
                    <?php
                        $canGrade = ($isHR || ($isManager && (Employee::isSubordinateOf((int)$rev['employee_id'], $authEmpId) || (int)$rev['reviewer_id'] === $authEmpId)));
                        $needsGrading = $canGrade && ($rev['manager_rating'] === null || $rev['status'] === 'submitted');
                    ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div>
                                <strong style="font-size: 15px;"><?= e($rev['employee_name']) ?></strong>
                                <span style="font-size: 12px; color: var(--text-muted);">(<?= e($rev['emp_code']) ?> • <?= e($rev['department_name'] ?? 'General') ?>)</span>
                            </div>
                            <div>
                                <?= status_badge($rev['status']) ?>
                            </div>
                        </div>

                        <div style="font-size: 13px; margin-bottom: 10px; color: var(--text-muted);">
                            Review Period: <strong><?= e($rev['review_period']) ?></strong> • Reviewer: <strong><?= e($rev['reviewer_name'] ?? 'Unassigned') ?></strong>
                        </div>

                        <div class="grid-2" style="background: #ffffff; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 8px;">
                            <div>
                                <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Self Assessment</span><br>
                                <strong><?= $rev['self_rating'] ? $rev['self_rating'] . ' / 5 <i class="fa-solid fa-star text-warning"></i>' : '<span style="color: var(--text-muted);">Not rated</span>' ?></strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= nl2br(e($rev['self_comments'] ?: 'No comments provided.')) ?></p>
                            </div>
                            <div>
                                <span style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Manager Assessment</span><br>
                                <strong><?= $rev['manager_rating'] ? $rev['manager_rating'] . ' / 5 <i class="fa-solid fa-star text-warning"></i>' : '<span style="color: #f59e0b;">Pending Evaluation</span>' ?></strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= nl2br(e($rev['manager_comments'] ?: 'Awaiting manager feedback.')) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($rev['final_rating'])): ?>
                            <div style="text-align: right; font-size: 13px; font-weight: 700; color: var(--primary); margin-top: 6px;">
                                Final Normalized Rating: <?= $rev['final_rating'] ?> / 5.0 <i class="fa-solid fa-star text-warning"></i>
                            </div>
                        <?php endif; ?>

                        <?php if ($needsGrading): ?>
                            <div style="margin-top: 10px; border-top: 1px dashed var(--border-color); padding-top: 10px; text-align: right;">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('grade-box-<?= $rev['id'] ?>').style.display = document.getElementById('grade-box-<?= $rev['id'] ?>').style.display === 'none' ? 'block' : 'none';">
                                    <i class="fa-solid fa-pen-nib"></i> Evaluate / Grade Appraisal
                                </button>
                            </div>
                            <div id="grade-box-<?= $rev['id'] ?>" style="display: none; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-top: 10px; text-align: left;">
                                <form action="<?= url('performance/reviews') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="grade">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">

                                    <div class="form-group" style="margin-bottom: 10px;">
                                        <label class="form-label" style="font-size: 12px;">Manager Rating (1 - 5 Stars) *</label>
                                        <select name="manager_rating" class="form-control" style="padding: 6px 10px; font-size: 13px;" required>
                                            <option value="5">⭐⭐⭐⭐⭐ 5 - Outstanding</option>
                                            <option value="4" selected>⭐⭐⭐⭐ 4 - Exceeds Expectations</option>
                                            <option value="3">⭐⭐⭐ 3 - Meets Expectations</option>
                                            <option value="2">⭐⭐ 2 - Needs Improvement</option>
                                            <option value="1">⭐ 1 - Unsatisfactory</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 10px;">
                                        <label class="form-label" style="font-size: 12px;">Evaluation Comments / Feedback *</label>
                                        <textarea name="manager_comments" class="form-control" rows="2" style="font-size: 13px;" placeholder="Provide constructive feedback and target milestones..." required></textarea>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 10px;">
                                        <label class="form-label" style="font-size: 12px;">Final Normalized Rating (Optional override)</label>
                                        <input type="number" step="0.1" min="1.0" max="5.0" name="final_rating" class="form-control" style="padding: 6px 10px; font-size: 13px;" placeholder="Defaults to Manager Rating">
                                    </div>
                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('grade-box-<?= $rev['id'] ?>').style.display = 'none';">Cancel</button>
                                        <button type="submit" class="btn btn-sm btn-primary">Save Assessment</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (isset($pagination)): ?>
                    <div style="margin-top: 16px;">
                        <?= render_pagination($pagination) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submit Review Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-pen-to-square text-primary"></i> <?= ($isHR || $isManager) ? 'Conduct Performance Appraisal' : 'Submit Self-Appraisal' ?></h3>
        </div>
        <div class="card-body">
            <form action="<?= url('performance/reviews') ?>" method="POST" id="appraisalForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <?php if (!$isHR && !$isManager): ?>
                    <!-- Regular Employee View: Self-Assessment only -->
                    <input type="hidden" name="employee_id" value="<?= $authEmpId ?>">
                    <div class="form-group">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" value="<?= e(Auth::user()['name']) ?> (Self)" readonly style="background: #f1f5f9;">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reviewer_id">Reporting Manager / Reviewer *</label>
                        <select name="reviewer_id" id="reviewer_id" class="form-control" required>
                            <?php foreach (Employee::getManagers() as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['designation'] ?? 'Manager') ?>)</option>
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
                        <label class="form-label" for="self_rating">Self-Assessment Rating (1 to 5 Stars) *</label>
                        <select name="self_rating" id="self_rating" class="form-control" required>
                            <option value="5">⭐⭐⭐⭐⭐ 5 - Far Exceeds Expectations</option>
                            <option value="4" selected>⭐⭐⭐⭐ 4 - Consistently Exceeds Expectations</option>
                            <option value="3">⭐⭐⭐ 3 - Meets All Expectations</option>
                            <option value="2">⭐⭐ 2 - Needs Improvement</option>
                            <option value="1">⭐ 1 - Unsatisfactory</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="self_comments">Achievements & Key Milestones</label>
                        <textarea name="self_comments" id="self_comments" class="form-control" rows="4" placeholder="Key projects delivered, goals completed, research publications..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Submit Self-Appraisal</button>

                <?php else: ?>
                    <!-- Manager or HR View -->
                    <div class="form-group">
                        <label class="form-label" for="employee_id">Select Employee *</label>
                        <select name="employee_id" id="employee_id" class="form-control" onchange="toggleFormFields(this.value)" required>
                            <?php if ($isManager && !$isHR): ?>
                                <option value="<?= $authEmpId ?>"><?= e(Auth::user()['name']) ?> (Self-Appraisal)</option>
                                <?php if (!empty($employees)): ?>
                                    <optgroup label="Team Reportees">
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?= $emp['id'] ?>"><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_code']) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($emp['id'] === $authEmpId) ? 'selected' : '' ?>>
                                        <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group" id="reviewerGroup" style="<?= ($isManager && !$isHR) ? 'display: block;' : 'display: none;' ?>">
                        <label class="form-label" for="reviewer_id">Reviewer / Manager</label>
                        <select name="reviewer_id" id="reviewer_id" class="form-control">
                            <?php foreach (Employee::getManagers() as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($m['id'] === $authEmpId) ? 'selected' : '' ?>><?= e($m['name']) ?> (<?= e($m['designation'] ?? 'Manager') ?>)</option>
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

                    <!-- Self-Appraisal Section (shown when evaluating self) -->
                    <div id="selfSection" style="display: <?= ($isManager && !$isHR) ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label class="form-label" for="self_rating">Self-Assessment Rating (1 - 5 Stars)</label>
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
                            <textarea name="self_comments" id="self_comments" class="form-control" rows="3" placeholder="Key projects delivered, milestones achieved..."></textarea>
                        </div>
                    </div>

                    <!-- Manager Assessment Section (shown when evaluating a subordinate or other employee) -->
                    <div id="managerSection" style="display: <?= ($isManager && !$isHR) ? 'none' : 'block' ?>;">
                        <div class="form-group">
                            <label class="form-label" for="manager_rating">Manager Rating (1 to 5 Stars) *</label>
                            <select name="manager_rating" id="manager_rating" class="form-control">
                                <option value="5">⭐⭐⭐⭐⭐ 5 - Far Exceeds Expectations</option>
                                <option value="4" selected>⭐⭐⭐⭐ 4 - Consistently Exceeds Expectations</option>
                                <option value="3">⭐⭐⭐ 3 - Meets All Expectations</option>
                                <option value="2">⭐⭐ 2 - Needs Improvement</option>
                                <option value="1">⭐ 1 - Unsatisfactory</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="manager_comments">Manager Evaluation & Feedback</label>
                            <textarea name="manager_comments" id="manager_comments" class="form-control" rows="3" placeholder="Performance feedback, strengths, and development areas..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="final_rating">Final Normalized Rating (Optional)</label>
                            <input type="number" step="0.1" min="1.0" max="5.0" name="final_rating" id="final_rating" class="form-control" placeholder="Defaults to Manager Rating">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Save & Submit Appraisal</button>

                    <script>
                    function toggleFormFields(selectedEmpId) {
                        var authId = <?= (int)$authEmpId ?>;
                        var isHR = <?= $isHR ? 'true' : 'false' ?>;
                        var selfSec = document.getElementById('selfSection');
                        var mgrSec = document.getElementById('managerSection');
                        var revGroup = document.getElementById('reviewerGroup');

                        if (!isHR && parseInt(selectedEmpId) === authId) {
                            if (selfSec) selfSec.style.display = 'block';
                            if (mgrSec) mgrSec.style.display = 'none';
                            if (revGroup) revGroup.style.display = 'block';
                        } else {
                            if (selfSec) selfSec.style.display = 'none';
                            if (mgrSec) mgrSec.style.display = 'block';
                            if (revGroup) revGroup.style.display = 'none';
                        }
                    }
                    </script>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
