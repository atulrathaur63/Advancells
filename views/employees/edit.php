<?php
$pageTitle = 'Edit Employee: ' . ($employee['emp_code'] ?? '');
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-user-pen text-primary"></i> Edit Employee Details: <?= e($employee['first_name'] . ' ' . $employee['last_name']) ?> (<?= e($employee['emp_code']) ?>)</h2>
        <a href="<?= url('employees/view?id=' . $employee['id']) ?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-arrow-left"></i> View Profile</a>
    </div>
    <div class="card-body">
        <form action="<?= url('employees/edit?id=' . $employee['id']) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?= e($employee['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" value="<?= e($employee['last_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= e($employee['phone']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="department_id">Department</label>
                    <select name="department_id" id="department_id" class="form-control">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($employee['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                <?= e($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="designation_id">Designation</label>
                    <select name="designation_id" id="designation_id" class="form-control">
                        <?php foreach ($designations as $desig): ?>
                            <option value="<?= $desig['id'] ?>" <?= ($employee['designation_id'] == $desig['id']) ? 'selected' : '' ?>>
                                <?= e($desig['title']) ?> (<?= e($desig['department_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="manager_id">Reporting Manager</label>
                    <select name="manager_id" id="manager_id" class="form-control">
                        <option value="">None (Executive)</option>
                        <?php foreach ($managers as $mgr): ?>
                            <?php if ($mgr['id'] != $employee['id']): ?>
                                <option value="<?= $mgr['id'] ?>" <?= ($employee['manager_id'] == $mgr['id']) ? 'selected' : '' ?>>
                                    <?= e($mgr['name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="employment_type">Employment Type</label>
                    <select name="employment_type" id="employment_type" class="form-control">
                        <option value="full_time" <?= ($employee['employment_type'] === 'full_time') ? 'selected' : '' ?>>Full Time</option>
                        <option value="probation" <?= ($employee['employment_type'] === 'probation') ? 'selected' : '' ?>>Probation</option>
                        <option value="contract" <?= ($employee['employment_type'] === 'contract') ? 'selected' : '' ?>>Contract</option>
                        <option value="intern" <?= ($employee['employment_type'] === 'intern') ? 'selected' : '' ?>>Intern</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Employment Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" <?= ($employee['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="on_leave" <?= ($employee['status'] === 'on_leave') ? 'selected' : '' ?>>On Leave</option>
                        <option value="resigned" <?= ($employee['status'] === 'resigned') ? 'selected' : '' ?>>Resigned</option>
                        <option value="terminated" <?= ($employee['status'] === 'terminated') ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="role">System Access Role</label>
                    <select name="role" id="role" class="form-control">
                        <option value="employee" <?= ($employee['role'] === 'employee') ? 'selected' : '' ?>>Employee</option>
                        <option value="manager" <?= ($employee['role'] === 'manager') ? 'selected' : '' ?>>Manager</option>
                        <option value="hr_admin" <?= ($employee['role'] === 'hr_admin') ? 'selected' : '' ?>>HR Admin</option>
                        <option value="super_admin" <?= ($employee['role'] === 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
            </div>

            <h3 style="font-size: 15px; font-weight: 700; margin: 24px 0 16px; color: #1e293b;"><i class="fa-solid fa-landmark text-primary"></i> Banking & Salary Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" value="<?= e($employee['bank_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="account_number">Account Number</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" value="<?= e($employee['account_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ifsc_code">IFSC Code</label>
                    <input type="text" name="ifsc_code" id="ifsc_code" class="form-control" value="<?= e($employee['ifsc_code'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="basic_salary">Basic Salary (₹ / mo)</label>
                    <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" value="<?= e($salary['basic_salary'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="hra">HRA (₹ / mo)</label>
                    <input type="number" step="0.01" name="hra" id="hra" class="form-control" value="<?= e($salary['hra'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="special_allowance">Special Allowance (₹ / mo)</label>
                    <input type="number" step="0.01" name="special_allowance" id="special_allowance" class="form-control" value="<?= e($salary['special_allowance'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-lg">Update Employee Records</button>
            </div>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
