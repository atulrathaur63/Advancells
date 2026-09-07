<?php
$pageTitle = 'My Document Locker & KYC Records';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Employee KYC Status & Progress Banner -->
<div class="card" style="margin-bottom: 22px;">
    <div class="card-body" style="padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span class="badge badge-purple"><i class="fa-solid fa-id-badge"></i> Self-Service Locker</span>
                    <?php if ($compliance['is_compliant']): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> 100% KYC Compliant</span>
                    <?php else: ?>
                        <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> KYC In Progress (<?= $compliance['verified_mandatory'] ?>/<?= $compliance['total_mandatory'] ?> Verified)</span>
                    <?php endif; ?>
                </div>
                <h2 style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">
                    My Documents & Digital KYC Vault
                </h2>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                    Securely store and verify your identity documents, employment agreements, and academic certificates.
                </p>
            </div>

            <button class="btn btn-primary" onclick="openUploadModal('other', 'Employee Document')">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
            </button>
        </div>

        <!-- Compliance Progress Bar -->
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-main);">
                    Mandatory Onboarding & KYC Verification Score
                </span>
                <strong style="font-size: 14px; color: <?= $compliance['is_compliant'] ? '#059669' : 'var(--primary)' ?>;">
                    <?= $compliance['percentage'] ?>% Completed
                </strong>
            </div>
            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
                <div style="width: <?= $compliance['percentage'] ?>%; height: 100%; background: linear-gradient(90deg, #93206c, #059669); border-radius: 9999px; transition: width 0.3s ease;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">
                <span><?= $compliance['verified_mandatory'] ?> of <?= $compliance['total_mandatory'] ?> mandatory proofs approved by HR</span>
                <span><?= count($documents) ?> total documents in your locker</span>
            </div>
        </div>
    </div>
</div>

