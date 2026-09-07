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
            <button class="tab-btn active" data-tab="tab-overview"><i class="fa-solid fa-user"></i> Profile Overview</button>
            <button class="tab-btn" data-tab="tab-avatar"><i class="fa-solid fa-camera"></i> Profile Photo</button>
            <button class="tab-btn" data-tab="tab-contact"><i class="fa-solid fa-address-book"></i> Edit Contact Details</button>
            <button class="tab-btn" data-tab="tab-security"><i class="fa-solid fa-shield-halved"></i> Security & Password</button>
        </div>

        <!-- Tab 1: Overview -->
        <div id="tab-overview" class="tab-content active">
            <div style="display: flex; gap: 24px; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap;">
                <div style="position: relative;">
                    <?php if (!empty($user['avatar']) && file_exists(BASE_PATH . '/' . $user['avatar'])): ?>
                        <img src="<?= url($user['avatar']) ?>?t=<?= time() ?>" alt="<?= e($user['name']) ?>" style="width: 84px; height: 84px; border-radius: var(--radius-full); object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
                    <?php else: ?>
                        <div style="width: 84px; height: 84px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 32px; font-weight: 800; box-shadow: 0 4px 14px rgba(147, 32, 108, 0.25);">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" onclick="document.querySelector('[data-tab=tab-avatar]').click()" style="position: absolute; bottom: 0; right: -2px; width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; cursor: pointer; box-shadow: var(--shadow-sm);" title="Change Photo">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 22px; margin-bottom: 4px;"><?= e($user['name']) ?></h2>
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

        <!-- Tab 2: Profile Photo Upload -->
        <div id="tab-avatar" class="tab-content">
            <div style="max-width: 600px;">
                <div style="display: flex; gap: 28px; align-items: center; margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div style="position: relative; flex-shrink: 0;">
                        <div id="avatar_preview_container" style="width: 110px; height: 110px; border-radius: 50%; overflow: hidden; border: 4px solid #ffffff; box-shadow: 0 4px 16px rgba(0,0,0,0.12); display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #93206c, #0284c7);">
                            <?php if (!empty($user['avatar']) && file_exists(BASE_PATH . '/' . $user['avatar'])): ?>
                                <img id="avatar_preview_img" src="<?= url($user['avatar']) ?>?t=<?= time() ?>" alt="<?= e($user['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <img id="avatar_preview_img" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <span id="avatar_preview_initial" style="color: #fff; font-size: 42px; font-weight: 800;"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Update Your Profile Photo</h3>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.5;">
                            Upload a recent professional photo. Square aspect ratio recommended.
                        </p>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; font-size: 11.5px; color: #64748b;">
                            <span class="badge badge-secondary"><i class="fa-solid fa-file-image"></i> JPG, PNG, WEBP</span>
                            <span class="badge badge-secondary"><i class="fa-solid fa-weight-scale"></i> Max 2 MB</span>
                        </div>
                    </div>
                </div>

                <form action="<?= url('profile') ?>" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_avatar">

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="form-label" for="avatar_input" style="font-weight: 600;">Choose Image File</label>
                        <input type="file" name="avatar" id="avatar_input" class="form-control" accept="image/png, image/jpeg, image/webp" required onchange="handleAvatarPreview(this)" style="padding: 10px;">
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload & Save Photo
                        </button>
                    </div>
                </form>

                <?php if (!empty($user['avatar']) && file_exists(BASE_PATH . '/' . $user['avatar'])): ?>
                    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 24px 0 18px;">
                    <form action="<?= url('profile') ?>" method="POST" onsubmit="return confirmAction('Are you sure you want to remove your profile photo?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove_avatar">
                        <button type="submit" class="btn btn-outline" style="color: #dc2626; border-color: #fecaca;">
                            <i class="fa-solid fa-trash-can"></i> Remove Current Photo
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <script>
        function handleAvatarPreview(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    alert('Selected file exceeds 2MB limit. Please choose a smaller photo.');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('avatar_preview_img');
                    const initial = document.getElementById('avatar_preview_initial');
                    if (img) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                    }
                    if (initial) {
                        initial.style.display = 'none';
                    }
                };
        </script>

        <!-- Tab 3: Edit Contact Details -->
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
