<?php
$pageTitle = $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['emp_code'] . ')';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<!-- Employee Hero Header Card -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 24px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($employee['avatar']) && file_exists(BASE_PATH . '/' . $employee['avatar'])): ?>
                <img src="<?= url($employee['avatar']) ?>?t=<?= time() ?>" alt="<?= e($employee['first_name']) ?>" style="width: 84px; height: 84px; border-radius: var(--radius-full); object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 6px 18px rgba(0,0,0,0.15);">
            <?php else: ?>
                <div style="width: 84px; height: 84px; border-radius: var(--radius-full); background: linear-gradient(135deg, #4f46e5, #06b6d4); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);">
                    <?= strtoupper(substr($employee['first_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <h2 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: var(--text-main);">
                        <?= e($employee['first_name'] . ' ' . $employee['last_name']) ?>
                    </h2>
                    <?= status_badge($employee['status']) ?>
                </div>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <strong><?= e($employee['designation_title'] ?? 'Specialist') ?></strong> • 
                    <?= e($employee['department_name'] ?? 'General') ?> • 
                    <span style="font-family: monospace; font-weight: 600; color: var(--primary);"><?= e($employee['emp_code']) ?></span>
                </p>
                <div style="font-size: 13px; color: var(--text-light); display: flex; gap: 16px; flex-wrap: wrap;">
                    <span><i class="fa-solid fa-envelope text-primary"></i> <?= e($employee['email']) ?></span>
                    <span><i class="fa-solid fa-phone text-primary"></i> <?= e($employee['phone']) ?></span>
                    <span><i class="fa-solid fa-calendar-check text-primary"></i> Joined: <?= format_date($employee['date_of_joining']) ?></span>
                </div>
            </div>
        </div>

        <?php if (Auth::isHR()): ?>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= url('employees/edit?id=' . $employee['id']) ?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-user-pen"></i> Edit Details</a>
                <a href="<?= url('payroll/salary-setup?employee_id=' . $employee['id']) ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-file-invoice-dollar"></i> Salary Structure</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="card">
    <div class="card-body">
        <div class="nav-tabs">
            <button class="tab-btn active" data-tab="tab-details"><i class="fa-solid fa-id-card"></i> Profile Details</button>
            <button class="tab-btn" data-tab="tab-attendance"><i class="fa-solid fa-clock"></i> Attendance (This Month)</button>
            <button class="tab-btn" data-tab="tab-leaves"><i class="fa-solid fa-calendar-days"></i> Leave Balances</button>
            <button class="tab-btn" data-tab="tab-salary"><i class="fa-solid fa-wallet"></i> Salary & Payslips</button>
            <button class="tab-btn" data-tab="tab-goals"><i class="fa-solid fa-bullseye"></i> Goals (<?= count($goals) ?>)</button>
            <button class="tab-btn" data-tab="tab-documents"><i class="fa-solid fa-folder-open"></i> Documents Locker (<?= count($documents) ?>)</button>
        </div>

        <!-- Tab 1: Profile Details -->
        <div id="tab-details" class="tab-content active">
            <div class="grid-2">
                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px; color: #1e293b;"><i class="fa-solid fa-user text-primary" style="margin-right: 6px;"></i> Personal & Contact</h3>
                    <p style="margin-bottom: 8px;"><strong>Full Name:</strong> <?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Work Email:</strong> <?= e($employee['email']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Phone:</strong> <?= e($employee['phone']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Gender:</strong> <?= ucfirst($employee['gender']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Date of Birth:</strong> <?= format_date($employee['dob']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Blood Group:</strong> <?= e($employee['blood_group'] ?: 'N/A') ?></p>
                    <p style="margin-bottom: 8px;"><strong>Marital Status:</strong> <?= ucfirst($employee['marital_status']) ?></p>
                    <p><strong>Address:</strong> <?= e($employee['address'] ?: 'Not provided') ?></p>
                </div>

                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px; color: #1e293b;"><i class="fa-solid fa-briefcase text-primary" style="margin-right: 6px;"></i> Organization & Statutory</h3>
                    <p style="margin-bottom: 8px;"><strong>Department:</strong> <?= e($employee['department_name']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Designation:</strong> <?= e($employee['designation_title']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Reporting Manager:</strong> <?= e($employee['manager_name'] ?: 'None (Direct)') ?></p>
                    <p style="margin-bottom: 8px;"><strong>Employment Type:</strong> <?= status_badge($employee['employment_type']) ?></p>
                    <p style="margin-bottom: 8px;"><strong>Bank Name:</strong> <?= e($employee['bank_name'] ?: 'N/A') ?></p>
                    <p style="margin-bottom: 8px;"><strong>Account Number:</strong> <?= e($employee['account_number'] ?: 'N/A') ?></p>
                    <p style="margin-bottom: 8px;"><strong>IFSC Code:</strong> <?= e($employee['ifsc_code'] ?: 'N/A') ?></p>
                    <p style="margin-bottom: 8px;"><strong>PAN Number:</strong> <?= e($employee['pan_number'] ?: 'N/A') ?></p>
                    <p><strong>UAN / PF Number:</strong> <?= e($employee['uan_number'] ?: 'N/A') ?></p>
                </div>
            </div>
        </div>

        <!-- Tab 2: Attendance Summary -->
        <div id="tab-attendance" class="tab-content">
            <div class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $attendanceSummary['present'] ?></div>
                        <div class="stat-label">Days Present</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $attendanceSummary['late'] ?></div>
                        <div class="stat-label">Late Entries</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-umbrella-beach"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $attendanceSummary['leave'] ?></div>
                        <div class="stat-label">Approved Leaves</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-stopwatch"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $attendanceSummary['total_hours'] ?>h</div>
                        <div class="stat-label">Total Hours</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Mini Doughnut Chart -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; max-width: 500px; margin: 0 auto; text-align: center;">
                <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px;"><i class="fa-solid fa-chart-pie text-primary" style="margin-right: 6px;"></i> Monthly Attendance Proportion</h4>
                <div style="height: 220px; position: relative;">
                    <canvas id="empAttendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tab 3: Leave Balances -->
        <div id="tab-leaves" class="tab-content">
            <div class="grid-2">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Leave Category</th>
                                <th>Code</th>
                                <th>Allocated</th>
                                <th>Used</th>
                                <th>Pending</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveBalances as $lb): ?>
                                <tr>
                                    <td><strong><?= e($lb['leave_type_name']) ?></strong></td>
                                    <td><span class="badge badge-info"><?= e($lb['leave_type_code']) ?></span></td>
                                    <td><?= $lb['total_allocated'] ?></td>
                                    <td><?= $lb['used'] ?></td>
                                    <td><?= $lb['pending'] ?></td>
                                    <td><strong style="color: var(--primary); font-size: 15px;"><?= $lb['available'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Leave Quota Donut Chart -->
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: center;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px;"><i class="fa-solid fa-chart-pie text-primary" style="margin-right: 6px;"></i> Available Quota Distribution</h4>
                    <div style="height: 220px; position: relative;">
                        <canvas id="empLeaveChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Salary & Payslips -->
        <div id="tab-salary" class="tab-content">
            <?php if (!empty($employee['gross_salary'])): ?>
                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); margin-bottom: 24px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fa-solid fa-money-check-dollar text-primary" style="margin-right: 6px;"></i> Configured Monthly CTC Breakdown</h3>
                        <?php if (Auth::isHR()): ?>
                            <a href="<?= url('payroll/salary-setup?employee_id=' . $employee['id']) ?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen-to-square"></i> Modify Salary</a>
                        <?php endif; ?>
                    </div>
                    <div class="grid-2">
                        <div>
                            <p style="margin-bottom: 6px;"><strong>Basic Salary:</strong> <?= format_currency($employee['basic_salary']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>House Rent Allowance (HRA):</strong> <?= format_currency($employee['hra']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>Special Allowance:</strong> <?= format_currency($employee['special_allowance']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>Gross Monthly Salary:</strong> <span style="font-weight:700; color:#10b981;"><?= format_currency($employee['gross_salary']) ?></span></p>
                        </div>
                        <div>
                            <p style="margin-bottom: 6px;"><strong>PF Deduction:</strong> <?= format_currency($employee['pf_deduction']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>ESI Deduction:</strong> <?= format_currency($employee['esi_deduction']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>Professional Tax:</strong> <?= format_currency($employee['professional_tax']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>Net Take-Home Salary:</strong> <span style="font-weight:800; color:var(--primary); font-size:16px;"><?= format_currency($employee['net_salary']) ?></span></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); margin-bottom: 20px;">No salary structure defined yet.</p>
            <?php endif; ?>

            <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 12px;"><i class="fa-solid fa-file-invoice text-primary" style="margin-right: 6px;"></i> Generated Payslip History</h3>
            <?php if (empty($recentPayrolls)): ?>
                <p style="color: var(--text-muted);">No payslips generated for this employee yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayrolls as $p): ?>
                                <tr>
                                    <td><strong><?= date('F Y', mktime(0, 0, 0, $p['month'], 1, $p['year'])) ?></strong></td>
                                    <td><?= format_currency($p['gross_earnings']) ?></td>
                                    <td><?= format_currency($p['total_deductions']) ?></td>
                                    <td><strong style="color: #16a34a;"><?= format_currency($p['net_salary']) ?></strong></td>
                                    <td><?= status_badge($p['payment_status']) ?></td>
                                    <td>
                                        <a href="<?= url('payroll/payslip?id=' . $p['id']) ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-eye"></i> View Payslip</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 5: Goals -->
        <div id="tab-goals" class="tab-content">
            <?php if (empty($goals)): ?>
                <p style="color: var(--text-muted);">No active goals recorded.</p>
            <?php else: ?>
                <?php foreach ($goals as $g): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <strong><?= e($g['title']) ?></strong>
                            <?= status_badge($g['status']) ?>
                        </div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;"><?= e($g['description']) ?></p>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?= $g['progress'] ?>%; height: 100%; background: var(--primary);"></div>
                            </div>
                            <span style="font-weight: 700; font-size: 13px; color: var(--primary);"><?= $g['progress'] ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Tab 6: Documents Locker -->
        <div id="tab-documents" class="tab-content">
            <!-- KYC Compliance Summary Card -->
            <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px;">
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 20px; color: var(--primary);"><i class="fa-solid fa-shield-halved"></i></span>
                        <h4 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">KYC & Statutory Compliance Status</h4>
                    </div>
                    <p style="font-size: 13px; color: #64748b; margin: 0;">
                        Mandatory Verified: <strong><?= $compliance['verified'] ?> of <?= $compliance['mandatory_total'] ?></strong>
                        <?php if ($compliance['percentage'] >= 100): ?>
                            <span class="badge badge-success" style="margin-left: 8px;"><i class="fa-solid fa-check-circle"></i> 100% Fully Compliant</span>
                        <?php else: ?>
                            <span class="badge badge-warning" style="margin-left: 8px;"><i class="fa-solid fa-clock"></i> <?= $compliance['percentage'] ?>% Compliant (<?= $compliance['missing'] ?> Pending/Missing)</span>
                        <?php endif; ?>
                    </p>
                    <div style="height: 8px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin-top: 12px;">
                        <div style="width: <?= $compliance['percentage'] ?>%; height: 100%; background: linear-gradient(90deg, #93206c, #10b981); border-radius: 6px; transition: width 0.5s ease;"></div>
                    </div>
                </div>
                <?php if (Auth::isHR() || (int)$employee['id'] === (int)Auth::employeeId()): ?>
                <div>
                    <button class="btn btn-primary" onclick="document.getElementById('uploadEmpDocModal').style.display='flex'">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Documents Table -->
            <?php if (empty($documents)): ?>
                <div class="empty-state" style="padding: 40px 20px; text-align: center;">
                    <i class="fa-solid fa-folder-open" style="font-size: 40px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
                    <h4 style="color: #475569; font-weight: 600;">No Documents Uploaded Yet</h4>
                    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 16px;">This employee has not uploaded any identity, educational, or statutory documents.</p>
                    <?php if (Auth::isHR() || (int)$employee['id'] === (int)Auth::employeeId()): ?>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('uploadEmpDocModal').style.display='flex'">
                        <i class="fa-solid fa-plus"></i> Upload First Document
                    </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Uploaded File & Size</th>
                                <th>Status</th>
                                <th>Verification</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): 
                                $typeMeta = Document::TYPES[$doc['document_type']] ?? ['label' => ucfirst($doc['document_type']), 'mandatory' => false, 'icon' => 'fa-file'];
                            ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 36px; height: 36px; border-radius: 8px; background: #fdf2f8; color: #93206c; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                                <?php if (str_ends_with(strtolower($doc['file_name']), '.pdf')): ?>
                                                    <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i>
                                                <?php else: ?>
                                                    <i class="fa-solid fa-file-image" style="color: #0284c7;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 13.5px; color: #1e293b;"><?= e($doc['title']) ?></div>
                                                <div style="font-size: 11.5px; color: #64748b;"><?= e($typeMeta['label']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600;">
                                            <?= $typeMeta['mandatory'] ? 'Statutory KYC' : 'Supplementary' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($typeMeta['mandatory']): ?>
                                            <span class="badge" style="background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 10px;">Mandatory</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 10px;">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 12.5px; color: #334155; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($doc['file_name']) ?>">
                                            <?= e($doc['file_name']) ?>
                                        </div>
                                        <div style="font-size: 11px; color: #94a3b8;">
                                            <?= number_format($doc['file_size'] / 1024, 1) ?> KB &bull; <?= date('d M Y', strtotime($doc['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($doc['status'] === 'verified'): ?>
                                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Verified</span>
                                        <?php elseif ($doc['status'] === 'rejected'): ?>
                                            <span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> Rejected</span>
                                            <?php if (!empty($doc['rejection_reason'])): ?>
                                                <div style="font-size: 11px; color: #dc2626; margin-top: 3px; max-width: 160px; line-height: 1.2;">
                                                    "<?= e($doc['rejection_reason']) ?>"
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($doc['verifier_name']): ?>
                                            <div style="font-size: 12px; font-weight: 600; color: #1e293b;"><?= e($doc['verifier_name']) ?></div>
                                            <div style="font-size: 11px; color: #94a3b8;"><?= date('d M Y, h:i A', strtotime($doc['verified_at'])) ?></div>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #94a3b8;">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?= url('documents/download?id=' . $doc['id']) ?>" class="btn btn-outline btn-sm" title="Download Document" target="_blank">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <?php if (Auth::isHR()): ?>
                                            <?php if ($doc['status'] !== 'verified'): ?>
                                                <form action="<?= url('documents/verify') ?>" method="POST" style="display: inline-block;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Approve & Verify">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($doc['status'] !== 'rejected'): ?>
                                                <button type="button" class="btn btn-danger btn-sm" title="Reject with Reason" onclick="openEmpRejectModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['title'])) ?>')">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (Auth::isHR() || ($doc['status'] === 'pending' && $doc['employee_id'] === Auth::employeeId())): ?>
                                            <form action="<?= url('documents/delete') ?>" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444;" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Donut Chart
    const attCtx = document.getElementById('empAttendanceChart');
    if (attCtx) {
        new Chart(attCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Leave'],
                datasets: [{
                    data: [<?= (int)$attendanceSummary['present'] ?>, <?= (int)$attendanceSummary['late'] ?>, <?= (int)$attendanceSummary['leave'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '65%'
            }
        });
    }

    // Leave Balances Donut Chart
    const leaveCtx = document.getElementById('empLeaveChart');
    if (leaveCtx) {
        <?php
        $leaveLabels = array_column($leaveBalances, 'leave_type_name');
        $leaveData = array_column($leaveBalances, 'available');
        ?>
        new Chart(leaveCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($leaveLabels) ?>,
                datasets: [{
                    data: <?= json_encode($leaveData) ?>,
                    backgroundColor: ['#93206c', '#0284c7', '#10b981', '#f59e0b', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<!-- Upload Document Modal -->
<div id="uploadEmpDocModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fa-solid fa-cloud-arrow-up text-primary" style="margin-right: 8px;"></i> Upload Document for <?= e($employee['first_name']) ?></h3>
            <button type="button" onclick="document.getElementById('uploadEmpDocModal').style.display='none'" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= url('documents/upload') ?>" method="POST" enctype="multipart/form-data" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Document Classification <span class="text-danger">*</span></label>
                <select name="document_type" class="form-control" required style="font-size: 13px;">
                    <option value="">-- Select Document Type --</option>
                    <?php foreach (Document::TYPES as $key => $meta): ?>
                        <option value="<?= $key ?>"><?= $meta['label'] ?><?= $meta['mandatory'] ? ' (Mandatory)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Document Title / Description <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Aadhaar Card Front & Back, Degree Certificate" required style="font-size: 13px;">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Select File (PDF, PNG, JPG &bull; Max 5MB) <span class="text-danger">*</span></label>
                <input type="file" name="document_file" class="form-control" accept=".pdf,.png,.jpg,.jpeg" required style="font-size: 13px; padding: 8px;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Expiry Date <span style="font-size: 11px; color: var(--text-muted);">(Optional for Passports, Certs)</span></label>
                <input type="date" name="expiry_date" class="form-control" style="font-size: 13px;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('uploadEmpDocModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Document</button>
            </div>
        </form>
    </div>
</div>

<!-- Rejection Modal for Employee View -->
<div id="empRejectModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #fef2f2;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #991b1b;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Reject Document</h3>
            <button type="button" onclick="document.getElementById('empRejectModal').style.display='none'" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= url('documents/reject') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="emp_reject_doc_id" value="">
            <input type="hidden" name="redirect_to" value="<?= url('employees/view?id=' . $employee['id'] . '&tab=documents') ?>">
            <p style="font-size: 13px; color: #475569; margin-bottom: 16px;">
                Please state the reason for rejecting <strong id="emp_reject_doc_title" style="color: #1e293b;"></strong>. The employee will see this remark to re-upload.
            </p>
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Rejection Remarks <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Blurred photocopy, please upload clear scanned copy with valid expiry date." required style="font-size: 13px;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('empRejectModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEmpRejectModal(docId, docTitle) {
    document.getElementById('emp_reject_doc_id').value = docId;
    document.getElementById('emp_reject_doc_title').textContent = docTitle;
    document.getElementById('empRejectModal').style.display = 'flex';
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
