<?php
$pageTitle = 'Edit Employee Leave Balances';
require_once BASE_PATH . '/views/layouts/header.php';

$employees = $data['employees'] ?? [];
$leaveTypes = $data['leave_types'] ?? [];
$selectedYear = $data['year'] ?? (int)date('Y');
$totalEmployees = count($employees);
?>

<style>
/* ====== Edit Balances Page Styles ====== */
.eb-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}
.eb-page-title-group {
    display: flex; align-items: center; gap: 14px;
}
.eb-page-icon {
    width: 42px; height: 42px; border-radius: 12px;
    background: linear-gradient(135deg, #0284c7, #0ea5e9);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.eb-page-title {
    font-family: var(--font-heading); font-size: 18px; font-weight: 700;
    color: var(--text-main); margin: 0; line-height: 1.2;
}
.eb-page-subtitle {
    font-size: 12px; color: var(--text-muted); margin-top: 2px;
}
.eb-controls {
    display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
}
.eb-select {
    padding: 7px 12px; font-size: 12px; border: 1px solid #e2e8f0;
    border-radius: 8px; background: #fff; color: var(--text-main);
    cursor: pointer; font-weight: 500; outline: none;
    transition: border-color 0.15s;
}
.eb-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(2,132,199,0.08); }
.eb-back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; font-size: 12px; font-weight: 600;
    color: #475569; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 8px; text-decoration: none; transition: all 0.15s;
}
.eb-back-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }

