<?php
$pageTitle = 'Edit Asset: ' . e($asset['name']);
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="<?= url('assets/view?id=' . $asset['id']) ?>" class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to Asset Details
    </a>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-pen-to-square text-primary"></i> Edit Asset Specifications (<?= e($asset['asset_code']) ?>)</h2>
    </div>
    <div class="card-body">
        <form action="<?= url('assets/edit') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $asset['id'] ?>">

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Asset Code</label>
                    <input type="text" class="form-control" value="<?= e($asset['asset_code']) ?>" readonly style="background: #f1f5f9; cursor: not-allowed;">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <?php foreach ($categories as $k => $c): ?>
                            <option value="<?= $k ?>" <?= ($asset['category'] === $k) ? 'selected' : '' ?>><?= e($c['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Device / Equipment Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e($asset['name']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Brand / Manufacturer</label>
                    <input type="text" name="brand" class="form-control" value="<?= e($asset['brand']) ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Model Number</label>
                    <input type="text" name="model" class="form-control" value="<?= e($asset['model']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" value="<?= e($asset['serial_number']) ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Physical Condition</label>
                    <select name="condition" class="form-control">
                        <option value="brand_new" <?= ($asset['condition'] === 'brand_new') ? 'selected' : '' ?>>Brand New</option>
                        <option value="good" <?= ($asset['condition'] === 'good') ? 'selected' : '' ?>>Good Condition</option>
                        <option value="fair" <?= ($asset['condition'] === 'fair') ? 'selected' : '' ?>>Fair (Usable)</option>
                        <option value="damaged" <?= ($asset['condition'] === 'damaged') ? 'selected' : '' ?>>Damaged / In Service</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Purchase Cost (₹)</label>
                    <input type="number" step="0.01" name="purchase_cost" class="form-control" value="<?= e($asset['purchase_cost']) ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= e($asset['purchase_date']) ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" class="form-control" value="<?= e($asset['warranty_expiry']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Hardware Notes / Configuration</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($asset['notes']) ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <a href="<?= url('assets/view?id=' . $asset['id']) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