<!-- Mandatory KYC Checklist Grid -->
<div style="margin-bottom: 24px;">
    <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
        Mandatory Onboarding Checklist (KYC Requirements)
    </h3>

    <div class="grid-3">
        <?php 
        $mandatoryItems = [
            'aadhaar_card'       => ['title' => 'Aadhaar Card', 'desc' => 'Front & back scan of National UID Card', 'icon' => 'fa-id-card'],
            'pan_card'           => ['title' => 'PAN Card', 'desc' => 'Valid Permanent Account Number card', 'icon' => 'fa-credit-card'],
            'educational_degree' => ['title' => 'Degree / Certificate', 'desc' => 'Highest qualification certificate or mark sheet', 'icon' => 'fa-graduation-cap'],
            'bank_passbook'      => ['title' => 'Bank Proof', 'desc' => 'First page passbook or cancelled cheque with IFSC', 'icon' => 'fa-building-columns'],
            'signed_nda'         => ['title' => 'Signed NDA', 'desc' => 'Executed Advancells non-disclosure agreement', 'icon' => 'fa-file-shield'],
        ];

        // Map uploaded documents by type
        $docsByType = [];
        foreach ($documents as $d) {
            $docsByType[$d['document_type']][] = $d;
        }
        ?>

        <?php foreach ($mandatoryItems as $typeKey => $meta): ?>
            <?php 
            $existing = $docsByType[$typeKey] ?? [];
            $latestDoc = !empty($existing) ? $existing[0] : null;
            $isVerified = $latestDoc && $latestDoc['status'] === 'verified';
            $isPending = $latestDoc && $latestDoc['status'] === 'pending';
            $isRejected = $latestDoc && $latestDoc['status'] === 'rejected';
            ?>
            <div class="card" style="margin-bottom: 0; border: 1px solid <?= $isVerified ? '#a7f3d0' : ($isRejected ? '#fecdd3' : 'var(--border-card)') ?>; transition: transform 0.2s;">
                <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; padding: 18px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <div style="width: 38px; height: 38px; border-radius: var(--radius-md); background: <?= $isVerified ? '#ecfdf5' : '#f8fafc' ?>; color: <?= $isVerified ? '#059669' : 'var(--primary)' ?>; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 1px solid var(--border-color);">
                                <i class="fa-solid <?= $meta['icon'] ?>"></i>
                            </div>
                            <?php if ($isVerified): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Verified</span>
                            <?php elseif ($isPending): ?>
                                <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> In Review</span>
                            <?php elseif ($isRejected): ?>
                                <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Pending Upload</span>
                            <?php endif; ?>
                        </div>

                        <h4 style="font-size: 14.5px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                            <?= e($meta['title']) ?>
                        </h4>
                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4; margin-bottom: 14px;">
                            <?= e($meta['desc']) ?>
                        </p>

                        <?php if ($isRejected && !empty($latestDoc['rejection_reason'])): ?>
                            <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--radius-sm); padding: 8px 10px; font-size: 11.5px; color: #9f1239; margin-bottom: 12px;">
                                <i class="fa-solid fa-circle-exclamation"></i> <?= e($latestDoc['rejection_reason']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if ($isVerified): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11.5px; color: #059669; font-weight: 600;">
                                    <i class="fa-solid fa-shield-check"></i> Approved
                                </span>
                                <a href="<?= url('documents/download?id=' . $latestDoc['id'] . '&preview=1') ?>" target="_blank" class="btn btn-sm btn-secondary" style="padding: 4px 8px; font-size: 11px;">
                                    <i class="fa-solid fa-eye"></i> View
                                </a>
                            </div>
                        <?php elseif ($isPending): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11.5px; color: #d97706; font-weight: 500;">
                                    Uploaded <?= format_date($latestDoc['created_at'], 'd M') ?>
                                </span>
                                <a href="<?= url('documents/download?id=' . $latestDoc['id'] . '&preview=1') ?>" target="_blank" class="btn btn-sm btn-secondary" style="padding: 4px 8px; font-size: 11px;">
                                    <i class="fa-solid fa-eye"></i> View
                                </a>
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary" style="width: 100%;" onclick="openUploadModal('<?= $typeKey ?>', '<?= e(addslashes($meta['title'])) ?>')">
                                <i class="fa-solid fa-cloud-arrow-up"></i> <?= $isRejected ? 'Re-upload Proof' : 'Upload Proof' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- All Stored Documents Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa-solid fa-box-archive" style="color: var(--primary);"></i>
            My Complete Document Repository (<?= count($documents) ?> Files)
        </h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($documents)): ?>
            <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #94a3b8; margin-bottom: 8px;"></i>
                <p>No documents uploaded yet. Upload your mandatory KYC proofs to complete your employee profile.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Category</th>
                            <th>Format & Size</th>
                            <th>Uploaded On</th>
                            <th>Status</th>
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
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: var(--primary); border: 1px solid var(--border-color);">
                                            <i class="fa-solid <?= $typeMeta['icon'] ?>"></i>
                                        </div>
                                        <div>
                                            <strong style="color: var(--text-main); font-size: 13px;"><?= e($doc['title']) ?></strong>
                                            <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">
                                                <?= e($doc['file_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge badge-secondary"><?= e($typeMeta['label']) ?></span>
                                </td>

                                <td>
                                    <span class="badge" style="background: <?= $doc['file_ext'] === 'pdf' ? '#fee2e2' : '#e0f2fe' ?>; color: <?= $doc['file_ext'] === 'pdf' ? '#b91c1c' : '#0369a1' ?>; font-weight: 700; text-transform: uppercase;">
                                        <?= e($doc['file_ext']) ?>
                                    </span>
                                    <span style="font-size: 11.5px; color: var(--text-muted); margin-left: 4px;"><?= $fileSizeMb ?></span>
                                </td>

                                <td><?= format_date($doc['created_at']) ?></td>

                                <td>
                                    <?php if ($doc['status'] === 'verified'): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check"></i> Verified</span>
                                    <?php elseif ($doc['status'] === 'pending'): ?>
                                        <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> In Review</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                                        <?php if (!empty($doc['rejection_reason'])): ?>
                                            <div style="font-size: 11px; color: #e11d48; margin-top: 2px;">
                                                <?= e($doc['rejection_reason']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= url('documents/download?id=' . $doc['id'] . '&preview=1') ?>" target="_blank" class="btn btn-sm btn-secondary" title="View Document">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="<?= url('documents/download?id=' . $doc['id']) ?>" class="btn btn-sm btn-secondary" title="Download Document">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <?php if ($doc['status'] === 'pending'): ?>
                                            <form action="<?= url('documents/delete') ?>" method="POST" style="display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                <input type="hidden" name="redirect_to" value="<?= url('documents/my-documents') ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Delete Upload" onclick="return confirmAction('Delete this pending document?')">
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
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal (Employee Self-Service) -->
<div id="empUploadModal" class="modal" style="display: none; position: fixed; inset: 0; z-index: 100; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="max-width: 500px; width: 100%; margin: 0; animation: fadeIn 0.2s ease-out;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-cloud-arrow-up text-primary"></i> Upload KYC / Verification File
            </h3>
            <button type="button" onclick="document.getElementById('empUploadModal').style.display='none'" style="background:none; border:none; font-size:18px; color:var(--text-muted); cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('documents/upload') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect_to" value="<?= url('documents/my-documents') ?>">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Document Category <span style="color: #e11d48;">*</span></label>
                    <select name="document_type" class="form-control" required id="empModalDocType" onchange="autoFillDocTitle(this)">
                        <?php foreach (Document::TYPES as $key => $meta): ?>
                            <option value="<?= $key ?>"><?= e($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Document Title <span style="color: #e11d48;">*</span></label>
                    <input type="text" name="title" id="empModalDocTitle" class="form-control" required placeholder="e.g. Aadhaar Card Front & Back Scan">
                </div>

                <div class="form-group">
                    <label class="form-label">Expiry Date <span style="font-size: 11px; color: var(--text-muted);">(Optional for Passports, Certs)</span></label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Choose File (PDF, PNG, JPG - Max 5MB) <span style="color: #e11d48;">*</span></label>
                    <input type="file" name="document_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg" required style="padding: 8px;">
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">
                        Ensure uploaded scans are clearly legible with all 4 corners visible.
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('empUploadModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal(type, title) {
    const select = document.getElementById('empModalDocType');
    if (type) {
        select.value = type;
    }
    document.getElementById('empModalDocTitle').value = title || select.options[select.selectedIndex].text;
    document.getElementById('empUploadModal').style.display = 'flex';
}

function autoFillDocTitle(selectElem) {
    const text = selectElem.options[selectElem.selectedIndex].text;
    document.getElementById('empModalDocTitle').value = text;
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