/* Employee Card Layout */
.eb-emp-card {
    background: #fff; border: 1px solid #e8ecf1; border-radius: 12px;
    margin-bottom: 14px; overflow: hidden;
    transition: box-shadow 0.2s, border-color 0.2s;
}
.eb-emp-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); border-color: #cbd5e1; }
.eb-emp-card.is-self { border-left: 3px solid #f59e0b; background: #fffdf7; }

.eb-emp-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid #f1f5f9;
}
.eb-emp-info { display: flex; align-items: center; gap: 12px; }
.eb-emp-avatar {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #93206c, #0284c7);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px; flex-shrink: 0; letter-spacing: 0.02em;
}
.eb-emp-name { font-size: 13.5px; font-weight: 600; color: #1e293b; }
.eb-emp-meta { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
.eb-emp-meta code { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 10.5px; background: #f1f5f9; padding: 1px 5px; border-radius: 3px; }
.eb-self-badge {
    font-size: 9px; font-weight: 700; background: #fef3c7; color: #92400e;
    border: 1px solid #fcd34d; padding: 2px 7px; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.eb-emp-dept {
    font-size: 10.5px; font-weight: 600; background: #f0f9ff; color: #0369a1;
    border: 1px solid #bae6fd; padding: 2px 8px; border-radius: 6px;
}

/* Balance Grid inside each card */
.eb-balance-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0; padding: 0;
}
.eb-balance-cell {
    padding: 14px 16px; border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9; text-align: center;
    position: relative;
    transition: background 0.15s;
}
.eb-balance-cell:hover { background: #fafbfd; }
.eb-balance-cell:last-child { border-right: none; }

.eb-bal-type {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 6px;
}
.eb-bal-type .eb-code {
    font-weight: 700; color: #475569;
}
.eb-bal-available {
    font-family: var(--font-heading); font-size: 22px; font-weight: 700;
    line-height: 1.1; letter-spacing: -0.02em;
}
.eb-bal-available.positive { color: #059669; }
.eb-bal-available.zero { color: #94a3b8; }
.eb-bal-available.negative { color: #e11d48; }

.eb-bal-breakdown {
    font-size: 10px; color: var(--text-muted); margin-top: 4px; line-height: 1.5;
}
.eb-bal-breakdown span { font-weight: 600; }

/* Compact progress bar */
.eb-mini-bar { width: 100%; height: 4px; background: #f1f5f9; border-radius: 99px; margin-top: 6px; overflow: hidden; }
.eb-mini-bar-fill { height: 100%; border-radius: 99px; transition: width 0.3s ease; }

.eb-edit-btn {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 8px; font-size: 10.5px; font-weight: 600;
    padding: 3px 10px; border-radius: 6px;
    background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7;
    cursor: pointer; transition: all 0.15s;
}
.eb-edit-btn:hover { background: #e0f2fe; border-color: #7dd3fc; }

.eb-locked-badge {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 8px; font-size: 9.5px; font-weight: 700;
    padding: 3px 10px; border-radius: 6px;
    background: #fef3c7; border: 1px solid #fcd34d; color: #92400e;
    letter-spacing: 0.02em;
}

/* Empty state */
.eb-empty {
    padding: 60px 20px; text-align: center;
}
.eb-empty-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center;
    font-size: 22px; color: #94a3b8; margin-bottom: 14px;
}
.eb-empty h3 { font-size: 15px; font-weight: 600; color: var(--text-main); margin: 0 0 4px; }
.eb-empty p { font-size: 12px; color: var(--text-muted); margin: 0; }

/* Stat pills at top */
.eb-stat-strip {
    display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px;
}
.eb-stat-pill {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 10px;
    background: #fff; border: 1px solid #e8ecf1;
    flex: 1; min-width: 140px;
}
.eb-stat-pill-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.eb-stat-pill-val { font-size: 18px; font-weight: 700; color: var(--text-main); line-height: 1; }
.eb-stat-pill-label { font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
</style>

<!-- Page Header -->
<div class="eb-page-header">
    <div class="eb-page-title-group">
        <div class="eb-page-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div>
            <h1 class="eb-page-title">Leave Balance Editor</h1>
            <p class="eb-page-subtitle"><?= $totalEmployees ?> active employee<?= $totalEmployees !== 1 ? 's' : '' ?> • FY <?= $selectedYear ?></p>
        </div>
    </div>
    <div class="eb-controls">
        <!-- Year selector -->
        <form method="GET" action="<?= url('leaves/edit-balances') ?>" style="display: flex; gap: 6px;">
            <select name="year" class="eb-select" onchange="this.form.submit()">
                <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <?php if ($filterEmpId): ?>
                <input type="hidden" name="employee_id" value="<?= $filterEmpId ?>">
            <?php endif; ?>
        </form>

        <!-- Employee filter -->
        <form method="GET" action="<?= url('leaves/edit-balances') ?>" style="display: flex; gap: 6px;">
            <input type="hidden" name="year" value="<?= $selectedYear ?>">
            <select name="employee_id" class="eb-select" style="min-width: 210px;" onchange="this.form.submit()">
                <option value="">All Employees</option>
                <?php foreach ($allEmployees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($filterEmpId == $emp['id']) ? 'selected' : '' ?>>
                        <?= e($emp['emp_code']) ?> — <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="<?= url('leaves/approvals') ?>" class="eb-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Approvals
        </a>
    </div>
</div>

<!-- Quick Stats -->
<?php
$totalLeaveTypes = count($leaveTypes);
$editableCount = 0;
foreach ($employees as $row) {
    if ($isSuperAdmin || (int)$row['employee']['id'] !== $currentEmpId) $editableCount++;
}
?>
<div class="eb-stat-strip">
    <div class="eb-stat-pill">
        <div class="eb-stat-pill-icon" style="background: #f0f9ff; color: #0284c7;"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="eb-stat-pill-val"><?= $totalEmployees ?></div>
            <div class="eb-stat-pill-label">Employees</div>
        </div>
    </div>
    <div class="eb-stat-pill">
        <div class="eb-stat-pill-icon" style="background: #f0fdf4; color: #059669;"><i class="fa-solid fa-pen-to-square"></i></div>
        <div>
            <div class="eb-stat-pill-val"><?= $editableCount ?></div>
            <div class="eb-stat-pill-label">Editable</div>
        </div>
    </div>
    <div class="eb-stat-pill">
        <div class="eb-stat-pill-icon" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="eb-stat-pill-val"><?= $totalLeaveTypes ?></div>
            <div class="eb-stat-pill-label">Leave Types</div>
        </div>
    </div>
    <div class="eb-stat-pill">
        <div class="eb-stat-pill-icon" style="background: #fdf2f8; color: #be185d;"><i class="fa-solid fa-calendar"></i></div>
        <div>
            <div class="eb-stat-pill-val"><?= $selectedYear ?></div>
            <div class="eb-stat-pill-label">Fiscal Year</div>
        </div>
    </div>
</div>

<!-- Employee Cards -->
<?php if (empty($employees)): ?>
    <div class="eb-empty">
        <div class="eb-empty-icon"><i class="fa-solid fa-users-slash"></i></div>
        <h3>No employees found</h3>
        <p>Try changing the year or employee filter above.</p>
    </div>
<?php else: ?>
    <?php foreach ($employees as $row):
        $emp = $row['employee'];
        $balances = $row['balances'];
        $isSelf = !$isSuperAdmin && (int)$emp['id'] === $currentEmpId;

        // Index balances by leave_type_id
        $balByType = [];
        foreach ($balances as $b) {
            $balByType[$b['leave_type_id']] = $b;
        }
    ?>
        <div class="eb-emp-card<?= $isSelf ? ' is-self' : '' ?>">
            <!-- Employee Header -->
            <div class="eb-emp-header">
                <div class="eb-emp-info">
                    <div class="eb-emp-avatar"><?= strtoupper(substr($emp['full_name'], 0, 2)) ?></div>
                    <div>
                        <div class="eb-emp-name">
                            <?= e($emp['full_name']) ?>
                            <?php if ($isSelf): ?>
                                <span class="eb-self-badge">You</span>
                            <?php endif; ?>
                        </div>
                        <div class="eb-emp-meta">
                            <code><?= e($emp['emp_code']) ?></code>
                            <?php if (!empty($emp['department_name'])): ?>
                                &nbsp;•&nbsp; <?= e($emp['department_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($emp['department_name'])): ?>
                    <span class="eb-emp-dept"><?= e($emp['department_name']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Balance Grid -->
            <div class="eb-balance-grid">
                <?php foreach ($leaveTypes as $lt):
                    $bal = $balByType[$lt['id']] ?? null;
                    $allocated = $bal ? (float)$bal['total_allocated'] : 0;
                    $used = $bal ? (float)$bal['used'] : 0;
                    $pending = $bal ? (float)$bal['pending'] : 0;
                    $available = $allocated - $used;
                    $usedPct = $allocated > 0 ? min(100, round(($used / $allocated) * 100)) : 0;
                    $pendPct = $allocated > 0 ? min(100 - $usedPct, round(($pending / $allocated) * 100)) : 0;
                    $colorClass = $available > 0 ? 'positive' : ($available < 0 ? 'negative' : 'zero');
                    $barColor = $available > 2 ? '#22c55e' : ($available > 0 ? '#f59e0b' : '#ef4444');
                ?>
                    <div class="eb-balance-cell">
                        <div class="eb-bal-type"><span class="eb-code"><?= e($lt['code']) ?></span> — <?= e($lt['name']) ?></div>

                        <?php if ($bal): ?>
                            <div class="eb-bal-available <?= $colorClass ?>"><?= number_format($available, 1) ?></div>
                            <div class="eb-bal-breakdown">
                                Alloc: <span><?= number_format($allocated, 1) ?></span>
                                &nbsp;·&nbsp; Used: <span><?= number_format($used, 1) ?></span>
                                <?php if (!empty($bal['carried_forward']) && (float)$bal['carried_forward'] > 0): ?>
                                    &nbsp;·&nbsp; C/F: <span style="color: #0284c7;"><?= number_format((float)$bal['carried_forward'], 1) ?></span>
                                <?php endif; ?>
                                <?php if ($pending > 0): ?>
                                    &nbsp;·&nbsp; Pend: <span style="color: #d97706;"><?= number_format($pending, 1) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="eb-mini-bar">
                                <div class="eb-mini-bar-fill" style="width: <?= $usedPct + $pendPct ?>%; background: <?= $barColor ?>;"></div>
                            </div>
                            <?php if (!$isSelf): ?>
                                <button type="button" class="eb-edit-btn"
                                    onclick="openEditModal(<?= $bal['id'] ?>, '<?= e($emp['full_name']) ?>', '<?= e($lt['name']) ?>', <?= $allocated ?>, <?= $used ?>, <?= $pending ?>)">
                                    <i class="fa-solid fa-pen" style="font-size: 9px;"></i> Adjust
                                </button>
                            <?php else: ?>
                                <span class="eb-locked-badge">
                                    <i class="fa-solid fa-lock" style="font-size: 8px;"></i> Self-Edit Blocked
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="eb-bal-available zero">—</div>
                            <div class="eb-bal-breakdown">Not configured</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


<!-- Edit Balance Modal -->
<div id="editBalanceModal" class="modal-backdrop-custom" style="display: none;">
    <div class="modal-dialog-compact" style="max-width: 440px;">
        <div class="modal-header-custom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="modal-header-icon" style="background: #f0f9ff; color: #0284c7;">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <h3 class="modal-title-custom" id="editModalTitle">Edit Leave Balance</h3>
                    <div class="modal-subtitle-custom" id="editModalSubtitle">Adjusting balance</div>
                </div>
            </div>
            <button type="button" class="modal-close-custom" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="<?= url('leaves/edit-balances') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="balance_id" id="editBalanceId">
            <input type="hidden" name="year" value="<?= $selectedYear ?>">

            <div style="padding: 18px 20px;">
                <!-- Current stats -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 16px;">
                    <div style="background: #f0fdf4; border-radius: 8px; padding: 10px; text-align: center;">
                        <div style="font-size: 10px; color: #065f46; font-weight: 600; text-transform: uppercase;">Allocated</div>
                        <div id="editStatAllocated" style="font-size: 18px; font-weight: 700; color: #059669;">0</div>
                    </div>
                    <div style="background: #fff1f2; border-radius: 8px; padding: 10px; text-align: center;">
                        <div style="font-size: 10px; color: #9f1239; font-weight: 600; text-transform: uppercase;">Used</div>
                        <div id="editStatUsed" style="font-size: 18px; font-weight: 700; color: #e11d48;">0</div>
                    </div>
                    <div style="background: #fffbeb; border-radius: 8px; padding: 10px; text-align: center;">
                        <div style="font-size: 10px; color: #92400e; font-weight: 600; text-transform: uppercase;">Pending</div>
                        <div id="editStatPending" style="font-size: 18px; font-weight: 700; color: #d97706;">0</div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="total_allocated">New Allocated Days *</label>
                    <input type="number" step="0.5" min="0" max="365" name="total_allocated" id="editAllocatedInput" class="form-control" required>
                    <small style="color: var(--text-muted); font-size: 11px;" id="editMinNote">Minimum: 0 (used + pending)</small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="reason">Reason for Adjustment</label>
                    <input type="text" name="reason" class="form-control" placeholder="e.g., Carry-forward, Special approval, Policy update" maxlength="255">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #f1f5f9;">
                <button type="button" onclick="closeEditModal()" style="background: none; border: none; padding: 14px; font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer; border-right: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='none'">
                    Cancel
                </button>
                <button type="submit" style="background: none; border: none; padding: 14px; font-size: 13px; font-weight: 600; color: #0284c7; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='none'">
                    <i class="fa-solid fa-check" style="font-size: 11px; margin-right: 4px;"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(balanceId, empName, leaveType, allocated, used, pending) {
    document.getElementById('editBalanceId').value = balanceId;
    document.getElementById('editModalTitle').textContent = 'Edit ' + leaveType;
    document.getElementById('editModalSubtitle').textContent = empName;
    document.getElementById('editStatAllocated').textContent = allocated.toFixed(1);
    document.getElementById('editStatUsed').textContent = used.toFixed(1);
    document.getElementById('editStatPending').textContent = pending.toFixed(1);

    var minVal = (used + pending);
    document.getElementById('editAllocatedInput').value = allocated.toFixed(1);
    document.getElementById('editAllocatedInput').min = minVal.toFixed(1);
    document.getElementById('editMinNote').textContent = 'Minimum: ' + minVal.toFixed(1) + ' (used + pending)';

    document.getElementById('editBalanceModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editBalanceModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('editBalanceModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
