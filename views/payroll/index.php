<?php
$pageTitle = 'Payroll & Compensation Manager';
require_once BASE_PATH . '/views/layouts/header.php';

// Prepare totals for the chart
$totalBasic = array_sum(array_column($payrolls, 'basic_salary'));
$totalHra = array_sum(array_column($payrolls, 'hra'));
$totalAllowances = array_sum(array_column($payrolls, 'allowances'));
$totalPf = array_sum(array_column($payrolls, 'pf_deduction'));
$totalTax = array_sum(array_column($payrolls, 'tax_deduction'));
$totalLop = array_sum(array_column($payrolls, 'lop_deduction'));
?>

<!-- Month Selector & Payroll Processing Card -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 20px; font-weight: 800; color: var(--text-main);">
                <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary); margin-right: 6px;"></i>
                Monthly Payroll: <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>
            </h2>
            <p style="color: var(--text-muted); font-size: 13px;">Automated salary calculation based on monthly attendance, approved leaves, and deductions.</p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Month Selector Form -->
            <form method="GET" action="<?= url('payroll') ?>" style="display: flex; gap: 8px;">
                <select name="month" class="form-control" style="width: 140px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select name="year" class="form-control" style="width: 100px;">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <button type="submit" class="btn btn-secondary">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
            </form>

            <!-- Generate Payroll Batch Button -->
            <form action="<?= url('payroll/process') ?>" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="month" value="<?= $month ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <button type="submit" class="btn btn-primary" onclick="return confirmAction('Generate or re-calculate payroll for all active employees for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>?')">
                    <i class="fa-solid fa-bolt"></i> Process Batch Payroll
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Payroll Executive KPI Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Total Gross CTC</span>
            <span class="stat-card-icon indigo"><i class="fa-solid fa-money-bill-wave"></i></span>
        </div>
        <div class="stat-card-value"><?= format_currency($totalGross) ?></div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-chart-line"></i> Total CTC</span>
            <span>All active workforce</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Total Deductions</span>
            <span class="stat-card-icon rose"><i class="fa-solid fa-percent"></i></span>
        </div>
        <div class="stat-card-value"><?= format_currency($totalDeductions) ?></div>
        <div class="stat-card-sub">
            <span class="trend-down" style="color: #e11d48;"><i class="fa-solid fa-shield-halved"></i> PF / Tax / LOP</span>
            <span>Statutory deductions</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Net Disbursed Take-Home</span>
            <span class="stat-card-icon green"><i class="fa-solid fa-building-columns"></i></span>
        </div>
        <div class="stat-card-value"><?= format_currency($totalNet) ?></div>
        <div class="stat-card-sub">
            <span class="trend-up" style="color: #059669;"><i class="fa-solid fa-circle-check"></i> Net Payout</span>
            <span>Bank disbursement sum</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-card-label">Processed Slips</span>
            <span class="stat-card-icon purple"><i class="fa-solid fa-receipt"></i></span>
        </div>
        <div class="stat-card-value"><?= count($payrolls) ?></div>
        <div class="stat-card-sub">
            <span class="trend-up"><i class="fa-solid fa-file-invoice"></i> Ready</span>
            <span>For <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></span>
        </div>
    </div>
</div>

