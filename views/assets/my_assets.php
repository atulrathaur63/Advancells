<?php
$pageTitle = 'My Assigned Assets & Devices';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Header Info Banner -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #1a0d18 0%, #0b1220 55%, #0a1a1c 100%); color: #fff; border: 0;">
    <div class="card-body" style="padding: 28px 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <span class="badge" style="background: rgba(255,255,255,0.15); color: #fff; margin-bottom: 10px; font-weight: 600;">
                <i class="fa-solid fa-laptop-medical" style="margin-right: 4px;"></i> Company Property Custody
            </span>
            <h2 style="font-size: 24px; font-weight: 800; margin: 0 0 6px; letter-spacing: -0.02em;">
                My Assigned Assets & Equipment
            </h2>
            <p style="color: rgba(255,255,255,0.75); font-size: 13.5px; max-width: 580px; margin: 0; line-height: 1.5;">
                Company hardware, clinical lab devices, and access badges assigned to your care. Please maintain devices in good order and report any damage immediately.
            </p>
        </div>

        <div style="background: rgba(255,255,255,0.08); padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.12); text-align: center;">
            <div style="font-size: 28px; font-weight: 800; color: #fff;"><?= count($activeAssets) ?></div>
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.7); font-weight: 600;">Active Devices</div>
        </div>
    </div>
</div>

<!-- Active Issued Devices -->
<div style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">
            <i class="fa-solid fa-laptop text-primary" style="margin-right: 8px;"></i> Currently Assigned Hardware (<?= count($activeAssets) ?>)
        </h3>
        <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('reportIssueModal')">
            <i class="fa-solid fa-triangle-exclamation text-warning"></i> Report Hardware Issue
        </button>
    </div>

    <?php if (empty($activeAssets)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 48px; color: var(--text-muted);">
                <i class="fa-solid fa-box-open" style="font-size: 40px; margin-bottom: 12px; color: #94a3b8; display: block;"></i>
                <h4 style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 4px;">No Company Assets Assigned</h4>
                <p style="font-size: 13.5px; margin: 0;">You do not have any company laptops, tablets, or lab tools currently registered in your custody.</p>
            </div>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px;">
            <?php foreach ($activeAssets as $ast): ?>
                <?php 
                    $cat = $categories[$ast['category']] ?? ['label' => ucfirst($ast['category']), 'icon' => 'fa-box', 'color' => '#64748b'];
                    $cond = $conditions[$ast['condition']] ?? ['label' => ucfirst($ast['condition']), 'class' => 'badge-secondary'];
                ?>
                <div class="card" style="position: relative; overflow: hidden; border-top: 3px solid <?= $cat['color'] ?>; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="card-body" style="padding: 22px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>; display: inline-flex; align-items: center; justify-content: center; font-size: 20px;">
                                <i class="fa-solid <?= $cat['icon'] ?>"></i>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <span class="badge" style="background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>; font-weight: 700; font-size: 11px;">
                                    <?= e($cat['label']) ?>
                                </span>
                                <span class="badge <?= $cond['class'] ?>" style="font-size: 10px;">
                                    <?= e($cond['label']) ?>
                                </span>
                            </div>
                        </div>

                        <h4 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 4px; letter-spacing: -0.02em;">
                            <?= e($ast['name']) ?>
                        </h4>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">
                            <?= !empty($ast['brand']) ? e($ast['brand']) : '' ?> <?= !empty($ast['model']) ? '• ' . e($ast['model']) : '' ?>
                        </div>

                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 6px; font-size: 12px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Asset Code:</span>
                                <code><?= e($ast['asset_code']) ?></code>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Serial Number:</span>
                                <code style="font-weight: 600;"><?= e($ast['serial_number'] ?: 'N/A') ?></code>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Issued On:</span>
                                <strong><?= format_date($ast['allocated_date']) ?></strong>
                            </div>
                            <?php if (!empty($ast['expected_return_date'])): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Return Due:</span>
                                    <strong style="color: #dc2626;"><?= format_date($ast['expected_return_date']) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($ast['allocation_notes'])): ?>
                            <div style="font-size: 11.5px; color: #475569; background: #fffbeb; border-left: 3px solid #f59e0b; padding: 6px 10px; border-radius: 4px; margin-bottom: 10px;">
                                <i class="fa-solid fa-circle-info text-warning" style="margin-right: 4px;"></i> <?= e($ast['allocation_notes']) ?>
                            </div>
                        <?php endif; ?>

                        <div style="font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 10px;">
                            <span>Issued by: <?= e($ast['allocated_by_name'] ?: 'IT Operations') ?></span>
                            <span style="color: #059669; font-weight: 600;"><i class="fa-solid fa-shield-check"></i> Verified</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Past Returned Equipment History -->
