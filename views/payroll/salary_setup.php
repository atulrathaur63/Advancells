<?php
$pageTitle = 'Salary Structure Setup: ' . ($employee['emp_code'] ?? '');
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-coins text-primary"></i> Configure Salary Structure: <?= e($employee['first_name'] . ' ' . $employee['last_name']) ?> (<?= e($employee['emp_code']) ?>)</h2>
        <a href="<?= url('employees/view?id=' . $employee['id']) ?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
    </div>
    <div class="card-body">
        <form action="<?= url('payroll/salary-setup?employee_id=' . $employee['id']) ?>" method="POST" id="salaryForm">
            <?= csrf_field() ?>

            <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #1e293b;">1. Earnings Components</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="basic_salary">Basic Salary (₹ / month) *</label>
                    <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" value="<?= e($salary['basic_salary'] ?? '0.00') ?>" required oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="hra">House Rent Allowance (HRA)</label>
                    <input type="number" step="0.01" name="hra" id="hra" class="form-control" value="<?= e($salary['hra'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="special_allowance">Special Allowance</label>
                    <input type="number" step="0.01" name="special_allowance" id="special_allowance" class="form-control" value="<?= e($salary['special_allowance'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="conveyance">Conveyance Allowance</label>
                    <input type="number" step="0.01" name="conveyance" id="conveyance" class="form-control" value="<?= e($salary['conveyance'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="medical_allowance">Medical Allowance</label>
                    <input type="number" step="0.01" name="medical_allowance" id="medical_allowance" class="form-control" value="<?= e($salary['medical_allowance'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="other_allowances">Other Special Allowances</label>
                    <input type="number" step="0.01" name="other_allowances" id="other_allowances" class="form-control" value="<?= e($salary['other_allowances'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
            </div>

            <h3 style="font-size: 15px; font-weight: 700; margin: 24px 0 16px; color: #1e293b;">2. Statutory & Tax Deductions</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="pf_deduction">Provident Fund (PF - 12% of Basic)</label>
                    <input type="number" step="0.01" name="pf_deduction" id="pf_deduction" class="form-control" value="<?= e($salary['pf_deduction'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="esi_deduction">ESI Deduction</label>
                    <input type="number" step="0.01" name="esi_deduction" id="esi_deduction" class="form-control" value="<?= e($salary['esi_deduction'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="professional_tax">Professional Tax (PT)</label>
                    <input type="number" step="0.01" name="professional_tax" id="professional_tax" class="form-control" value="<?= e($salary['professional_tax'] ?? '200.00') ?>" oninput="recalcSalary()">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tds">TDS / Monthly Income Tax (₹)</label>
                    <input type="number" step="0.01" name="tds" id="tds" class="form-control" value="<?= e($salary['tds'] ?? '0.00') ?>" oninput="recalcSalary()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="effective_date">Effective From Date</label>
                    <input type="date" name="effective_date" id="effective_date" class="form-control" value="<?= e($salary['effective_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>

            <!-- Live Summary Box -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin: 24px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 13px; color: var(--text-muted);">Calculated Gross Monthly CTC:</div>
                        <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: #10b981;" id="lblGross">
                            <?= format_currency($salary['gross_salary'] ?? 0) ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: var(--text-muted);">Total Monthly Deductions:</div>
                        <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: #ef4444;" id="lblDeductions">
                            <?= format_currency(($salary['pf_deduction'] ?? 0) + ($salary['esi_deduction'] ?? 0) + ($salary['professional_tax'] ?? 0) + ($salary['tds'] ?? 0)) ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: var(--text-muted);">Net Take-Home Salary:</div>
                        <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: #4f46e5;" id="lblNet">
                            <?= format_currency($salary['net_salary'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                💾 Save Salary Structure
            </button>
        </form>
    </div>
</div>

<script>
function recalcSalary() {
    const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
    const hra = parseFloat(document.getElementById('hra').value) || 0;
    const special = parseFloat(document.getElementById('special_allowance').value) || 0;
    const conv = parseFloat(document.getElementById('conveyance').value) || 0;
    const med = parseFloat(document.getElementById('medical_allowance').value) || 0;
    const other = parseFloat(document.getElementById('other_allowances').value) || 0;

    const pf = parseFloat(document.getElementById('pf_deduction').value) || 0;
    const esi = parseFloat(document.getElementById('esi_deduction').value) || 0;
    const pt = parseFloat(document.getElementById('professional_tax').value) || 0;
    const tds = parseFloat(document.getElementById('tds').value) || 0;

    const gross = basic + hra + special + conv + med + other;
    const deductions = pf + esi + pt + tds;
    const net = Math.max(0, gross - deductions);

    document.getElementById('lblGross').textContent = '₹ ' + gross.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('lblDeductions').textContent = '₹ ' + deductions.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('lblNet').textContent = '₹ ' + net.toLocaleString('en-IN', { minimumFractionDigits: 2 });
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
