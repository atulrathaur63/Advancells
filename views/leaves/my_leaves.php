<?php
$pageTitle = 'My Leave Applications & Balances';
require_once BASE_PATH . '/views/layouts/header.php';

$balanceMap = [];
$totalAvail = 0.00;
$totalUsed = 0.00;
$totalPending = 0.00;

foreach ($balances as $b) {
    $balanceMap[$b['leave_type_id']] = $b;
    if ($b['leave_type_code'] !== 'LOP') {
        $totalAvail += (float)$b['available'];
        $totalUsed += (float)$b['used'];
        $totalPending += (float)$b['pending'];
    }
}

$pendingCount = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejectedCount = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
$cancelledCount = count(array_filter($requests, fn($r) => $r['status'] === 'cancelled'));
?>


<!-- 2. Executive Leave Balances Grid -->
<div class="leave-balances-grid">
    <?php foreach ($balances as $b): ?>
        <?php if ($b['leave_type_code'] !== 'LOP'): 
            $code = strtolower($b['leave_type_code']);
            $codeUpper = strtoupper($b['leave_type_code']);
            
            $allocated = (float)$b['total_allocated'];
            $available = (float)$b['available'];
            $used = (float)$b['used'];
            $pending = (float)$b['pending'];
            
            if ($allocated > 0) {
                $usedPct = min(100, round(($used / $allocated) * 100, 1));
                $pendingPct = min(100 - $usedPct, round(($pending / $allocated) * 100, 1));
                $availPct = max(0, 100 - $usedPct - $pendingPct);
            } else {
                $usedPct = 0;
                $pendingPct = 0;
                $availPct = 100;
            }
        ?>
            <div class="leave-balance-card <?= $code ?>" onclick="selectCategoryByCode('<?= $codeUpper ?>')" style="cursor: pointer;" title="Click to select <?= e($b['leave_type_name']) ?>">
                <div class="leave-card-header">
                    <div>
                        <span class="leave-code-pill <?= $code ?>"><?= e($codeUpper) ?></span>
                        <div class="leave-type-name" title="<?= e($b['leave_type_name']) ?>">
                            <?= e($b['leave_type_name']) ?>
                        </div>
                    </div>
                    <?php if (!empty($b['accrual_label'])): ?>
                        <span class="leave-accrual-pill" title="Accrual Frequency">
                            <?= e($b['accrual_label']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="leave-card-hero">
                    <div class="leave-hero-number-wrap">
                        <span class="leave-hero-value"><?= number_format($available, 2) ?></span>
                        <span class="leave-hero-unit">Days Available</span>
                    </div>
                    <div class="leave-hero-sub">
                        <i class="fa-solid fa-chart-pie" style="font-size: 10px; color: var(--text-light);"></i>
                        Accrued: <strong><?= number_format($allocated, 2) ?> days</strong>
                        <?php if (!empty($b['carried_forward']) && (float)$b['carried_forward'] > 0): ?>
                            <span style="display: inline-block; background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 600; margin-left: 4px;">+<?= number_format((float)$b['carried_forward'], 1) ?> C/F</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Multi-segment visual bar -->
                <div class="leave-progress-track" title="Available: <?= $available ?>, Pending: <?= $pending ?>, Used: <?= $used ?>">
                    <div class="leave-progress-segment avail-<?= $code ?>" style="width: <?= $availPct ?>%;"></div>
                    <div class="leave-progress-segment pending" style="width: <?= $pendingPct ?>%;"></div>
                    <div class="leave-progress-segment used" style="width: <?= $usedPct ?>%;"></div>
                </div>

                <!-- 3-Column Micro Metrics Breakdown in Tiles -->
                <div class="leave-card-footer">
                    <div class="leave-footer-tile">
                        <span class="leave-footer-label">Accrued</span>
                        <span class="leave-footer-val"><?= number_format($allocated, 2) ?></span>
                    </div>
                    <div class="leave-footer-tile">
                        <span class="leave-footer-label">Used</span>
                        <span class="leave-footer-val <?= ($used > 0) ? 'danger' : '' ?>"><?= number_format($used, 2) ?></span>
                    </div>
                    <div class="leave-footer-tile">
                        <span class="leave-footer-label">Pending</span>
                        <span class="leave-footer-val <?= ($pending > 0) ? 'warning' : '' ?>"><?= number_format($pending, 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- 3. Workspace Layout: Form & History Table -->
<div class="leave-workspace-layout">
    <!-- Leave Application Form -->
    <div class="leave-apply-card">
        <div class="leave-apply-header">
            <div class="leave-apply-header-icon">
                <i class="fa-solid fa-calendar-plus"></i>
            </div>
            <div>
                <h2 class="leave-apply-title">Apply for Leave</h2>
                <div class="leave-apply-subtitle">Submit a time-off or absence request</div>
            </div>
        </div>
        <div class="leave-apply-body">
            <form action="<?= url('leaves/apply') ?>" method="POST" id="leaveApplyForm">
                <?= csrf_field() ?>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                        <span>Leave Category *</span>
                        <span style="font-size: 11px; color: var(--text-muted); font-weight: 500;">Click card or chip</span>
                    </label>
                    
                    <!-- Quick Category Selector Chips -->
                    <div class="cat-pills-row">
                        <?php foreach ($types as $t): 
                            $bal = $balanceMap[$t['id']] ?? null;
                            $avail = $bal ? (float)$bal['available'] : 0.00;
                            $codeUpper = strtoupper($t['code']);
                        ?>
                            <div class="cat-pill-btn" onclick="selectCategory(<?= $t['id'] ?>)" id="cat_btn_<?= $t['id'] ?>" data-id="<?= $t['id'] ?>" data-code="<?= $codeUpper ?>">
                                <span class="cat-pill-code"><?= e($codeUpper) ?></span>
                                <span class="cat-pill-avail"><?= $codeUpper === 'LOP' ? 'Unpaid' : number_format($avail, 1) . 'd' ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Hidden Synced Select for Standard Form Post -->
                    <select name="leave_type_id" id="leave_type_id" class="form-control" required style="display: none;">
                        <option value="">-- Choose Leave Category --</option>
                        <?php foreach ($types as $t): 
                            $bal = $balanceMap[$t['id']] ?? null;
                            $avail = $bal ? (float)$bal['available'] : 0.00;
                            $codeUpper = strtoupper($t['code']);
                        ?>
                            <option value="<?= $t['id'] ?>" 
                                    data-code="<?= e($codeUpper) ?>" 
                                    data-available="<?= $avail ?>"
                                    data-is-paid="<?= $t['is_paid'] ? '1' : '0' ?>">
                                <?= e($t['name']) ?> (<?= e($codeUpper) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="leave_balance_hint" class="leave-balance-live-hint" style="display: none;">
                        <span id="leave_balance_text">Select a category to view quota</span>
                        <span id="leave_balance_badge" class="badge badge-success" style="font-size: 11px;"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="from_date">From Date *</label>
                        <div class="input-icon-wrap">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="date" name="from_date" id="from_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="to_date">To Date *</label>
                        <div class="input-icon-wrap">
                            <i class="fa-regular fa-calendar-check"></i>
                            <input type="date" name="to_date" id="to_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <!-- Dynamic Duration & Trip Preview Widget -->
                <div id="leave_duration_preview" class="leave-preview-summary-box" style="display: none;">
                    <div class="preview-dates-row">
                        <span><i class="fa-regular fa-calendar" style="color: var(--brand-plum); margin-right: 4px;"></i> <span id="preview_from_date">--</span></span>
                        <i class="fa-solid fa-arrow-right-long" style="color: #94a3b8; font-size: 11px;"></i>
                        <span><i class="fa-regular fa-calendar-check" style="color: var(--brand-teal); margin-right: 4px;"></i> <span id="preview_to_date">--</span></span>
                    </div>
                    <div class="preview-metric-chips">
                        <span class="preview-chip duration" id="preview_duration_chip">
                            <i class="fa-solid fa-clock"></i> <span id="preview_duration_text">0.0 Days</span>
                        </span>
                        <span class="preview-chip balance" id="preview_balance_chip">
                            <i class="fa-solid fa-wallet"></i> <span id="preview_balance_text">Balance OK</span>
                        </span>
                    </div>
                    <div id="preview_sandwich_notice" style="display: none; font-size: 11px; margin-top: 8px; padding: 6px 10px; border-radius: 6px; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; line-height: 1.35;">
                        <i class="fa-solid fa-layer-group" style="margin-right: 4px;"></i> <span id="preview_sandwich_text"></span>
                    </div>
                </div>

                <!-- Modern Half Day Toggle Card -->
                <div class="form-group">
                    <div class="half-day-toggle-card" id="half_day_card" onclick="toggleHalfDayCard()">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-cloud-sun" style="font-size: 18px; color: var(--brand-plum);"></i>
                            <div>
                                <div style="font-size: 13.5px; font-weight: 600; color: var(--text-main);">Half Day Leave</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Apply for a single morning or afternoon session (0.5d)</div>
                            </div>
                        </div>
                        <div class="toggle-switch-ui" id="half_day_switch"></div>
                        <input type="checkbox" name="is_half_day" id="is_half_day" value="1" style="display: none;">
                    </div>
                </div>

                <div class="form-group" id="half_day_select" style="display: none;">
                    <label class="form-label" for="half_day_type">Half Day Session</label>
                    <select name="half_day_type" id="half_day_type" class="form-control">
                        <option value="first_half">First Half (Morning Session)</option>
                        <option value="second_half">Second Half (Afternoon Session)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Leave *</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Specify reason or context for taking time off..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: var(--shadow-plum);">
                    <i class="fa-solid fa-paper-plane"></i> Submit Leave Application
                </button>
            </form>
        </div>
    </div>

    <!-- My Leave Requests History -->
    <div class="leave-history-card">
        <div class="leave-history-header">
            <div class="leave-history-title-wrap">
                <i class="fa-solid fa-clock-rotate-left" style="font-size: 17px; color: #64748b;"></i>
                <h3 class="leave-history-title">My Leave History</h3>
            </div>
            <div class="leave-history-tabs">
                <button type="button" class="history-filter-tab active" onclick="filterHistory('all', this)">
                    All (<?= count($requests) ?>)
                </button>
                <button type="button" class="history-filter-tab" onclick="filterHistory('pending', this)">
                    Pending (<?= $pendingCount ?>)
                </button>
                <button type="button" class="history-filter-tab" onclick="filterHistory('approved', this)">
                    Approved (<?= $approvedCount ?>)
                </button>
                <button type="button" class="history-filter-tab" onclick="filterHistory('rejected', this)">
                    Rejected (<?= $rejectedCount ?>)
                </button>
                <button type="button" class="history-filter-tab" onclick="filterHistory('cancelled', this)">
                    Cancelled (<?= $cancelledCount ?>)
                </button>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($requests)): ?>
                <div style="padding: 60px 20px; text-align: center;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: #f8fafc; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 14px; border: 1px solid #e2e8f0;">
                        <i class="fa-regular fa-calendar-days"></i>
                    </div>
                    <h4 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">No Leave Records Yet</h4>
                    <p style="font-size: 13px; color: var(--text-muted); max-width: 340px; margin: 0 auto; line-height: 1.4;">
                        You haven't submitted any leave applications for <?= $year ?> yet. Choose a category on the left to apply!
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table" id="leaveHistoryTable" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Leave Category</th>
                                <th style="width: 24%;">Duration & Schedule</th>
                                <th style="width: 10%; text-align: center;">Days</th>
                                <th style="width: 20%;">Reason</th>
                                <th style="width: 13%; text-align: center;">Status</th>
                                <th style="width: 13%; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): 
                                $code = strtoupper($req['leave_type_code'] ?? '');
                                $pillClass = strtolower($code);
                                $reqStatus = strtolower($req['status'] ?? 'pending');
                            ?>
                                <tr class="history-row" data-status="<?= $reqStatus ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="leave-code-pill <?= $pillClass ?>"><?= e($code) ?></span>
                                            <div>
                                                <div style="font-size: 13px; font-weight: 700; color: var(--text-main);">
                                                    <?= e($req['leave_type_name'] ?? $code) ?>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                    <?= $req['is_half_day'] ? ('Half Day (' . ucfirst(str_replace('_', ' ', $req['half_day_type'] ?? '')) . ')') : 'Full Day' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 4px;">
                                            <span><?= format_date($req['from_date'], 'd M Y') ?></span>
                                            <?php if ($req['from_date'] !== $req['to_date']): ?>
                                                <i class="fa-solid fa-arrow-right-long" style="font-size: 10px; color: #94a3b8;"></i>
                                                <span><?= format_date($req['to_date'], 'd M Y') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-light); margin-top: 2px;">
                                            Applied <?= format_date($req['created_at'], 'd M, h:i A') ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge" style="background: #ffffff; color: #1e293b; font-weight: 800; font-size: 12px; border: 1px solid #e2e8f0; padding: 4px 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                            <?= number_format((float)$req['total_days'], 1) ?>d
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 12.5px; color: #334155; line-height: 1.35; max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($req['reason']) ?>">
                                            <?= e($req['reason']) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($reqStatus === 'pending'): ?>
                                            <span class="status-pill pending">
                                                <span class="status-pulse-dot pending"></span>
                                                Pending
                                            </span>
                                        <?php elseif ($reqStatus === 'approved'): ?>
                                            <span class="status-pill approved">
                                                <i class="fa-solid fa-check" style="font-size: 10px;"></i>
                                                Approved
                                            </span>
                                        <?php elseif ($reqStatus === 'cancelled'): ?>
                                            <span class="status-pill" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                                <i class="fa-solid fa-ban" style="font-size: 10px;"></i>
                                                Cancelled
                                            </span>
                                        <?php else: ?>
                                            <span class="status-pill rejected">
                                                <i class="fa-solid fa-xmark" style="font-size: 10px;"></i>
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($reqStatus === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: 6px;" onclick="openCancelModal(<?= $req['id'] ?>, 'Pending Request', '<?= number_format((float)$req['total_days'], 1) ?>')" title="Cancel Request">
                                                <i class="fa-solid fa-ban"></i> Cancel
                                            </button>
                                        <?php elseif ($reqStatus === 'approved' && $req['to_date'] >= date('Y-m-d')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" style="padding: 4px 9px; font-size: 11px; font-weight: 700; border-radius: 6px;" onclick="openCancelModal(<?= $req['id'] ?>, 'Approved Leave', '<?= number_format((float)$req['total_days'], 1) ?>')" title="Cancel Approved Leave">
                                                <i class="fa-solid fa-ban"></i> Cancel
                                            </button>
                                        <?php elseif ($reqStatus === 'cancelled'): ?>
                                            <span style="font-size: 11px; color: var(--text-muted);"><i class="fa-solid fa-rotate-left"></i> Refunded</span>
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: var(--text-light);">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="no_filter_matches" style="display: none;">
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    <i class="fa-solid fa-filter" style="font-size: 20px; color: #cbd5e1; margin-bottom: 6px; display: block;"></i>
                                    No leave requests match this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($pagination)): ?>
                    <div style="padding: 12px 18px; border-top: 1px solid var(--border-color);">
                        <?= render_pagination($pagination) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Category selection handlers
function selectCategory(id) {
    const sel = document.getElementById('leave_type_id');
    sel.value = id;
    
    // Highlight pill
    document.querySelectorAll('.cat-pill-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.getElementById('cat_btn_' + id);
    if (activeBtn) activeBtn.classList.add('active');

    // Trigger update
    onCategoryChanged();
}

function selectCategoryByCode(code) {
    const sel = document.getElementById('leave_type_id');
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].getAttribute('data-code') === code) {
            selectCategory(sel.options[i].value);
            // Smooth scroll down to form
            document.getElementById('leaveApplyForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            break;
        }
    }
}

function onCategoryChanged() {
    const sel = document.getElementById('leave_type_id');
    const selOpt = sel.options[sel.selectedIndex];
    const hint = document.getElementById('leave_balance_hint');
    const text = document.getElementById('leave_balance_text');
    const badge = document.getElementById('leave_balance_badge');
    
    if (!sel.value) {
        hint.style.display = 'none';
        return;
    }

    const avail = parseFloat(selOpt.getAttribute('data-available') || 0);
    const code = selOpt.getAttribute('data-code') || '';
    
    hint.style.display = 'flex';
    if (code === 'LOP') {
        hint.className = 'leave-balance-live-hint';
        text.innerHTML = '<i class="fa-solid fa-circle-info" style="color:#64748b;"></i> Loss of Pay (Unpaid Leave)';
        badge.style.display = 'none';
    } else if (avail > 0) {
        hint.className = 'leave-balance-live-hint has-balance';
        text.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available Balance';
        badge.style.display = 'inline-block';
        badge.className = 'badge badge-success';
        badge.innerText = avail.toFixed(2) + ' days';
    } else {
        hint.className = 'leave-balance-live-hint zero-balance';
        text.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> 0 days remaining (exhausted)';
        badge.style.display = 'none';
    }
    updateDurationPreview();
}

function toggleHalfDayCard() {
    const chk = document.getElementById('is_half_day');
    const card = document.getElementById('half_day_card');
    const box = document.getElementById('half_day_select');
    const toDateInput = document.getElementById('to_date');
    const fromDateInput = document.getElementById('from_date');

    chk.checked = !chk.checked;
    if (chk.checked) {
        card.classList.add('active');
        box.style.display = 'block';
        if (fromDateInput.value) {
            toDateInput.value = fromDateInput.value;
        }
        toDateInput.readOnly = true;
        toDateInput.style.backgroundColor = '#f1f5f9';
        toDateInput.style.cursor = 'not-allowed';
    } else {
        card.classList.remove('active');
        box.style.display = 'none';
        toDateInput.readOnly = false;
        toDateInput.style.backgroundColor = '';
        toDateInput.style.cursor = '';
    }
    updateDurationPreview();
}

function formatDateDisplay(dStr) {
    if (!dStr) return '';
    const d = new Date(dStr + 'T00:00:00');
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function updateDurationPreview() {
    const fromVal = document.getElementById('from_date').value;
    const toVal = document.getElementById('to_date').value;
    const isHalf = document.getElementById('is_half_day').checked;
    const preview = document.getElementById('leave_duration_preview');
    const fromPreview = document.getElementById('preview_from_date');
    const toPreview = document.getElementById('preview_to_date');
    const durText = document.getElementById('preview_duration_text');
    const balChip = document.getElementById('preview_balance_chip');
    const balText = document.getElementById('preview_balance_text');
    const sandwichBox = document.getElementById('preview_sandwich_notice');
    const sandwichText = document.getElementById('preview_sandwich_text');
    const sel = document.getElementById('leave_type_id');

    if (!fromVal || (!toVal && !isHalf)) {
        preview.style.display = 'none';
        return;
    }

    fromPreview.innerText = formatDateDisplay(fromVal);
    toPreview.innerText = isHalf ? formatDateDisplay(fromVal) : formatDateDisplay(toVal);

    // Call server endpoint for live sandwich calculation
    const calcUrl = '<?= url("leaves/calculate-days") ?>?from_date=' + encodeURIComponent(fromVal) + '&to_date=' + encodeURIComponent(isHalf ? fromVal : toVal) + '&is_half_day=' + (isHalf ? '1' : '0');

    fetch(calcUrl)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                preview.style.display = 'block';
                durText.innerHTML = '<span style="color:#e11d48;"><i class="fa-solid fa-triangle-exclamation"></i> ' + (data.message || 'Invalid range') + '</span>';
                balChip.style.display = 'none';
                sandwichBox.style.display = 'none';
                return;
            }

            const totalDays = parseFloat(data.total_days || 0);
            const sandwichedDays = parseInt(data.sandwiched_days || 0);
            const trimmedCount = (data.trimmed_dates && data.trimmed_dates.length) ? data.trimmed_dates.length : 0;

            if (isHalf) {
                durText.innerText = '0.5 Day (Half Day)';
                sandwichBox.style.display = 'none';
            } else if (sandwichedDays > 0) {
                durText.innerHTML = '<strong>' + totalDays.toFixed(1) + ' Days</strong> <span class="badge badge-warning" style="margin-left:6px; font-size:10px;"><i class="fa-solid fa-layer-group"></i> Sandwich Rule</span>';
                sandwichBox.style.display = 'block';
                sandwichText.innerText = data.details || (data.working_days + ' working day(s) + ' + sandwichedDays + ' sandwiched day(s) per company rule.');
            } else if (trimmedCount > 0) {
                durText.innerHTML = '<strong>' + totalDays.toFixed(1) + ' Day(s)</strong> <span class="badge badge-secondary" style="margin-left:6px; font-size:10px;"><i class="fa-solid fa-scissors"></i> ' + trimmedCount + ' Weekend(s) Trimmed</span>';
                sandwichBox.style.display = 'block';
                sandwichBox.style.background = '#f8fafc';
                sandwichBox.style.borderColor = '#e2e8f0';
                sandwichBox.style.color = '#475569';
                sandwichText.innerText = data.details || ('Weekend days excluded as not followed by leave.');
            } else {
                durText.innerText = totalDays.toFixed(1) + ' Working Day(s)';
                sandwichBox.style.display = 'none';
            }

            // Check against available quota
            if (sel.value) {
                const selOpt = sel.options[sel.selectedIndex];
                const code = selOpt.getAttribute('data-code') || '';
                const avail = parseFloat(selOpt.getAttribute('data-available') || 0);

                balChip.style.display = 'inline-flex';
                if (code === 'LOP') {
                    balChip.className = 'preview-chip';
                    balChip.style.background = '#f1f5f9';
                    balChip.style.color = '#475569';
                    balText.innerText = 'Unpaid Leave';
                } else if (avail >= totalDays) {
                    const rem = (avail - totalDays).toFixed(2);
                    balChip.className = 'preview-chip balance';
                    balChip.style.background = '';
                    balChip.style.color = '';
                    balText.innerText = 'Remaining Balance: ' + rem + 'd';
                } else {
                    balChip.className = 'preview-chip duration';
                    balChip.style.background = '#fff1f2';
                    balChip.style.color = '#be123c';
                    balText.innerText = 'Exceeds Balance (' + avail.toFixed(1) + 'd avail)';
                }
            } else {
                balChip.style.display = 'none';
            }

            preview.style.display = 'block';
        })
        .catch(err => {
            // Fallback to client-side simple difference if network glitch
            preview.style.display = 'block';
        });
}

