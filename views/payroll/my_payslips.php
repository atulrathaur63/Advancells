<?php
$pageTitle = 'My Payslips & Tax Documents';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-file-invoice-dollar text-primary"></i> My Salary Statements & Payslips (<?= count($payrolls) ?> Records)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($payrolls)): ?>
            <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <div style="font-size: 36px; margin-bottom: 10px; color: var(--primary);"><i class="fa-solid fa-receipt"></i></div>
                <h3>No payslips generated for your account yet</h3>
                <p>Your monthly salary slips will appear here once finalized by HR.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Reference #</th>
                            <th>Days Worked</th>
                            <th>Gross Earnings</th>
                            <th>Deductions</th>
                            <th>Net Disbursed</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrolls as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= date('F Y', mktime(0, 0, 0, $p['month'], 1, $p['year'])) ?></strong>
                                </td>
                                <td>
                                    <span style="font-family: monospace; color: var(--text-muted);"><?= e($p['payslip_number']) ?></span>
                                </td>
                                <td><?= $p['present_days'] ?> / <?= $p['working_days'] ?> days</td>
                                <td><?= format_currency($p['gross_earnings']) ?></td>
                                <td><?= format_currency($p['total_deductions']) ?></td>
                                <td>
                                    <strong style="color: #15803d; font-size: 15px;"><?= format_currency($p['net_salary']) ?></strong>
                                </td>
                                <td><?= status_badge($p['payment_status']) ?></td>
                                <td>
                                    <a href="<?= url('payroll/payslip?id=' . $p['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-eye"></i> View / Print
                                    </a>
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

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
