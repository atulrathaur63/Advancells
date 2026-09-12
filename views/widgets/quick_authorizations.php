<?php
/**
 * Dual Quick Authorization Hub Widget
 * Supports 1-click Review & Approval for both Pending Leaves & Missed Punch Regularizations
 */
$pendingLeaves = $pendingLeaves ?? [];
$pendingRegs = $pendingRegs ?? [];
?>

<div class="card" id="dualAuthWidget">
    <div class="card-header card-header-compact" style="flex-wrap: wrap; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-clipboard-check text-primary" style="font-size: 15px;"></i>
            
            <!-- Dual Segment Tabs -->
            <div class="auth-segment-tabs" role="tablist">
                <button type="button" class="auth-seg-tab active" id="tabBtnLeaves" onclick="switchAuthTab('leaves')">
                    <span>Leaves</span>
                    <span class="auth-tab-pill" id="authLeavesBadge"><?= count($pendingLeaves) ?></span>
                </button>
                <button type="button" class="auth-seg-tab" id="tabBtnRegs" onclick="switchAuthTab('regularizations')">
                    <span>Missed Punches</span>
                    <span class="auth-tab-pill" id="authRegsBadge"><?= count($pendingRegs) ?></span>
                </button>
            </div>
        </div>

        <a href="<?= url('leaves/approvals') ?>" id="authReviewPortalBtn" class="btn btn-sm btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">
            Review Portal
        </a>
    </div>

    <div class="card-body" style="padding: 0;">
        <!-- PANE 1: PENDING LEAVES -->
        <div id="authLeavesPane">
            <?php if (empty($pendingLeaves)): ?>
                <div class="dash-empty-scroll-box" id="authLeavesEmpty">
                    <i class="fa-solid fa-circle-check" style="font-size: 32px; color: #10b981; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin: 0 0 4px 0;">All Leaves Reviewed</p>
                    <span style="font-size: 11.5px; color: var(--text-muted);">There are no pending leave applications in the queue.</span>
                </div>
            <?php else: ?>
                <div class="table-responsive dash-table-scroll-wrap" id="authLeavesWrap">
                    <table class="data-table data-table-sticky">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Policy</th>
                                <th>Dates</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="authLeavesTableBody">
                            <?php foreach ($pendingLeaves as $l): ?>
                                <tr id="authLeaveRow_<?= $l['id'] ?>" class="auth-item-row">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 28px; height: 28px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 10px; flex-shrink: 0;">
                                                <?= strtoupper(substr($l['employee_name'], 0, 1)) ?>
                                            </div>
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 12px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($l['employee_name']) ?></strong>
                                                <small style="color:var(--text-muted); font-size: 10.5px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($l['emp_code']) ?> • <?= e($l['department_name']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-purple" style="font-size: 10px;"><?= e($l['leave_type_code']) ?></span></td>
                                    <td>
                                        <span style="font-size: 11.5px;"><?= format_date($l['from_date'], 'd M') ?> - <?= format_date($l['to_date'], 'd M') ?></span><br>
                                        <small><strong><?= $l['total_days'] ?> day(s)</strong></small>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-xs btn-primary" 
                                                onclick="openLeaveAuthModal(<?= $l['id'] ?>, '<?= e(addslashes($l['employee_name'])) ?>', '<?= e($l['emp_code']) ?>', '<?= e($l['leave_type_code']) ?>', '<?= format_date($l['from_date'], 'd M Y') ?>', '<?= format_date($l['to_date'], 'd M Y') ?>', '<?= $l['total_days'] ?>', '<?= e(addslashes($l['reason'] ?? '')) ?>')">
                                            Review
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANE 2: PENDING REGULARIZATIONS -->
        <div id="authRegsPane" style="display: none;">
            <?php if (empty($pendingRegs)): ?>
                <div class="dash-empty-scroll-box" id="authRegsEmpty">
                    <i class="fa-solid fa-circle-check" style="font-size: 32px; color: #10b981; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin: 0 0 4px 0;">All Punches Regularized</p>
                    <span style="font-size: 11.5px; color: var(--text-muted);">No missed punch regularization requests pending.</span>
                </div>
            <?php else: ?>
                <div class="table-responsive dash-table-scroll-wrap" id="authRegsWrap">
                    <table class="data-table data-table-sticky">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Date</th>
                                <th>Requested Timings</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="authRegsTableBody">
                            <?php foreach ($pendingRegs as $r): ?>
                                <tr id="authRegRow_<?= $r['id'] ?>" class="auth-item-row">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 28px; height: 28px; border-radius: var(--radius-full); background: linear-gradient(135deg, #0284c7, #0d9488); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 10px; flex-shrink: 0;">
                                                <?= strtoupper(substr($r['employee_name'], 0, 1)) ?>
                                            </div>
                                            <div style="min-width: 0;">
                                                <strong style="font-size: 12px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($r['employee_name']) ?></strong>
                                                <small style="color:var(--text-muted); font-size: 10.5px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($r['emp_code']) ?> • <?= e($r['department_name']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 11.5px; font-weight: 600;"><?= format_date($r['date'], 'd M Y') ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size: 11.5px;"><i class="fa-solid fa-arrow-right-to-bracket text-success" style="font-size: 10px;"></i> <?= format_time($r['requested_punch_in']) ?></span> • 
                                        <span style="font-size: 11.5px;"><i class="fa-solid fa-arrow-right-from-bracket text-danger" style="font-size: 10px;"></i> <?= format_time($r['requested_punch_out']) ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-xs btn-primary" 
                                                onclick="openRegAuthModal(<?= $r['id'] ?>, '<?= e(addslashes($r['employee_name'])) ?>', '<?= e($r['emp_code']) ?>', '<?= format_date($r['date'], 'd M Y') ?>', '<?= format_time($r['requested_punch_in']) ?>', '<?= format_time($r['requested_punch_out']) ?>', '<?= e(addslashes($r['reason'] ?? '')) ?>')">
                                            Review
                                        </button>
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

<!-- Quick Decision Modal -->
<div class="modal-backdrop-custom" id="quickAuthModal" style="display: none;" onclick="if(event.target === this) closeQuickAuthModal()">
    <div class="modal-dialog-custom modal-dialog-compact">
        <div class="modal-header-custom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="modal-header-icon" id="quickAuthIcon" style="background: #fdf2f8; color: var(--brand-plum);">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                    <h4 class="modal-title-custom" id="quickAuthTitle">Review Application</h4>
                    <span class="modal-subtitle-custom" id="quickAuthSubtitle">1-Click Authorization Console</span>
                </div>
            </div>
            <button type="button" class="modal-close-custom" onclick="closeQuickAuthModal()">×</button>
        </div>

        <div class="modal-body-custom" style="padding: 16px 20px;">
            <!-- Applicant Info Card -->
            <div class="quick-auth-applicant-box" id="quickAuthApplicantBox">
                <!-- Dynamically populated -->
            </div>

            <!-- Approver Remarks -->
            <div class="form-group" style="margin-bottom: 0;">
                <label for="quickAuthRemarks" style="font-size: 12px; font-weight: 600; color: var(--text-main); margin-bottom: 4px; display: block;">
                    Approver Remarks (Optional):
                </label>
                <textarea id="quickAuthRemarks" rows="2" class="form-control" placeholder="Add approval remarks or rejection notes..."></textarea>
            </div>
        </div>

        <div class="modal-footer-custom" style="padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeQuickAuthModal()">Cancel</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-sm btn-danger" id="quickAuthRejectBtn" onclick="submitQuickAuth('reject')">
                    <i class="fa-solid fa-xmark"></i> Reject
                </button>
                <button type="button" class="btn btn-sm btn-success" id="quickAuthApproveBtn" onclick="submitQuickAuth('approve')">
                    <i class="fa-solid fa-check"></i> Approve Application
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAuthContext = {
    type: 'leave', // 'leave' or 'regularization'
    id: 0
};

function switchAuthTab(type) {
    const tabLeaves = document.getElementById('tabBtnLeaves');
    const tabRegs = document.getElementById('tabBtnRegs');
    const paneLeaves = document.getElementById('authLeavesPane');
    const paneRegs = document.getElementById('authRegsPane');
    const portalBtn = document.getElementById('authReviewPortalBtn');

    if (type === 'leaves') {
        tabLeaves.classList.add('active');
        tabRegs.classList.remove('active');
        paneLeaves.style.display = 'block';
        paneRegs.style.display = 'none';
        portalBtn.href = '<?= url("leaves/approvals") ?>';
    } else {
        tabRegs.classList.add('active');
        tabLeaves.classList.remove('active');
        paneRegs.style.display = 'block';
        paneLeaves.style.display = 'none';
        portalBtn.href = '<?= url("attendance/regularize-approvals") ?>';
    }
}

function openLeaveAuthModal(id, name, code, policy, from, to, days, reason) {
    currentAuthContext = { type: 'leave', id: id };
    document.getElementById('quickAuthTitle').textContent = `Review Leave Application`;
    document.getElementById('quickAuthSubtitle').textContent = `${name} (${code})`;
    document.getElementById('quickAuthRemarks').value = '';

    const iconEl = document.getElementById('quickAuthIcon');
    iconEl.innerHTML = '<i class="fa-solid fa-calendar-minus"></i>';
    iconEl.style.background = '#fdf2f8';
    iconEl.style.color = '#ec4899';

    document.getElementById('quickAuthApplicantBox').innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span class="badge badge-purple" style="font-size: 11px;">Policy: ${escapeHtml(policy)}</span>
            <strong style="font-size: 13px; color: var(--text-main);">${days} Day(s)</strong>
        </div>
        <div style="font-size: 12.5px; color: var(--text-main); margin-bottom: 6px;">
            <i class="fa-regular fa-calendar" style="color: var(--primary); margin-right: 4px;"></i> <strong>${from}</strong> to <strong>${to}</strong>
        </div>
        ${reason ? `<div style="font-size: 11.5px; color: #475569; background: #fff; padding: 8px 10px; border-radius: 4px; border: 1px solid #e2e8f0; margin-top: 6px;">
            <em>"${escapeHtml(reason)}"</em>
        </div>` : ''}
    `;

    document.getElementById('quickAuthModal').style.display = 'flex';
}

function openRegAuthModal(id, name, code, date, inTime, outTime, reason) {
    currentAuthContext = { type: 'regularization', id: id };
    document.getElementById('quickAuthTitle').textContent = `Review Missed Punch`;
    document.getElementById('quickAuthSubtitle').textContent = `${name} (${code})`;
    document.getElementById('quickAuthRemarks').value = '';

    const iconEl = document.getElementById('quickAuthIcon');
    iconEl.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i>';
    iconEl.style.background = '#f0fdfa';
    iconEl.style.color = '#0d9488';

    document.getElementById('quickAuthApplicantBox').innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span class="badge badge-info" style="font-size: 11px;">Missed Punch Request</span>
            <strong style="font-size: 12.5px; color: var(--text-main);">${date}</strong>
        </div>
        <div style="font-size: 12.5px; color: var(--text-main); margin-bottom: 6px;">
            <i class="fa-solid fa-clock" style="color: var(--primary); margin-right: 4px;"></i> Requested: <strong>${inTime}</strong> - <strong>${outTime}</strong>
        </div>
        ${reason ? `<div style="font-size: 11.5px; color: #475569; background: #fff; padding: 8px 10px; border-radius: 4px; border: 1px solid #e2e8f0; margin-top: 6px;">
            <em>"${escapeHtml(reason)}"</em>
        </div>` : ''}
    `;

    document.getElementById('quickAuthModal').style.display = 'flex';
}

function closeQuickAuthModal() {
    document.getElementById('quickAuthModal').style.display = 'none';
}

function submitQuickAuth(action) {
    const approveBtn = document.getElementById('quickAuthApproveBtn');
    const rejectBtn = document.getElementById('quickAuthRejectBtn');
    const remarks = document.getElementById('quickAuthRemarks').value;
    const csrfToken = '<?= csrf_token() ?>';

    approveBtn.disabled = true;
    rejectBtn.disabled = true;

    let targetUrl = '';
    let bodyData = new FormData();
    bodyData.append('csrf_token', csrfToken);

    if (currentAuthContext.type === 'leave') {
        targetUrl = action === 'approve' ? '<?= url("leaves/approve") ?>' : '<?= url("leaves/reject") ?>';
        bodyData.append('request_id', currentAuthContext.id);
        bodyData.append('approver_remarks', remarks);
    } else {
        targetUrl = action === 'approve' ? '<?= url("attendance/approve-regularization") ?>' : '<?= url("attendance/reject-regularization") ?>';
        bodyData.append('id', currentAuthContext.id);
        bodyData.append('admin_remarks', remarks);
    }

    fetch(targetUrl, {
        method: 'POST',
        body: bodyData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        approveBtn.disabled = false;
        rejectBtn.disabled = false;

        if (data.success) {
            closeQuickAuthModal();
            showCelebrationToast(data.message || `Application ${action}d successfully!`);

            // Remove row with animation
            const rowId = currentAuthContext.type === 'leave' ? `authLeaveRow_${currentAuthContext.id}` : `authRegRow_${currentAuthContext.id}`;
            const rowEl = document.getElementById(rowId);
            if (rowEl) {
                rowEl.style.transition = 'all 0.3s ease';
                rowEl.style.opacity = '0';
                rowEl.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    rowEl.remove();
                    // Update badge count
                    if (currentAuthContext.type === 'leave') {
                        const badge = document.getElementById('authLeavesBadge');
                        const cur = parseInt(badge.textContent || '0', 10);
                        const next = Math.max(0, cur - 1);
                        badge.textContent = next;
                        if (next === 0) {
                            const pane = document.getElementById('authLeavesPane');
                            pane.innerHTML = `
                                <div class="dash-empty-scroll-box">
                                    <i class="fa-solid fa-circle-check" style="font-size: 32px; color: #10b981; margin-bottom: 8px;"></i>
                                    <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin: 0 0 4px 0;">All Leaves Reviewed</p>
                                    <span style="font-size: 11.5px; color: var(--text-muted);">There are no pending leave applications in the queue.</span>
                                </div>
                            `;
                        }
                    } else {
                        const badge = document.getElementById('authRegsBadge');
                        const cur = parseInt(badge.textContent || '0', 10);
                        const next = Math.max(0, cur - 1);
                        badge.textContent = next;
                        if (next === 0) {
                            const pane = document.getElementById('authRegsPane');
                            pane.innerHTML = `
                                <div class="dash-empty-scroll-box">
                                    <i class="fa-solid fa-circle-check" style="font-size: 32px; color: #10b981; margin-bottom: 8px;"></i>
                                    <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin: 0 0 4px 0;">All Punches Regularized</p>
                                    <span style="font-size: 11.5px; color: var(--text-muted);">No missed punch regularization requests pending.</span>
                                </div>
                            `;
                        }
                    }
                }, 300);
            }
        } else {
            alert(data.message || 'Error processing request.');
        }
    })
    .catch(() => {
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        alert('Network error while processing authorization.');
    });
}
</script>