<?php if (!empty($pastAssets)): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Past Returned Equipment History</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Device & Asset Code</th>
                            <th>Category</th>
                            <th>Serial Number</th>
                            <th>Issued On</th>
                            <th>Returned On</th>
                            <th>Return Condition</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pastAssets as $pa): ?>
                            <?php 
                                $cat = $categories[$pa['category']] ?? ['label' => ucfirst($pa['category']), 'icon' => 'fa-box', 'color' => '#64748b'];
                                $cond = $conditions[$pa['return_condition']] ?? ['label' => ucfirst($pa['return_condition'] ?? 'Good'), 'class' => 'badge-secondary'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($pa['name']) ?></strong>
                                    <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= e($pa['asset_code']) ?></div>
                                </td>
                                <td>
                                    <span style="font-size: 12px; font-weight: 600; color: #334155;">
                                        <i class="fa-solid <?= $cat['icon'] ?>" style="color: <?= $cat['color'] ?>; margin-right: 4px;"></i>
                                        <?= e($cat['label']) ?>
                                    </span>
                                </td>
                                <td><code><?= e($pa['serial_number'] ?: '--') ?></code></td>
                                <td><?= format_date($pa['allocated_date']) ?></td>
                                <td><strong style="color: #059669;"><?= format_date($pa['returned_date']) ?></strong></td>
                                <td><span class="badge <?= $cond['class'] ?>"><?= $cond['label'] ?></span></td>
                                <td><?= e($pa['received_by_name'] ?: 'IT Admin') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Report Issue Modal -->
<div id="reportIssueModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="margin: 0;"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Report Hardware Issue or Loss</h3>
            <button type="button" class="btn-close" onclick="closeModal('reportIssueModal')" style="border:0; background:transparent; font-size:18px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <div class="card-body" style="padding: 22px;">
            <p style="font-size: 13.5px; color: #334155; line-height: 1.5; margin-bottom: 16px;">
                If your company laptop, monitor, or cleanroom access badge is damaged, malfunctioning, or lost, please notify IT and HR immediately:
            </p>

            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 13px;">
                <div style="margin-bottom: 8px;"><strong><i class="fa-solid fa-envelope text-primary" style="margin-right: 6px;"></i> IT Helpdesk:</strong> it-support@advancells.com</div>
                <div style="margin-bottom: 8px;"><strong><i class="fa-solid fa-phone text-primary" style="margin-right: 6px;"></i> Internal Extension:</strong> Ext 402 (IT Room, 2nd Floor)</div>
                <div><strong><i class="fa-solid fa-user-shield text-primary" style="margin-right: 6px;"></i> Lab Safety Officer:</strong> Ext 104 (Bio-safety Suite)</div>
            </div>

            <div class="alert alert-warning" style="margin-bottom: 0;">
                <small><strong>Note:</strong> In the event of a lost access badge or laptop containing company clinical research files, report immediately so access keys and session tokens can be revoked.</small>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('reportIssueModal')">Understood</button>
            </div>
        </div>
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

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