<!-- Visual Payroll Charts Row: Distribution Donut & Comparison Bar Chart -->
<?php if (!empty($payrolls)): ?>
<div class="grid-2" style="margin-bottom: 24px;">
    <!-- Payroll Expense Breakdown Donut Chart -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: #93206c;"></i>
                    Monthly Compensation Breakdown
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Salary components & statutory withholdings
                </div>
            </div>
            <span class="badge badge-purple">Breakdown</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 20px;">
            <div class="donut-chart-wrapper" style="max-width: 240px; width: 100%; height: 210px;">
                <canvas id="payrollPieChart"></canvas>
                <div class="donut-center-metric">
                    <span class="metric-number" style="font-size: 17px;"><?= format_currency($totalGross) ?></span>
                    <span class="metric-label">Total CTC</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 18px; font-size: 11.5px; flex-wrap: wrap; justify-content: center;">
                <span class="badge" style="background:#fdf2f8; color:#93206c; border:1px solid #fbcfe8;"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:4px;"></i> Basic</span>
                <span class="badge" style="background:#f0f9ff; color:#0284c7; border:1px solid #bae6fd;"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:4px;"></i> HRA</span>
                <span class="badge" style="background:#f0fdfa; color:#0d9488; border:1px solid #99f6e4;"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:4px;"></i> Allowances</span>
                <span class="badge" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a;"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:4px;"></i> PF</span>
                <span class="badge" style="background:#fff1f2; color:#e11d48; border:1px solid #fecdd3;"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:4px;"></i> Tax/TDS</span>
            </div>
        </div>
    </div>

    <!-- Employee Net Salary Comparison Graph -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-bar" style="color: #059669;"></i>
                    Individual Net Salary Comparison
                </h3>
                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                    Take-home payout distribution per employee
                </div>
            </div>
            <span class="badge badge-success">Bar Graph</span>
        </div>
        <div class="card-body" style="padding: 20px 22px;">
            <div style="height: 220px; width: 100%;">
                <canvas id="employeeNetSalaryBarChart"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Payroll Breakdown Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa-solid fa-list-check"></i>
            Processed Employee Slips (<?= count($payrolls) ?>)
        </h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($payrolls)): ?>
            <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-file-invoice-dollar" style="font-size: 40px; color: #94a3b8; margin-bottom: 12px;"></i>
                <h3>No payroll generated yet for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></h3>
                <p style="margin-top: 6px;">Click <strong>"Process Batch Payroll"</strong> above to auto-generate salary slips based on attendance!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Payslip #</th>
                            <th>Employee</th>
                            <th>Days (Pres/LOP)</th>
                            <th>Gross CTC</th>
                            <th>Total Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrolls as $p): ?>
                            <tr>
                                <td>
                                    <strong style="font-family: monospace; color: var(--primary);"><?= e($p['payslip_number']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= e($p['employee_name']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= e($p['emp_code']) ?> • <?= e($p['designation_title'] ?? 'Staff') ?></small>
                                </td>
                                <td>
                                    <strong><?= $p['present_days'] ?></strong> / <?= $p['working_days'] ?> days
                                    <?php if ($p['lop_days'] > 0): ?>
                                        <br><small style="color: #dc2626;">LOP: <?= $p['lop_days'] ?>d (-<?= format_currency($p['lop_deduction']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_currency($p['gross_earnings']) ?></td>
                                <td>
                                    <?= format_currency($p['total_deductions']) ?><br>
                                    <small style="color:var(--text-muted);">PF: <?= format_currency($p['pf_deduction']) ?></small>
                                </td>
                                <td>
                                    <strong style="color: #15803d; font-size: 15px;"><?= format_currency($p['net_salary']) ?></strong>
                                </td>
                                <td><?= status_badge($p['payment_status']) ?></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= url('payroll/payslip?id=' . $p['id']) ?>" class="btn btn-sm btn-secondary" title="View Payslip">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>

                                        <?php if ($p['payment_status'] !== 'paid'): ?>
                                            <form action="<?= url('payroll/mark-paid') ?>" method="POST" style="display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="payroll_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirmAction('Disburse and mark as paid?')">
                                                    <i class="fa-solid fa-check"></i> Mark Paid
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

<?php if (!empty($payrolls)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Payroll Compensation Pie / Donut Chart
    const pieCtx = document.getElementById('payrollPieChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Basic Salary', 'HRA', 'Special Allowances', 'Provident Fund (PF)', 'Taxes & TDS', 'LOP Deductions'],
                datasets: [{
                    data: [<?= $totalBasic ?>, <?= $totalHra ?>, <?= $totalAllowances ?>, <?= $totalPf ?>, <?= $totalTax ?>, <?= $totalLop ?>],
                    backgroundColor: ['#93206c', '#0284c7', '#0d9488', '#f59e0b', '#e11d48', '#64748b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '76%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₹ ' + context.raw.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Individual Net Salary Bar Chart
    const barCtx = document.getElementById('employeeNetSalaryBarChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($payrolls, 'employee_name')) ?>,
                datasets: [{
                    label: 'Net Take-Home Salary (₹)',
                    data: <?= json_encode(array_map('floatval', array_column($payrolls, 'net_salary'))) ?>,
                    backgroundColor: 'rgba(5, 150, 105, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Net Pay: ₹ ' + context.raw.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(val) {
                                return '₹' + (val / 1000) + 'k';
                            }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
