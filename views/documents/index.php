<?php
$pageTitle = 'Document Locker & KYC Compliance Matrix';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Document Locker Header & Quick Actions -->
<div class="card" style="margin-bottom: 22px;">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="badge badge-purple"><i class="fa-solid fa-shield-halved"></i> Digital Compliance</span>
                <span class="badge badge-info"><i class="fa-solid fa-lock"></i> Encrypted Storage</span>
            </div>
            <h2 style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">
                Document Locker & Compliance Matrix
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                Centralized repository for statutory KYC proofs, contracts, credentials, and verification records.
            </p>
        </div>

        <?php if (Auth::isHR()): ?>
            <button class="btn btn-primary" onclick="document.getElementById('uploadModal').style.display='flex'">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document for Employee
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Executive Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Total Documents</span>
            <span class="stat-card-icon indigo"><i class="fa-solid fa-folder-tree"></i></span>
        </div>
        <div class="stat-card-value"><?= $stats['total'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-circle-check"></i> Stored</span>
            <span>Across all departments</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">KYC Verified</span>
            <span class="stat-card-icon green"><i class="fa-solid fa-file-circle-check"></i></span>
        </div>
        <div class="stat-card-value"><?= $stats['verified'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-up" style="color: #059669;"><i class="fa-solid fa-check-double"></i> Approved</span>
            <span>Compliance cleared</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Pending HR Review</span>
            <span class="stat-card-icon amber"><i class="fa-solid fa-clock-rotate-left"></i></span>
        </div>
        <div class="stat-card-value"><?= $stats['pending'] ?></div>
        <div class="stat-card-sub">
            <?php if ($stats['pending'] > 0): ?>
                <span class="trend-down" style="color: #d97706;"><i class="fa-solid fa-hourglass-half"></i> Action Needed</span>
            <?php else: ?>
                <span class="trend-up"><i class="fa-solid fa-circle-check"></i> All Clear</span>
            <?php endif; ?>
            <span>Awaiting authorization</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Rejected / Incomplete</span>
            <span class="stat-card-icon rose"><i class="fa-solid fa-file-circle-xmark"></i></span>
        </div>
        <div class="stat-card-value"><?= $stats['rejected'] ?></div>
        <div class="stat-card-sub">
            <span class="trend-down" style="color: #e11d48;"><i class="fa-solid fa-triangle-exclamation"></i> Re-upload</span>
            <span>Correction required</span>
        </div>
    </div>
</div>

<!-- Master Documents Table Card -->
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <h3 class="card-title">
            <i class="fa-solid fa-folder-open" style="color: var(--primary);"></i>
            Compliance Records & Document Register (<?= $pagination['total_items'] ?? count($documents) ?>)
        </h3>
    </div>

    <!-- Filter Toolbar -->
    <div style="padding: 16px 22px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
        <form method="GET" action="<?= url('documents') ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px; position: relative;">
                <input type="text" name="search" class="form-control" placeholder="Search employee, title, filename..." value="<?= e($filters['search']) ?>" style="padding-left: 36px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8;"></i>
            </div>

            <div style="width: 190px;">
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] == $d['id']) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width: 190px;">
                <select name="document_type" class="form-control">
                    <option value="">All Document Types</option>
                    <?php foreach (Document::TYPES as $key => $meta): ?>
                        <option value="<?= $key ?>" <?= ($filters['document_type'] === $key) ? 'selected' : '' ?>>
                            <?= e($meta['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width: 160px;">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($filters['status'] === 'pending') ? 'selected' : '' ?>>Pending Review</option>
                    <option value="verified" <?= ($filters['status'] === 'verified') ? 'selected' : '' ?>>Verified</option>
                    <option value="rejected" <?= ($filters['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['document_type']) || !empty($filters['status'])): ?>
                <a href="<?= url('documents') ?>" class="btn btn-secondary">Reset</a>
            <?php endif; ?>

            <a href="<?= url('documents?' . http_build_query(array_merge($filters, ['export' => 'csv']))) ?>" class="btn btn-secondary" style="margin-left: auto;">
                <i class="fa-solid fa-file-csv text-primary"></i> Export Audit CSV
            </a>
        </form>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (empty($documents)): ?>
            <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-folder-blank" style="font-size: 38px; color: #94a3b8; margin-bottom: 12px;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main);">No document records found</h3>
                <p style="font-size: 13px;">Upload new documents or adjust your search filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Document Title & Type</th>
                            <th>File Info</th>
                            <th>Uploaded On</th>
                            <th>Verification Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <?php 
                            $typeMeta = Document::TYPES[$doc['document_type']] ?? ['label' => ucfirst($doc['document_type']), 'icon' => 'fa-file'];
                            $fileSizeKb = round($doc['file_size'] / 1024, 1);
                            $fileSizeMb = $fileSizeKb > 1024 ? round($fileSizeKb / 1024, 2) . ' MB' : $fileSizeKb . ' KB';
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 36px; height: 36px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; flex-shrink: 0;">
                                            <?= strtoupper(substr($doc['first_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <a href="<?= url('employees/view?id=' . $doc['employee_id']) ?>" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
                                                <?= e($doc['first_name'] . ' ' . $doc['last_name']) ?>
                                            </a>
                                            <div style="font-size: 11px; color: var(--text-muted);">
                                                <span style="font-family: monospace; font-weight: 600;"><?= e($doc['emp_code']) ?></span> • <?= e($doc['department_name'] ?? 'General') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 14px;">
                                            <i class="fa-solid <?= $typeMeta['icon'] ?>"></i>
                                        </div>
                                        <div>
                                            <strong style="color: var(--text-main); font-size: 13.5px;"><?= e($doc['title']) ?></strong>
                                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                                                <span class="badge badge-secondary" style="font-size: 10px;"><?= e($typeMeta['label']) ?></span>
                                                <?php if (!empty($typeMeta['mandatory'])): ?>
                                                    <span style="font-size: 10.5px; color: #0284c7; font-weight: 600;">Mandatory KYC</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-size: 12.5px; color: var(--text-main);">
                                        <span class="badge" style="background: <?= $doc['file_ext'] === 'pdf' ? '#fee2e2' : '#e0f2fe' ?>; color: <?= $doc['file_ext'] === 'pdf' ? '#b91c1c' : '#0369a1' ?>; font-weight: 700; text-transform: uppercase;">
                                            <?= e($doc['file_ext']) ?>
                                        </span>
                                        <span style="color: var(--text-muted); font-size: 11.5px; margin-left: 4px;"><?= $fileSizeMb ?></span>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($doc['file_name']) ?>">
                                        <?= e($doc['file_name']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-size: 12.5px;"><?= format_date($doc['created_at']) ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);">By: <?= e($doc['uploader_name'] ?? 'System') ?></div>
                                    <?php if (!empty($doc['expiry_date'])): 
                                        $expTime = strtotime($doc['expiry_date']);
                                        $isExpired = $expTime < time();
                                        $isExpiringSoon = !$isExpired && ($expTime < strtotime('+30 days'));
                                    ?>
                                        <div style="font-size: 10.5px; margin-top: 4px;">
                                            <?php if ($isExpired): ?>
                                                <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 10px; padding: 2px 5px;"><i class="fa-solid fa-triangle-exclamation"></i> Expired: <?= date('d M Y', $expTime) ?></span>
                                            <?php elseif ($isExpiringSoon): ?>
                                                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 10px; padding: 2px 5px;"><i class="fa-solid fa-clock"></i> Exp: <?= date('d M Y', $expTime) ?></span>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);"><i class="fa-regular fa-calendar"></i> Exp: <?= date('d M Y', $expTime) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($doc['status'] === 'verified'): ?>
                                        <span class="badge badge-success" style="padding: 5px 10px;">
                                            <i class="fa-solid fa-circle-check" style="margin-right: 4px;"></i> Verified
                                        </span>
                                        <div style="font-size: 10.5px; color: var(--text-muted); margin-top: 3px;">
                                            by <?= e($doc['verifier_name'] ?? 'HR') ?> on <?= format_date($doc['verified_at'], 'd M') ?>
                                        </div>
                                    <?php elseif ($doc['status'] === 'pending'): ?>
                                        <span class="badge badge-warning" style="padding: 5px 10px;">
                                            <i class="fa-solid fa-clock" style="margin-right: 4px;"></i> Awaiting Review
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="padding: 5px 10px;">
                                            <i class="fa-solid fa-circle-xmark" style="margin-right: 4px;"></i> Rejected
                                        </span>
                                        <?php if (!empty($doc['rejection_reason'])): ?>
                                            <div style="font-size: 11px; color: #e11d48; margin-top: 3px; max-width: 160px;">
                                                <?= e($doc['rejection_reason']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <!-- Preview in New Tab -->
                                        <a href="<?= url('documents/download?id=' . $doc['id'] . '&preview=1') ?>" target="_blank" class="btn btn-sm btn-secondary" title="Preview Document">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>

                                        <!-- Download -->
                                        <a href="<?= url('documents/download?id=' . $doc['id']) ?>" class="btn btn-sm btn-secondary" title="Download File">
                                            <i class="fa-solid fa-download"></i>
                                        </a>

                                        <?php if (Auth::isHR()): ?>
                                            <?php if ($doc['status'] !== 'verified'): ?>
                                                <!-- Verify Button -->
                                                <form action="<?= url('documents/verify') ?>" method="POST" style="display: inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                    <input type="hidden" name="redirect_to" value="<?= url('documents') ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve & Mark Verified" onclick="return confirmAction('Verify this document as authentic KYC compliance?')">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($doc['status'] !== 'rejected'): ?>
                                                <!-- Reject Button -->
                                                <button type="button" class="btn btn-sm btn-danger" title="Reject Document" onclick="openRejectModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['title'])) ?>')">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Delete Button -->
                                            <form action="<?= url('documents/delete') ?>" method="POST" style="display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                <input type="hidden" name="redirect_to" value="<?= url('documents') ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Delete Document" onclick="return confirmAction('Permanently remove this document file from the server?')">
                                                    <i class="fa-solid fa-trash" style="color: #e11d48;"></i>
                                                </button>
                                            </form>
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

<!-- Upload Modal (HR Admin) -->
<div id="uploadModal" class="modal" style="display: none; position: fixed; inset: 0; z-index: 100; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="max-width: 540px; width: 100%; margin: 0; animation: fadeIn 0.2s ease-out;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-cloud-arrow-up text-primary"></i> Upload Employee Document
            </h3>
            <button type="button" onclick="document.getElementById('uploadModal').style.display='none'" style="background:none; border:none; font-size:18px; color:var(--text-muted); cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('documents/upload') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect_to" value="<?= url('documents') ?>">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Select Employee <span style="color: #e11d48;">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- Choose Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>">
                                <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= e($emp['emp_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Document Category <span style="color: #e11d48;">*</span></label>
                        <select name="document_type" class="form-control" required id="modalDocType" onchange="autoFillDocTitle(this)">
                            <?php foreach (Document::TYPES as $key => $meta): ?>
                                <option value="<?= $key ?>"><?= e($meta['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Document Title <span style="color: #e11d48;">*</span></label>
                        <input type="text" name="title" id="modalDocTitle" class="form-control" placeholder="e.g. Official Appointment Letter" required value="Aadhaar Card (National ID)">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Select File (PDF, PNG, JPG - Max 5MB) <span style="color: #e11d48;">*</span></label>
                        <input type="file" name="document_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg" required style="padding: 8px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expiry Date <span style="font-size: 11px; color: var(--text-muted);">(Optional)</span></label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('uploadModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Document</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Reason Modal -->
<div id="rejectModal" class="modal" style="display: none; position: fixed; inset: 0; z-index: 100; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="max-width: 480px; width: 100%; margin: 0; animation: fadeIn 0.2s ease-out;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-circle-xmark" style="color: #e11d48;"></i> Reject Document Verification
            </h3>
            <button type="button" onclick="document.getElementById('rejectModal').style.display='none'" style="background:none; border:none; font-size:18px; color:var(--text-muted); cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('documents/reject') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="rejectDocId" value="">
            <input type="hidden" name="redirect_to" value="<?= url('documents') ?>">
            <div class="card-body">
                <p id="rejectDocTitle" style="font-weight: 700; color: var(--text-main); margin-bottom: 12px;"></p>
                <div class="form-group">
                    <label class="form-label">Reason for Rejection <span style="color: #e11d48;">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Blurred photocopy, please upload high-resolution colored scan..." required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Reject Document</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id, title) {
    document.getElementById('rejectDocId').value = id;
    document.getElementById('rejectDocTitle').textContent = 'Document: ' + title;
    document.getElementById('rejectModal').style.display = 'flex';
}

function autoFillDocTitle(selectElem) {
    const text = selectElem.options[selectElem.selectedIndex].text;
    document.getElementById('modalDocTitle').value = text;
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
