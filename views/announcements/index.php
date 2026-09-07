<?php
$pageTitle = 'Company Notice Board & Announcements';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="grid-2">
    <!-- Notice Board Stream -->
    <div class="card" style="grid-column: <?= Auth::isHR() ? 'span 1' : 'span 2' ?>;">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-bullhorn text-primary"></i> Advancells Circulars & Broadcasts (<?= count($announcements) ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($announcements)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    No active announcements on the notice board.
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-left: 5px solid <?= ($ann['priority'] === 'urgent' || $ann['priority'] === 'high') ? 'var(--danger)' : 'var(--primary)' ?>; border-radius: var(--radius-md); padding: 20px; margin-bottom: 18px; box-shadow: var(--shadow-sm);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <h3 style="font-family: var(--font-heading); font-size: 17px; font-weight: 700; color: var(--text-main);">
                                <?= e($ann['title']) ?>
                            </h3>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if (($ann['target_role'] ?? 'all') === 'manager'): ?>
                                    <span class="badge" style="background: #e0e7ff; color: #3730a3; font-size: 11px;"><i class="fa-solid fa-user-tie"></i> Managers</span>
                                <?php elseif (($ann['target_role'] ?? 'all') === 'employee'): ?>
                                    <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 11px;"><i class="fa-solid fa-users"></i> Staff</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 11px;"><i class="fa-solid fa-globe"></i> Everyone</span>
                                <?php endif; ?>
                                <?= status_badge($ann['priority']) ?>
                                <?php if (Auth::isHR()): ?>
                                    <form action="<?= url('announcements') ?>" method="POST" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                        <button type="submit" style="background: none; border: none; cursor: pointer; color: #ef4444; font-size: 14px;" title="Delete Notice" onclick="return confirmAction('Delete this circular?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p style="font-size: 13.5px; color: #334155; line-height: 1.6; margin-bottom: 12px; white-space: pre-line;">
                            <?= e($ann['content']) ?>
                        </p>

                        <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: var(--text-muted); border-top: 1px dashed var(--border-color); padding-top: 8px;">
                            <span>Published by: <strong><?= e($ann['author_name']) ?></strong></span>
                            <span><?= format_date($ann['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Post Announcement Form (HR/Admin) -->
    <?php if (Auth::isHR()): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-feather text-primary"></i> Broadcast New Notice</h3>
        </div>
        <div class="card-body">
            <form action="<?= url('announcements') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label class="form-label" for="ann_title">Notice Headline / Subject *</label>
                    <input type="text" name="title" id="ann_title" class="form-control" placeholder="e.g. Mandatory Lab Safety Audit on Thursday" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="priority">Priority Level</label>
                        <select name="priority" id="priority" class="form-control">
                            <option value="normal" selected>Normal</option>
                            <option value="high">High Priority</option>
                            <option value="urgent">Urgent Alert</option>
                            <option value="low">Informational / Low</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="target_role">Target Audience</label>
                        <select name="target_role" id="target_role" class="form-control">
                            <option value="all" selected>All Staff (Company-wide)</option>
                            <option value="manager">Managers Only</option>
                            <option value="employee">Staff / Employees Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expires_at">Expiration Date (Optional)</label>
                        <input type="date" name="expires_at" id="expires_at" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ann_content">Notice Body & Details *</label>
                    <textarea name="content" id="ann_content" class="form-control" rows="6" placeholder="Full details, instructions, date/time, and target audience..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    📢 Publish Company Notice
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
