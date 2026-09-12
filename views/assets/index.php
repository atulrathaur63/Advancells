<?php
$pageTitle = 'Company Asset & Device Management';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- KPI Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Inventory</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-laptop-user"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['allocated'] ?></div>
            <div class="stat-label">Allocated to Staff</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['available'] ?></div>
            <div class="stat-label">Available in Pool</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['under_repair'] ?></div>
            <div class="stat-label">In Repair / Service</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="stat-info">
            <div class="stat-value" style="font-size: 20px;"><?= format_currency($stats['total_valuation']) ?></div>
            <div class="stat-label">Total Fleet Valuation</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <h2 class="card-title" style="margin: 0;"><i class="fa-solid fa-laptop-code text-primary"></i> Asset Catalog & Device Allocation</h2>
            <span class="badge badge-info" style="font-size: 11px;"><?= $pagination['total_items'] ?? count($assets) ?> Items</span>
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php if (Auth::isHR()): ?>
                <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addAssetModal')">
                    <i class="fa-solid fa-plus"></i> Register Asset
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('assignAssetModal')">
                    <i class="fa-solid fa-hand-holding-hand"></i> Assign Device
                </button>
            <?php endif; ?>
            <a href="<?= url('assets?' . http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Filter Toolbar -->
        <form method="GET" action="<?= url('assets') ?>" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label class="form-label" style="font-size: 11.5px; margin-bottom: 4px;">Search Keyword</label>
                <input type="text" name="search" class="form-control" style="height: 38px; font-size: 13px;" placeholder="Code, device, brand, serial, employee..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>

            <div style="min-width: 160px;">
                <label class="form-label" style="font-size: 11.5px; margin-bottom: 4px;">Category</label>
                <select name="category" class="form-control" style="height: 38px; font-size: 13px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $catKey => $catMeta): ?>
                        <option value="<?= $catKey ?>" <?= (($_GET['category'] ?? '') === $catKey) ? 'selected' : '' ?>>
                            <?= e($catMeta['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="min-width: 140px;">
                <label class="form-label" style="font-size: 11.5px; margin-bottom: 4px;">Status</label>
                <select name="status" class="form-control" style="height: 38px; font-size: 13px;">
                    <option value="">All Statuses</option>
                    <option value="available" <?= (($_GET['status'] ?? '') === 'available') ? 'selected' : '' ?>>Available (Pool)</option>
                    <option value="allocated" <?= (($_GET['status'] ?? '') === 'allocated') ? 'selected' : '' ?>>Allocated</option>
                    <option value="under_repair" <?= (($_GET['status'] ?? '') === 'under_repair') ? 'selected' : '' ?>>Under Repair</option>
                    <option value="lost" <?= (($_GET['status'] ?? '') === 'lost') ? 'selected' : '' ?>>Lost / Missing</option>
                    <option value="retired" <?= (($_GET['status'] ?? '') === 'retired') ? 'selected' : '' ?>>Retired</option>
                </select>
            </div>

            <div style="min-width: 140px;">
                <label class="form-label" style="font-size: 11.5px; margin-bottom: 4px;">Condition</label>
                <select name="condition" class="form-control" style="height: 38px; font-size: 13px;">
                    <option value="">All Conditions</option>
                    <option value="brand_new" <?= (($_GET['condition'] ?? '') === 'brand_new') ? 'selected' : '' ?>>Brand New</option>
                    <option value="good" <?= (($_GET['condition'] ?? '') === 'good') ? 'selected' : '' ?>>Good</option>
                    <option value="fair" <?= (($_GET['condition'] ?? '') === 'fair') ? 'selected' : '' ?>>Fair</option>
                    <option value="damaged" <?= (($_GET['condition'] ?? '') === 'damaged') ? 'selected' : '' ?>>Damaged</option>
                </select>
            </div>

            <div style="display: flex; gap: 6px;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 38px; padding: 0 16px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= url('assets') ?>" class="btn btn-secondary btn-sm" style="height: 38px; padding: 0 12px;" title="Reset filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>

        <!-- Assets Data Table -->
        <?php if (empty($assets)): ?>
            <div style="text-align: center; padding: 48px; color: var(--text-muted);">
                <i class="fa-solid fa-laptop-code" style="font-size: 36px; margin-bottom: 12px; color: #94a3b8; display: block;"></i>
                <h3 style="font-size: 16px; font-weight: 600; color: #334155; margin-bottom: 4px;">No matching assets found</h3>
                <p style="font-size: 13px; margin: 0;">Try adjusting your search criteria or register a new piece of equipment.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset Code & Device</th>
                            <th>Category</th>
                            <th>Serial Number</th>
                            <th>Status & Condition</th>
                            <th>Current Custodian</th>
                            <th>Valuation</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $a): ?>
                            <?php 
                                $catInfo = $categories[$a['category']] ?? ['label' => ucfirst($a['category']), 'icon' => 'fa-box', 'color' => '#64748b'];
                                $condInfo = $conditions[$a['condition']] ?? ['label' => ucfirst($a['condition']), 'class' => 'badge-secondary'];
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 8px; background: <?= $catInfo['color'] ?>15; color: <?= $catInfo['color'] ?>; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                                            <i class="fa-solid <?= $catInfo['icon'] ?>"></i>
                                        </div>
                                        <div>
                                            <a href="<?= url('assets/view?id=' . $a['id']) ?>" style="font-weight: 700; color: var(--brand-dark); text-decoration: none;">
                                                <?= e($a['name']) ?>
                                            </a>
                                            <div style="font-size: 11.5px; color: var(--text-muted); font-family: monospace;">
                                                <?= e($a['asset_code']) ?> <?= !empty($a['brand']) ? '• ' . e($a['brand']) : '' ?> <?= !empty($a['model']) ? '(' . e($a['model']) . ')' : '' ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: #334155;">
                                        <i class="fa-solid <?= $catInfo['icon'] ?>" style="color: <?= $catInfo['color'] ?>;"></i>
                                        <?= e($catInfo['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($a['serial_number'])): ?>
                                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0f172a;"><?= e($a['serial_number']) ?></code>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px;">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <div>
                                            <?php if ($a['status'] === 'allocated'): ?>
                                                <span class="badge badge-info"><i class="fa-solid fa-user-check" style="font-size: 10px; margin-right: 3px;"></i> Allocated</span>
                                            <?php elseif ($a['status'] === 'available'): ?>
                                                <span class="badge badge-success"><i class="fa-solid fa-circle-check" style="font-size: 10px; margin-right: 3px;"></i> Available</span>
                                            <?php elseif ($a['status'] === 'under_repair'): ?>
                                                <span class="badge badge-warning"><i class="fa-solid fa-screwdriver-wrench" style="font-size: 10px; margin-right: 3px;"></i> In Repair</span>
                                            <?php elseif ($a['status'] === 'lost'): ?>
                                                <span class="badge badge-danger"><i class="fa-solid fa-circle-exclamation" style="font-size: 10px; margin-right: 3px;"></i> Missing</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= ucfirst($a['status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="badge <?= $condInfo['class'] ?>" style="font-size: 10px;"><?= $condInfo['label'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($a['current_employee_id'])): ?>
                                        <div>
                                            <a href="<?= url('employees/view?id=' . $a['current_employee_id']) ?>" style="font-weight: 600; color: #1e293b; text-decoration: none;">
                                                <?= e($a['employee_name']) ?>
                                            </a>
                                            <div style="font-size: 11px; color: var(--text-muted);">
                                                <code><?= e($a['emp_code']) ?></code> • <?= e($a['department_name'] ?: 'General') ?>
                                            </div>
                                            <div style="font-size: 10.5px; color: #0284c7; margin-top: 2px;">
                                                <i class="fa-regular fa-calendar" style="font-size: 9px;"></i> Assigned <?= format_date($a['allocated_date']) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px; font-style: italic;">In IT Storage Pool</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= format_currency($a['purchase_cost']) ?></strong>
                                    <?php if (!empty($a['warranty_expiry'])): ?>
                                        <div style="font-size: 10.5px; color: <?= strtotime($a['warranty_expiry']) < time() ? '#dc2626' : '#64748b' ?>;">
                                            Warranty: <?= format_date($a['warranty_expiry']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 4px; align-items: center;">
                                        <a href="<?= url('assets/view?id=' . $a['id']) ?>" class="btn btn-sm btn-secondary" title="View Full Specs & Handover Log" style="padding: 5px 8px;">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <?php if (Auth::isHR()): ?>
                                            <?php if ($a['status'] === 'allocated' && !empty($a['active_allocation_id'])): ?>
                                                <button type="button" class="btn btn-sm btn-warning" title="Process Asset Return" 
                                                        onclick="openReturnModal(<?= $a['active_allocation_id'] ?>, '<?= e(addslashes($a['asset_code'])) ?>', '<?= e(addslashes($a['name'])) ?>', '<?= e(addslashes($a['employee_name'] ?? '')) ?>', '<?= $a['condition'] ?>')" 
                                                        style="padding: 5px 8px;">
                                                    <i class="fa-solid fa-rotate-left"></i> Return
                                                </button>
                                            <?php elseif ($a['status'] === 'available'): ?>
                                                <button type="button" class="btn btn-sm btn-primary" title="Assign to Employee" 
                                                        onclick="openAssignForAsset(<?= $a['id'] ?>, '<?= e(addslashes($a['asset_code'] . ' - ' . $a['name'])) ?>')" 
                                                        style="padding: 5px 8px;">
                                                    <i class="fa-solid fa-user-plus"></i> Assign
                                                </button>
                                            <?php endif; ?>

                                            <a href="<?= url('assets/edit?id=' . $a['id']) ?>" class="btn btn-sm btn-secondary" title="Edit Specifications" style="padding: 5px 8px;">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <?php if ($a['status'] !== 'allocated'): ?>
                                                <form action="<?= url('assets/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirmAction('Are you sure you want to remove this asset from inventory?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Asset" style="padding: 5px 8px;">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (isset($pagination)): ?>
                <?= render_pagination($pagination) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: REGISTER NEW ASSET -->
<!-- ========================================== -->
<div id="addAssetModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="margin: 0;"><i class="fa-solid fa-laptop text-primary"></i> Register Asset into Inventory</h3>
            <button type="button" class="btn-close" onclick="closeModal('addAssetModal')" style="border:0; background:transparent; font-size:18px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form action="<?= url('assets/create') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Asset Code</label>
                    <input type="text" name="asset_code" class="form-control" placeholder="Auto-generated (e.g. ADV-AST-008)" value="<?= Asset::nextAssetCode() ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <?php foreach ($categories as $k => $c): ?>
                            <option value="<?= $k ?>" <?= ($k === 'laptop') ? 'selected' : '' ?>><?= e($c['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Device / Equipment Name *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. MacBook Pro 16 M3 Max, Centrifuge Pro" required>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Brand / Manufacturer</label>
                    <input type="text" name="brand" class="form-control" placeholder="e.g. Apple, Dell, Eppendorf">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Model Number</label>
                    <input type="text" name="model" class="form-control" placeholder="e.g. A2991, Latitude 5440">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" placeholder="e.g. C02GL38XMD6T">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Initial Condition</label>
                    <select name="condition" class="form-control">
                        <option value="brand_new" selected>Brand New</option>
                        <option value="good">Good Condition</option>
                        <option value="fair">Fair (Usable)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Purchase Cost (₹)</label>
                    <input type="number" step="0.01" name="purchase_cost" class="form-control" placeholder="e.g. 125000">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Hardware Notes / Configuration</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. 32GB RAM, 1TB SSD, includes charger & sleeve..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAssetModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Register Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: ASSIGN ASSET TO EMPLOYEE -->
<!-- ========================================== -->
<div id="assignAssetModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="margin: 0;"><i class="fa-solid fa-hand-holding-hand text-primary"></i> Assign Asset to Employee</h3>
            <button type="button" class="btn-close" onclick="closeModal('assignAssetModal')" style="border:0; background:transparent; font-size:18px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form action="<?= url('assets/allocate') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label">Select Available Asset *</label>
                <select name="asset_id" id="assign_asset_id" class="form-control" required>
                    <option value="">-- Choose from available pool --</option>
                    <?php foreach ($availableAssets as $av): ?>
                        <option value="<?= $av['id'] ?>">
                            [<?= e($av['asset_code']) ?>] <?= e($av['name']) ?> (S/N: <?= e($av['serial_number'] ?: 'N/A') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($availableAssets)): ?>
                    <small style="color: #dc2626; margin-top: 4px; display: block;">No available assets in pool. Register an asset or process a return first.</small>
                <?php endif; ?>
            </div>

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
                    <input type="date" name="expected_return_date" class="form-control" placeholder="Optional">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Handover Notes / Accessories Provided</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Issued with power adapter, HDMI cable, laptop bag..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignAssetModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" <?= empty($availableAssets) ? 'disabled' : '' ?>><i class="fa-solid fa-check"></i> Complete Handover</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 3: RETURN ASSET FROM EMPLOYEE -->
<!-- ========================================== -->
<div id="returnAssetModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="margin: 0;"><i class="fa-solid fa-rotate-left text-warning"></i> Process Device Return</h3>
            <button type="button" class="btn-close" onclick="closeModal('returnAssetModal')" style="border:0; background:transparent; font-size:18px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form action="<?= url('assets/return') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="allocation_id" id="return_allocation_id">

            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;">Returning Device</div>
                <div id="return_asset_title" style="font-weight: 700; color: #0f172a; font-size: 15px; margin: 2px 0;">--</div>
                <div style="font-size: 12px; color: #475569;">Currently held by: <strong id="return_custodian_name">--</strong></div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Return Date *</label>
                    <input type="date" name="returned_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Condition upon Return *</label>
                    <select name="return_condition" id="return_condition_select" class="form-control" required>
                        <option value="good">Good Condition</option>
                        <option value="brand_new">Like Brand New</option>
                        <option value="fair">Fair (Minor Scratches / Wear)</option>
                        <option value="damaged">Damaged / Needs Servicing</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Next Asset Status</label>
                <select name="next_status" class="form-control">
                    <option value="available" selected>Return to Available Storage Pool</option>
                    <option value="under_repair">Send to Repair / Servicing</option>
                    <option value="retired">Retire / Decommission Asset</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Inspection & Handover Remarks</label>
                <textarea name="return_notes" class="form-control" rows="2" placeholder="e.g. Inspected all ports, cleaned, charger returned intact..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('returnAssetModal')">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-check"></i> Confirm Return</button>
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

function openAssignForAsset(assetId, assetLabel) {
    const sel = document.getElementById('assign_asset_id');
    if (sel) {
        sel.value = assetId;
    }
    openModal('assignAssetModal');
}

function openReturnModal(allocationId, code, name, custodian, condition) {
    document.getElementById('return_allocation_id').value = allocationId;
    document.getElementById('return_asset_title').innerText = code + ' - ' + name;
    document.getElementById('return_custodian_name').innerText = custodian;
    document.getElementById('return_condition_select').value = condition || 'good';
    openModal('returnAssetModal');
}

// Close modals when clicking backdrop
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
