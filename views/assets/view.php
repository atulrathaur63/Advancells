<?php
$pageTitle = 'Asset Details: ' . e($asset['name']);
require_once BASE_PATH . '/views/layouts/header.php';

$cat = $categories[$asset['category']] ?? ['label' => ucfirst($asset['category']), 'icon' => 'fa-box', 'color' => '#64748b'];
$cond = $conditions[$asset['condition']] ?? ['label' => ucfirst($asset['condition']), 'class' => 'badge-secondary'];
?>

<div style="margin-bottom: 20px;">
    <a href="<?= url('assets') ?>" class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to Asset Catalog
    </a>
</div>

<div class="grid-2">
    <!-- Left Column: Asset Specifications & Current Custody -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>; display: inline-flex; align-items: center; justify-content: center; font-size: 22px;">
                        <i class="fa-solid <?= $cat['icon'] ?>"></i>
                    </div>
                    <div>
                        <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 2px;">
                            <?= e($asset['name']) ?>
                        </h2>
                        <div style="font-size: 12px; color: var(--text-muted); font-family: monospace;">
                            <?= e($asset['asset_code']) ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                    <?php if ($asset['status'] === 'allocated'): ?>
                        <span class="badge badge-info"><i class="fa-solid fa-user-check"></i> Allocated</span>
                    <?php elseif ($asset['status'] === 'available'): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Available Pool</span>
                    <?php elseif ($asset['status'] === 'under_repair'): ?>
                        <span class="badge badge-warning"><i class="fa-solid fa-screwdriver-wrench"></i> In Repair</span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?= ucfirst($asset['status']) ?></span>
                    <?php endif; ?>
                    <span class="badge <?= $cond['class'] ?>"><?= $cond['label'] ?></span>
                </div>
            </div>

            <div class="card-body">
                <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-bottom: 12px;">
                    Hardware Specifications
                </h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; font-size: 13px;">
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Category</span>
                        <strong><?= e($cat['label']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Brand</span>
                        <strong><?= e($asset['brand'] ?: 'Not specified') ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Model</span>
                        <strong><?= e($asset['model'] ?: 'Not specified') ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Serial Number</span>
                        <code><?= e($asset['serial_number'] ?: 'N/A') ?></code>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Purchase Date</span>
                        <strong><?= format_date($asset['purchase_date']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Purchase Cost</span>
                        <strong style="color: #059669;"><?= format_currency($asset['purchase_cost']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Warranty Expiry</span>
                        <strong style="color: <?= !empty($asset['warranty_expiry']) && strtotime($asset['warranty_expiry']) < time() ? '#dc2626' : '#1e293b' ?>;">
                            <?= format_date($asset['warranty_expiry']) ?>
                        </strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 11.5px;">Current Condition</span>
                        <span class="badge <?= $cond['class'] ?>"><?= $cond['label'] ?></span>
                    </div>
                </div>

                <?php if (!empty($asset['notes'])): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 12.5px;">
                        <span style="font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Configuration & Hardware Notes:</span>
                        <p style="margin: 0; color: #475569;"><?= nl2br(e($asset['notes'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Current Custody Box -->
                <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-bottom: 10px;">
                    Current Assignment & Custody
                </h4>

                <?php if (!empty($asset['current_employee_id'])): ?>
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div>
                                <a href="<?= url('employees/view?id=' . $asset['current_employee_id']) ?>" style="font-weight: 800; color: #1e40af; font-size: 15px; text-decoration: none;">
                                    <?= e($asset['employee_name']) ?>
                                </a>
                                <div style="font-size: 12px; color: #3b82f6;">
                                    <code><?= e($asset['emp_code']) ?></code> • <?= e($asset['designation_title'] ?: 'Staff') ?> (<?= e($asset['department_name'] ?: 'General') ?>)
                                </div>
                            </div>
                            <span class="badge badge-info">Active Custodian</span>
                        </div>

                        <div style="font-size: 12px; color: #475569; border-top: 1px dashed #bfdbfe; padding-top: 10px;">
                            <div><i class="fa-regular fa-calendar" style="margin-right: 6px;"></i> Assigned Date: <strong><?= format_date($asset['allocated_date']) ?></strong></div>
                            <?php if (!empty($asset['expected_return_date'])): ?>
                                <div style="margin-top: 4px;"><i class="fa-regular fa-clock" style="margin-right: 6px;"></i> Expected Return: <strong><?= format_date($asset['expected_return_date']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($asset['allocation_notes'])): ?>
                                <div style="margin-top: 4px; font-style: italic;">"<?= e($asset['allocation_notes']) ?>"</div>
                            <?php endif; ?>
                        </div>

                        <?php if (Auth::isHR()): ?>
                            <div style="margin-top: 14px; display: flex; gap: 8px;">
                                <button type="button" class="btn btn-sm btn-warning" onclick="openReturnModal(<?= $asset['active_allocation_id'] ?>, '<?= e(addslashes($asset['asset_code'])) ?>', '<?= e(addslashes($asset['name'])) ?>', '<?= e(addslashes($asset['employee_name'])) ?>', '<?= $asset['condition'] ?>')">
                                    <i class="fa-solid fa-rotate-left"></i> Process Return
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: #166534; font-size: 14px; display: block;">Available in Storage Pool</strong>
                            <span style="font-size: 12px; color: #15803d;">Device is ready for deployment to new or existing staff.</span>
                        </div>
                        <?php if (Auth::isHR()): ?>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openModal('assignModal')">
                                <i class="fa-solid fa-user-plus"></i> Assign Now
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Actions Bar -->
                <?php if (Auth::isHR()): ?>
                    <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <a href="<?= url('assets/edit?id=' . $asset['id']) ?>" class="btn btn-sm btn-secondary">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Specifications
                        </a>
                        <?php if ($asset['status'] !== 'allocated'): ?>
                            <form action="<?= url('assets/delete') ?>" method="POST" onsubmit="return confirmAction('Are you sure you want to permanently remove this asset?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $asset['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa-solid fa-trash-can"></i> Delete Asset
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Handover & Return Lifecycle Timeline History -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-timeline text-primary"></i> Lifecycle & Custody History (<?= count($history) ?>)
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div style="text-align: center; padding: 36px; color: var(--text-muted);">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 32px; margin-bottom: 8px; color: #cbd5e1; display: block;"></i>
                        <p style="font-size: 13px; margin: 0;">No allocation history recorded yet for this asset.</p>
                    </div>
                <?php else: ?>
                    <div style="position: relative; padding-left: 24px; border-left: 2px solid #e2e8f0; margin-left: 10px;">
                        <?php foreach ($history as $h): ?>
                            <div style="position: relative; margin-bottom: 24px;">
                                <!-- Bullet icon -->
                                <div style="position: absolute; left: -31px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: <?= ($h['status'] === 'active') ? '#0284c7' : '#10b981' ?>; border: 3px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></div>

                                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                        <div>
                                            <a href="<?= url('employees/view?id=' . $h['employee_id']) ?>" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                                <?= e($h['employee_name']) ?>
                                            </a>
                                            <span style="font-size: 11.5px; color: var(--text-muted); font-family: monospace; margin-left: 4px;">
                                                (<?= e($h['emp_code']) ?>)
                                            </span>
                                        </div>

                                        <?php if ($h['status'] === 'active'): ?>
                                            <span class="badge badge-info" style="font-size: 10px;">Currently Holding</span>
                                        <?php else: ?>
                                            <span class="badge badge-success" style="font-size: 10px;">Returned & Cleared</span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="font-size: 12px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                                        <div>
                                            <i class="fa-solid fa-arrow-right-to-bracket text-primary" style="font-size: 10px; margin-right: 4px;"></i>
                                            Assigned: <strong><?= format_date($h['allocated_date']) ?></strong> by <?= e($h['allocated_by_name'] ?: 'HR Operations') ?>
                                        </div>

                                        <?php if ($h['status'] === 'returned'): ?>
                                            <div>
                                                <i class="fa-solid fa-arrow-right-from-bracket text-success" style="font-size: 10px; margin-right: 4px;"></i>
                                                Returned: <strong><?= format_date($h['returned_date']) ?></strong> (Condition: <em><?= e(ucfirst($h['return_condition'] ?? 'good')) ?></em>)
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($h['allocation_notes'])): ?>
                                            <div style="font-size: 11.5px; color: #64748b; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; margin-top: 4px;">
                                                <strong>Handover note:</strong> <?= e($h['allocation_notes']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($h['return_notes'])): ?>
                                            <div style="font-size: 11.5px; color: #059669; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 6px 8px; margin-top: 4px;">
                                                <strong>Return note:</strong> <?= e($h['return_notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="margin: 0;"><i class="fa-solid fa-hand-holding-hand text-primary"></i> Assign <?= e($asset['name']) ?></h3>
            <button type="button" class="btn-close" onclick="closeModal('assignModal')" style="border:0; background:transparent; font-size:18px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form action="<?= url('assets/allocate') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="asset_id" value="<?= $asset['id'] ?>">
            <input type="hidden" name="redirect_to" value="<?= url('assets/view?id=' . $asset['id']) ?>">

            <div class="form-group">
                <label class="form-label">Assign To Employee *</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">-- Choose employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>">
                            <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_code']) ?> - <?= e($emp['department_name'] ?: 'No Dept') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Handover Date *</label>
                    <input type="date" name="allocated_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Expected Return Date</label>
                    <input type="date" name="expected_return_date" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Handover Notes / Condition</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Issued with power brick, sleeve, security cable..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Complete Handover</button>
            </div>
        </form>
    </div>
</div>

<!-- Return Modal -->
<div id="returnModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="margin: 0;"><i class="fa-solid fa-rotate-left text-warning"></i> Confirm Device Return</h3>
            <button type="button" class="btn-close" onclick="closeModal('returnModal')" style="border:0; background:transparent; font-size:18px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form action="<?= url('assets/return') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="allocation_id" id="modal_allocation_id">
            <input type="hidden" name="redirect_to" value="<?= url('assets/view?id=' . $asset['id']) ?>">

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Return Date *</label>
                    <input type="date" name="returned_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Condition upon Return *</label>
                    <select name="return_condition" id="modal_condition" class="form-control" required>
                        <option value="good">Good Condition</option>
                        <option value="brand_new">Like Brand New</option>
                        <option value="fair">Fair (Wear & Tear)</option>
                        <option value="damaged">Damaged / Needs Servicing</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Next Asset Status</label>
                <select name="next_status" class="form-control">
                    <option value="available" selected>Return to Available Storage Pool</option>
                    <option value="under_repair">Send for Repair / Service</option>
                    <option value="retired">Retire / Decommission</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Inspection & Handover Remarks</label>
                <textarea name="return_notes" class="form-control" rows="2" placeholder="e.g. Returned clean, cables intact, checked functional..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('returnModal')">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-check"></i> Process Return</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function openReturnModal(allocationId, code, name, custodian, condition) {
    document.getElementById('modal_allocation_id').value = allocationId;
    document.getElementById('modal_condition').value = condition || 'good';
    openModal('returnModal');
}

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
