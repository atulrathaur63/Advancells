<?php
$pageTitle = 'Payslip #' . ($payslip['payslip_number'] ?? '');
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div style="max-width: 850px; margin: 0 auto;">
    <!-- Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;" class="no-print">
        <a href="<?= Auth::isHR() ? url('payroll?month=' . $payslip['month'] . '&year=' . $payslip['year']) : url('payroll/my-payslips') ?>" class="btn btn-sm btn-secondary">
            ← Back to Payroll
        </a>

        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                🖨️ Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Formal Printable Payslip Container -->
    <div class="card payslip-container" style="background: #ffffff; padding: 36px; border: 1px solid #cbd5e1; border-radius: var(--radius-md); box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        <!-- Company Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid #0f172a; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 18px;">
               <img src="<?= url('assets/img/logo.png') ?>" alt="Advancells Group" style="max-height: 60px; width: auto; object-fit: contain;">
                <div>
                   <h1 style="font-family: var(--font-heading); font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
                        Advancells Group
                    </h1>
                    <p style="font-size: 12px; color: #64748b;"><?= COMPANY_ADDRESS ?></p>
                    <p style="font-size: 12px; color: #64748b;">Email: <?= COMPANY_EMAIL ?> • Phone: <?= COMPANY_PHONE ?></p>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-family: var(--font-heading); font-size: 18px; font-weight: 700; color: var(--primary); text-transform: uppercase;">
                    Salary Statement
                </div>
                <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 2px;">
                    <?= date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])) ?>
                </div>
                <div style="font-size: 11.5px; font-family: monospace; color: #64748b; margin-top: 2px;">
                    Ref: <?= e($payslip['payslip_number']) ?>
                </div>
            </div>
        </div>

        <!-- Employee Info Summary Grid -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 16px; margin-bottom: 24px; font-size: 13px;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                <div>
                    <span style="color: #64748b;">Employee Name:</span><br>
                    <strong><?= e($payslip['first_name'] . ' ' . $payslip['last_name']) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">Employee Code:</span><br>
                    <strong style="font-family: monospace;"><?= e($payslip['emp_code']) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">Designation:</span><br>
                    <strong><?= e($payslip['designation_title']) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">Department:</span><br>
                    <strong><?= e($payslip['department_name']) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">Date of Joining:</span><br>
                    <strong><?= format_date($payslip['date_of_joining']) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">PAN Number:</span><br>
                    <strong style="font-family: monospace;"><?= e($payslip['pan_number'] ?: 'N/A') ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">Bank Name:</span><br>
                    <strong><?= e($payslip['bank_name'] ?: 'N/A') ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">Bank Account Number:</span><br>
                    <strong style="font-family: monospace;"><?= e($payslip['account_number'] ?: 'N/A') ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">UAN / PF Number:</span><br>
                    <strong style="font-family: monospace;"><?= e($payslip['uan_number'] ?: 'N/A') ?></strong>
                </div>
            </div>
        </div>

        <!-- Attendance Days Summary -->
        <div style="display: flex; justify-content: space-around; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: var(--radius-sm); padding: 10px; margin-bottom: 24px; font-size: 13px; text-align: center;">
            <div>
                <span style="color: #4338ca;">Total Days in Month:</span> <strong><?= $payslip['working_days'] ?></strong>
            </div>
            <div>
                <span style="color: #4338ca;">Present & Paid Days:</span> <strong><?= $payslip['present_days'] ?></strong>
            </div>
            <div>
                <span style="color: #4338ca;">Loss of Pay (LOP):</span> <strong style="color: #dc2626;"><?= $payslip['lop_days'] ?></strong>
            </div>
            <div>
                <span style="color: #4338ca;">Payment Status:</span> <?= status_badge($payslip['payment_status']) ?>
            </div>
        </div>

        <!-- Earnings vs Deductions Table -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #cbd5e1; border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 24px;">
            <!-- Earnings Column -->
            <div style="border-right: 1px solid #cbd5e1;">
                <div style="background: #f1f5f9; padding: 10px 16px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between;">
                    <span>EARNINGS</span>
                    <span>AMOUNT (₹)</span>
                </div>
                <div style="padding: 12px 16px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Basic Salary</span>
                        <span><?= number_format($payslip['basic_salary'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>House Rent Allowance (HRA)</span>
                        <span><?= number_format($payslip['hra'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Special & Other Allowances</span>
                        <span><?= number_format($payslip['allowances'], 2) ?></span>
                    </div>
                    <?php if ($payslip['overtime_pay'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Overtime Pay</span>
                            <span><?= number_format($payslip['overtime_pay'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($payslip['bonus'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Bonus / Incentive</span>
                            <span><?= number_format($payslip['bonus'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="background: #f8fafc; padding: 12px 16px; border-top: 1px solid #e2e8f0; font-weight: 700; display: flex; justify-content: space-between; font-size: 13.5px;">
                    <span>Total Gross Earnings</span>
                    <span style="color: #10b981;"><?= format_currency($payslip['gross_earnings']) ?></span>
                </div>
            </div>

            <!-- Deductions Column -->
            <div>
                <div style="background: #f1f5f9; padding: 10px 16px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between;">
                    <span>DEDUCTIONS</span>
                    <span>AMOUNT (₹)</span>
                </div>
                <div style="padding: 12px 16px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Provident Fund (PF)</span>
                        <span><?= number_format($payslip['pf_deduction'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>ESI Deduction</span>
                        <span><?= number_format($payslip['esi_deduction'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Professional Tax / TDS</span>
                        <span><?= number_format($payslip['tax_deduction'], 2) ?></span>
                    </div>
                    <?php if ($payslip['lop_deduction'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #dc2626;">
                            <span>Loss of Pay (LOP) Deduction</span>
                            <span><?= number_format($payslip['lop_deduction'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($payslip['other_deductions'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Other Deductions</span>
                            <span><?= number_format($payslip['other_deductions'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="background: #f8fafc; padding: 12px 16px; border-top: 1px solid #e2e8f0; font-weight: 700; display: flex; justify-content: space-between; font-size: 13.5px;">
                    <span>Total Deductions</span>
                    <span style="color: #ef4444;"><?= format_currency($payslip['total_deductions']) ?></span>
                </div>
            </div>
        </div>

        <!-- Net Take Home Box -->
        <div style="background: linear-gradient(135deg, #1e1b4b, #312e81); color: #fff; padding: 18px 24px; border-radius: var(--radius-sm); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; color: #a5f3fc;">
                    Net Disbursed Take-Home Salary
                </div>
                <div style="font-size: 12px; color: #cbd5e1; margin-top: 4px;">
                    In Words: <strong><?= e($netInWords) ?></strong>
                </div>
            </div>
            <div style="font-family: var(--font-heading); font-size: 28px; font-weight: 800; color: #ffffff;">
                <?= format_currency($payslip['net_salary']) ?>
            </div>
        </div>

        <!-- Signatures & Footnote -->
        <div style="margin-top: 48px; display: flex; justify-content: space-between; align-items: flex-end; padding-top: 20px;">
            <div style="text-align: center; width: 220px;">
                <div style="border-bottom: 1px solid #64748b; margin-bottom: 6px;"></div>
                <div style="font-size: 12px; font-weight: 600; color: #475569;">Employee Signature</div>
            </div>

            <div style="text-align: center; width: 220px;">
                <div style="font-family: 'Brush Script MT', cursive; font-size: 20px; color: #4f46e5; margin-bottom: 4px;">
                    Vipul Jain
                </div>
                <div style="border-bottom: 1px solid #64748b; margin-bottom: 6px;"></div>
                <div style="font-size: 12px; font-weight: 600; color: #475569;">Authorized HR Signatory</div>
                <div style="font-size: 10.5px; color: #94a3b8;">Advancells Biotech India</div>
            </div>
        </div>

        <div style="margin-top: 28px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
            This is a computer-generated salary statement from Advancells HRMS and is legally valid without physical signature.
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
