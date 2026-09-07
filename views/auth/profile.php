<?php
$pageTitle = 'My Profile & Security';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">👤 User Account & Profile Settings</h2>
    </div>
    <div class="card-body">
        <div class="nav-tabs">
            <button class="tab-btn active" data-tab="tab-overview">Profile Overview</button>
            <button class="tab-btn" data-tab="tab-contact">Edit Contact Details</button>
            <button class="tab-btn" data-tab="tab-security">Security & Password</button>
        </div>

        <!-- Tab 1: Overview -->
        <div id="tab-overview" class="tab-content active">
            <div style="display: flex; gap: 24px; align-items: flex-start; margin-bottom: 24px;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-full); background: linear-gradient(135deg, #4f46e5, #06b6d4); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 32px; font-weight: 800;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 22px;"><?= e($user['name']) ?></h2>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 8px;">
                        <?= e($user['email']) ?> • 
                        <span class="badge badge-info"><?= ucwords(str_replace('_', ' ', $user['role'])) ?></span>
                    </p>
                    <p style="font-size: 13px; color: var(--text-muted);">
                        <strong>Department:</strong> <?= e($user['department_name'] ?? 'N/A') ?> | 
                        <strong>Designation:</strong> <?= e($user['designation_title'] ?? 'N/A') ?> | 
                        <strong>Employee Code:</strong> <?= e($user['emp_code'] ?? 'N/A') ?>
                    </p>
                </div>
            </div>

            <?php if ($employee): ?>
            <div class="grid-2">
                <div style="background: #f8fafc; padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: #334155;">Personal Information</h3>
                    <p style="margin-bottom: 6px;"><strong>Phone:</strong> <?= e($employee['phone'] ?: 'Not set') ?></p>
                    <p style="margin-bottom: 6px;"><strong>Gender:</strong> <?= ucfirst($employee['gender'] ?? 'N/A') ?></p>
                    <p style="margin-bottom: 6px;"><strong>Date of Birth:</strong> <?= format_date($employee['dob']) ?></p>
                    <p style="margin-bottom: 6px;"><strong>Blood Group:</strong> <?= e($employee['blood_group'] ?: 'N/A') ?></p>
                    <p><strong>Address:</strong> <?= e($employee['address'] ?: 'Not set') ?></p>
                </div>

                <div style="background: #f8fafc; padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: #334155;">Emergency Contact</h3>
                    <p style="margin-bottom: 6px;"><strong>Contact Person:</strong> <?= e($employee['emergency_contact_name'] ?: 'N/A') ?></p>
                    <p style="margin-bottom: 6px;"><strong>Emergency Phone:</strong> <?= e($employee['emergency_contact_phone'] ?: 'N/A') ?></p>
                    <p style="margin-bottom: 6px;"><strong>Joining Date:</strong> <?= format_date($employee['date_of_joining']) ?></p>
                    <p><strong>Employment Status:</strong> <?= status_badge($employee['status']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Edit Contact Details -->
        <div id="tab-contact" class="tab-content">
            <form action="<?= url('profile') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Personal Mobile Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= e($employee['phone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="emergency_contact_name">Emergency Contact Person</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" value="<?= e($employee['emergency_contact_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control" value="<?= e($employee['emergency_contact_phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Residential Address</label>
                    <textarea name="address" id="address" class="form-control" rows="3"><?= e($employee['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

        <!-- Tab 3: Security & Password -->
        <div id="tab-security" class="tab-content">
            <form action="<?= url('profile') ?>" method="POST" style="max-width: 500px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password (minimum 6 characters)</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