// Cancel Modal handlers
function openCancelModal(reqId, typeLabel, days) {
    document.getElementById('cancel_request_id').value = reqId;
    document.getElementById('cancel_modal_days').innerText = days;
    document.getElementById('cancelLeaveModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelLeaveModal').style.display = 'none';
    document.getElementById('cancel_reason').value = '';
}

document.getElementById('from_date').addEventListener('change', function() {
    if (document.getElementById('is_half_day').checked) {
        document.getElementById('to_date').value = this.value;
    }
    updateDurationPreview();
});
document.getElementById('to_date').addEventListener('change', updateDurationPreview);

// History filter tabs
function filterHistory(status, tabBtn) {
    document.querySelectorAll('.history-filter-tab').forEach(b => b.classList.remove('active'));
    tabBtn.classList.add('active');

    const rows = document.querySelectorAll('.history-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noMatches = document.getElementById('no_filter_matches');
    if (noMatches) {
        noMatches.style.display = (visibleCount === 0) ? '' : 'none';
    }
}

// Auto-select first category (CL) on page load for immediate polish
document.addEventListener('DOMContentLoaded', function() {
    const firstPill = document.querySelector('.cat-pill-btn');
    if (firstPill) {
        selectCategory(firstPill.getAttribute('data-id'));
    }
});
</script>

<!-- Cancel Leave Modal Markup -->
<div class="modal-backdrop" id="cancelLeaveModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="max-width: 440px; width: 90%; margin: auto; background: #ffffff; border-radius: 12px; box-shadow: var(--shadow-xl); overflow: hidden;">
        <div class="modal-content">
            <div class="modal-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 700; color: #b91c1c; margin: 0;">
                    <i class="fa-solid fa-ban"></i> Cancel Leave Application
                </h3>
                <button type="button" style="border: none; background: transparent; font-size: 20px; color: #94a3b8; cursor: pointer;" onclick="closeCancelModal()">&times;</button>
            </div>
            <form action="<?= url('leaves/cancel') ?>" method="POST" id="cancelLeaveForm">
                <?= csrf_field() ?>
                <input type="hidden" name="request_id" id="cancel_request_id" value="">
                <div class="modal-body" style="padding: 20px;">
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px; color: #991b1b; line-height: 1.4;">
                        <i class="fa-solid fa-circle-info" style="margin-right: 6px;"></i>
                        Cancelling will restore <strong id="cancel_modal_days">--</strong> day(s) back to your available balance.
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="cancel_reason" style="font-weight: 600; font-size: 13px;">Reason for Cancellation (Optional)</label>
                        <textarea name="cancel_reason" id="cancel_reason" class="form-control" rows="3" placeholder="e.g. Schedule changed, work necessity..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeCancelModal()" style="font-weight: 600;">Keep Application</button>
                    <button type="submit" class="btn btn-danger btn-sm" style="font-weight: 700;">
                        <i class="fa-solid fa-ban"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
