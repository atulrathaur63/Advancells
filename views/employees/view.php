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
            <button class="tab-btn" data-tab="tab-attendance"><i class="fa-solid fa-clock"></i> Attendance</button>
            <button class="tab-btn" data-tab="tab-leaves"><i class="fa-solid fa-calendar-days"></i> Leave Balances</button>
            <button class="tab-btn" data-tab="tab-salary"><i class="fa-solid fa-wallet"></i> Salary & Payslips</button>
            <button class="tab-btn" data-tab="tab-goals"><i class="fa-solid fa-bullseye"></i> Goals <span class="tab-badge"><?= count($goals) ?></span></button>
            <button class="tab-btn" data-tab="tab-documents"><i class="fa-solid fa-folder-open"></i> Documents <span class="tab-badge"><?= count($documents) ?></span></button>
            <button class="tab-btn" data-tab="tab-assets"><i class="fa-solid fa-laptop-code"></i> Assets <span class="tab-badge"><?= count($activeAssets) ?></span></button>
        </div>

        <!-- Tab 1: Profile Details -->
        <div id="tab-details" class="tab-content active">
            <div class="grid-2">
                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px; color: #1e293b;"><i class="fa-solid fa-user text-primary" style="margin-right: 6px;"></i> Personal & Contact</h3>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Full Name</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Work Email</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['email']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Phone</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['phone']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Gender</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= ucfirst($employee['gender']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Date of Birth</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= format_date($employee['dob']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Blood Group</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['blood_group'] ?: 'N/A') ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Marital Status</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= ucfirst($employee['marital_status']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                        <span style="color: var(--text-muted); font-size: 13px;">Address</span>
                        <span style="color: #1e293b; font-size: 13px; text-align: right; max-width: 60%; font-weight: 600;"><?= e($employee['address'] ?: 'Not provided') ?></span>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px; color: #1e293b;"><i class="fa-solid fa-briefcase text-primary" style="margin-right: 6px;"></i> Organization & Statutory</h3>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Department</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['department_name']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Designation</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['designation_title']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Reporting Manager</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['manager_name'] ?: 'None (Direct)') ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Employment Type</span>
                        <div><?= status_badge($employee['employment_type']) ?></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Bank Name</span>
                        <strong style="color: #1e293b; font-size: 13px;"><?= e($employee['bank_name'] ?: 'N/A') ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">Account Number</span>
                        <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #1e293b;"><?= e($employee['account_number'] ?: 'N/A') ?></code>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">IFSC Code</span>
                        <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #1e293b;"><?= e($employee['ifsc_code'] ?: 'N/A') ?></code>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eef2f6;">
                        <span style="color: var(--text-muted); font-size: 13px;">PAN Number</span>
                        <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #1e293b;"><?= e($employee['pan_number'] ?: 'N/A') ?></code>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                        <span style="color: var(--text-muted); font-size: 13px;">UAN / PF Number</span>
                        <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #1e293b;"><?= e($employee['uan_number'] ?: 'N/A') ?></code>
                    </div>
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
                <div class="table-responsive" style="border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 40%; padding: 12px 16px;">Document & Classification</th>
                                <th style="width: 25%; padding: 12px 16px;">File & Upload Info</th>
                                <th style="width: 20%; padding: 12px 16px;">Verification Status</th>
                                <th style="width: 15%; text-align: right; padding: 12px 16px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): 
                                $typeMeta = Document::TYPES[$doc['document_type']] ?? ['label' => ucfirst($doc['document_type']), 'mandatory' => false, 'icon' => 'fa-file'];
                                $isPdf = str_ends_with(strtolower($doc['file_name']), '.pdf');
                            ?>
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 40px; height: 40px; border-radius: 10px; background: <?= $isPdf ? '#fee2e2' : '#e0f2fe' ?>; color: <?= $isPdf ? '#ef4444' : '#0284c7' ?>; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                                <i class="fa-solid <?= $isPdf ? 'fa-file-pdf' : 'fa-file-image' ?>"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; font-size: 13.5px; color: #1e293b; line-height: 1.3; margin-bottom: 4px;">
                                                    <?= e($doc['title']) ?>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
                                                        <?= e($typeMeta['label']) ?>
                                                    </span>
                                                    <span style="color: #cbd5e1; font-size: 10px;">&bull;</span>
                                                    <?php if ($typeMeta['mandatory']): ?>
                                                        <span class="badge" style="background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 10.5px; padding: 2px 7px;">Mandatory KYC</span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 10.5px; padding: 2px 7px;">Optional</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 3px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($doc['file_name']) ?>">
                                            <i class="fa-solid fa-paperclip" style="color: #94a3b8; font-size: 11px; margin-right: 4px;"></i>
                                            <?= e($doc['file_name']) ?>
                                        </div>
                                        <div style="font-size: 11.5px; color: #94a3b8;">
                                            <?= number_format($doc['file_size'] / 1024, 1) ?> KB &bull; Uploaded <?= date('d M Y', strtotime($doc['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <?php if ($doc['status'] === 'verified'): ?>
                                            <span class="badge badge-success" style="font-size: 11.5px; padding: 3px 9px;">
                                                <i class="fa-solid fa-check"></i> Verified
                                            </span>
                                            <?php if (!empty($doc['verifier_name'])): ?>
                                                <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                                                    by <strong style="color: #334155;"><?= e($doc['verifier_name']) ?></strong>
                                                    <span style="color: #94a3b8; font-size: 10.5px; display: block; margin-top: 1px;"><?= date('d M Y, h:i A', strtotime($doc['verified_at'])) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($doc['status'] === 'rejected'): ?>
                                            <span class="badge badge-danger" style="font-size: 11.5px; padding: 3px 9px;">
                                                <i class="fa-solid fa-xmark"></i> Rejected
                                            </span>
                                            <?php if (!empty($doc['rejection_reason'])): ?>
                                                <div style="font-size: 11.5px; color: #dc2626; margin-top: 4px; max-width: 200px; line-height: 1.3;">
                                                    "<?= e($doc['rejection_reason']) ?>"
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-warning" style="font-size: 11.5px; padding: 3px 9px;">
                                                <i class="fa-solid fa-clock"></i> Pending Review
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap; padding: 14px 16px;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <a href="<?= url('documents/download?id=' . $doc['id']) ?>" class="btn btn-sm btn-secondary" title="Download Document" target="_blank" style="padding: 5px 9px;">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <?php if (Auth::isHR()): ?>
                                                <?php if ($doc['status'] !== 'verified'): ?>
                                                    <form action="<?= url('documents/verify') ?>" method="POST" style="display: inline-block; margin: 0;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve & Verify" style="padding: 5px 9px;">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($doc['status'] !== 'rejected'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline" title="Reject with Reason" onclick="openEmpRejectModal(<?= $doc['id'] ?>, '<?= e(addslashes($doc['title'])) ?>')" style="padding: 5px 9px; color: #dc2626; border-color: #fca5a5;">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (Auth::isHR() || ($doc['status'] === 'pending' && (int)$doc['employee_id'] === (int)Auth::employeeId())): ?>
                                                <form action="<?= url('documents/delete') ?>" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirmAction('Are you sure you want to delete this document?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary" style="padding: 5px 9px;" title="Delete Document">
                                                        <i class="fa-solid fa-trash" style="color: #ef4444; font-size: 11px;"></i>
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

        <!-- Tab 7: Assets & Equipment -->
        <div id="tab-assets" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 4px;">
                        <i class="fa-solid fa-laptop-code text-primary" style="margin-right: 6px;"></i> Assigned Company Hardware & Devices
                    </h3>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">
                        Laptops, lab instruments, access badges, and tools currently in this employee's custody.
                    </p>
                </div>
                <?php if (Auth::isHR()): ?>
                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('quickAssignModal').style.display='flex'">
                        <i class="fa-solid fa-plus"></i> Assign Device
                    </button>
                <?php endif; ?>
            </div>

            <!-- Active Assigned Hardware -->
            <?php if (empty($activeAssets)): ?>
                <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: var(--radius-md); border: 1px dashed var(--border-color); color: var(--text-muted); margin-bottom: 24px;">
                    <i class="fa-solid fa-box-open" style="font-size: 32px; margin-bottom: 10px; color: #94a3b8; display: block;"></i>
                    <p style="font-size: 13.5px; margin: 0;">No company assets currently assigned to this employee.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <?php foreach ($activeAssets as $ast): ?>
                        <?php 
                            $cat = $assetCategories[$ast['category']] ?? ['label' => ucfirst($ast['category']), 'icon' => 'fa-box', 'color' => '#64748b'];
                            $cond = $assetConditions[$ast['condition']] ?? ['label' => ucfirst($ast['condition']), 'class' => 'badge-secondary'];
                        ?>
                        <div style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); border-top: 3px solid <?= $cat['color'] ?>; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 6px; background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>; display: inline-flex; align-items: center; justify-content: center; font-size: 15px;">
                                        <i class="fa-solid <?= $cat['icon'] ?>"></i>
                                    </div>
                                    <span class="badge" style="background: <?= $cat['color'] ?>15; color: <?= $cat['color'] ?>; font-weight: 700; font-size: 10.5px;">
                                        <?= e($cat['label']) ?>
                                    </span>
                                </div>
                                <span class="badge <?= $cond['class'] ?>" style="font-size: 10px;">
                                    <?= e($cond['label']) ?>
                                </span>
                            </div>

                            <a href="<?= url('assets/view?id=' . $ast['id']) ?>" style="font-size: 15px; font-weight: 700; color: #0f172a; text-decoration: none; display: block; margin-bottom: 2px;">
                                <?= e($ast['name']) ?>
                            </a>
                            <div style="font-size: 11.5px; color: var(--text-muted); font-family: monospace; margin-bottom: 12px;">
                                <?= e($ast['asset_code']) ?> <?= !empty($ast['brand']) ? '• ' . e($ast['brand']) : '' ?>
                            </div>

                            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 6px; padding: 8px 10px; font-size: 11.5px; display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Serial No:</span>
                                    <code><?= e($ast['serial_number'] ?: 'N/A') ?></code>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Assigned On:</span>
                                    <strong><?= format_date($ast['allocated_date']) ?></strong>
                                </div>
                                <?php if (!empty($ast['expected_return_date'])): ?>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: var(--text-muted);">Due Date:</span>
                                        <strong style="color: #dc2626;"><?= format_date($ast['expected_return_date']) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (Auth::isHR()): ?>
                                <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                    <button type="button" class="btn btn-sm btn-warning" style="font-size: 11.5px; padding: 4px 10px;"
                                            onclick="openEmpAssetReturn(<?= $ast['allocation_id'] ?>, '<?= e(addslashes($ast['asset_code'] . ' - ' . $ast['name'])) ?>', '<?= $ast['condition'] ?>')">
                                        <i class="fa-solid fa-rotate-left"></i> Return Device
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Past Returned Assets History for this Employee -->
            <?php if (!empty($pastAssets)): ?>
                <h4 style="font-size: 13.5px; font-weight: 700; color: #334155; margin: 24px 0 10px;">
                    <i class="fa-solid fa-clock-rotate-left text-primary" style="margin-right: 6px;"></i> Previously Returned Equipment History
                </h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Device & Code</th>
                                <th>Serial No</th>
                                <th>Handover Date</th>
                                <th>Returned On</th>
                                <th>Return Condition</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastAssets as $past): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($past['name']) ?></strong>
                                        <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= e($past['asset_code']) ?></div>
                                    </td>
                                    <td><code><?= e($past['serial_number'] ?: '--') ?></code></td>
                                    <td><?= format_date($past['allocated_date']) ?></td>
                                    <td><strong style="color: #059669;"><?= format_date($past['returned_date']) ?></strong></td>
                                    <td><?= ucfirst($past['return_condition'] ?? 'good') ?></td>
                                    <td><?= e($past['received_by_name'] ?: 'HR Admin') ?></td>
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

function openEmpAssetReturn(allocationId, assetTitle, condition) {
    document.getElementById('emp_return_allocation_id').value = allocationId;
    document.getElementById('emp_return_asset_title').innerText = assetTitle;
    document.getElementById('emp_return_condition').value = condition || 'good';
    document.getElementById('quickReturnModal').style.display = 'flex';
}
</script>

<!-- Quick Assign Asset Modal for Employee Profile -->
<div id="quickAssignModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div class="modal-dialog" style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fa-solid fa-laptop text-primary" style="margin-right: 8px;"></i> Assign Device to <?= e($employee['first_name']) ?></h3>
            <button type="button" onclick="document.getElementById('quickAssignModal').style.display='none'" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= url('assets/allocate') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
            <input type="hidden" name="redirect_to" value="<?= url('employees/view?id=' . $employee['id'] . '&tab=assets') ?>">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Select Available Device *</label>
                <select name="asset_id" class="form-control" required style="font-size: 13px;">
                    <option value="">-- Choose from available storage pool --</option>
                    <?php foreach ($availableAssets as $av): ?>
                        <option value="<?= $av['id'] ?>">
                            [<?= e($av['asset_code']) ?>] <?= e($av['name']) ?> (S/N: <?= e($av['serial_number'] ?: 'N/A') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($availableAssets)): ?>
                    <small style="color: #dc2626; margin-top: 4px; display: block;">No available hardware in inventory. Register an asset first.</small>
                <?php endif; ?>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 13px;">Handover Date *</label>
                    <input type="date" name="allocated_date" class="form-control" value="<?= date('Y-m-d') ?>" required style="font-size: 13px;">
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 13px;">Expected Return</label>
                    <input type="date" name="expected_return_date" class="form-control" style="font-size: 13px;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Handover Remarks / Accessories</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Issued with charger, mouse, cleanroom pass..." style="font-size: 13px;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('quickAssignModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" <?= empty($availableAssets) ? 'disabled' : '' ?>><i class="fa-solid fa-check"></i> Assign Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Return Asset Modal for Employee Profile -->
<div id="quickReturnModal" class="modal-backdrop" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div class="modal-dialog" style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #fefce8;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #854d0e;"><i class="fa-solid fa-rotate-left text-warning" style="margin-right: 8px;"></i> Process Asset Return</h3>
            <button type="button" onclick="document.getElementById('quickReturnModal').style.display='none'" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= url('assets/return') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="allocation_id" id="emp_return_allocation_id">
            <input type="hidden" name="redirect_to" value="<?= url('employees/view?id=' . $employee['id'] . '&tab=assets') ?>">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;">Returning Device</div>
                <div id="emp_return_asset_title" style="font-weight: 700; color: #0f172a; font-size: 14px; margin-top: 2px;">--</div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 13px;">Return Date *</label>
                    <input type="date" name="returned_date" class="form-control" value="<?= date('Y-m-d') ?>" required style="font-size: 13px;">
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 13px;">Condition upon Return *</label>
                    <select name="return_condition" id="emp_return_condition" class="form-control" required style="font-size: 13px;">
                        <option value="good">Good Condition</option>
                        <option value="brand_new">Like Brand New</option>
                        <option value="fair">Fair (Wear & Tear)</option>
                        <option value="damaged">Damaged / Defective</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Next Asset Status</label>
                <select name="next_status" class="form-control" style="font-size: 13px;">
                    <option value="available" selected>Return to Available Pool</option>
                    <option value="under_repair">Send for Repair / Service</option>
                    <option value="retired">Retire / Decommission</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Inspection & Handover Remarks</label>
                <textarea name="return_notes" class="form-control" rows="2" placeholder="e.g. Device returned clean, checked functional..." style="font-size: 13px;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('quickReturnModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-check"></i> Complete Return</button>
            </div>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
