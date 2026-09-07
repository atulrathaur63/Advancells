<?php
$pageTitle = 'Add New Employee';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-user-plus text-primary"></i> Employee Onboarding & Registration</h2>
        <a href="<?= url('employees') ?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Directory</a>
    </div>
    <div class="card-body">
        <form action="<?= url('employees/create') ?>" method="POST" id="employeeForm">
            <?= csrf_field() ?>

            <div class="nav-tabs">
                <button type="button" class="tab-btn active" data-tab="tab-personal"><i class="fa-solid fa-user"></i> 1. Personal Information</button>
                <button type="button" class="tab-btn" data-tab="tab-job"><i class="fa-solid fa-briefcase"></i> 2. Job & Organization</button>
                <button type="button" class="tab-btn" data-tab="tab-bank"><i class="fa-solid fa-landmark"></i> 3. Bank, Statutory & Salary</button>
            </div>

            <!-- Tab 1: Personal Details -->
            <div id="tab-personal" class="tab-content active">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required placeholder="e.g. Rahul">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" required placeholder="e.g. Sharma">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Work Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="name@advancells.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <input type="text" name="phone" id="phone" class="form-control" required placeholder="+91 98765 43210">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gender">Gender</label>
                        <select name="gender" id="gender" class="form-control">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input type="date" name="dob" id="dob" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="blood_group">Blood Group</label>
                        <select name="blood_group" id="blood_group" class="form-control">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="marital_status">Marital Status</label>
                        <select name="marital_status" id="marital_status" class="form-control">
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                            <option value="divorced">Divorced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="emergency_contact_name">Emergency Contact Person</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" placeholder="Contact person name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control" placeholder="Phone number">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="address">Residential Address</label>
                        <input type="text" name="address" id="address" class="form-control" placeholder="Full residential street address">
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn btn-primary" onclick="document.querySelector('[data-tab=\'tab-job\']').click()">Next: Job Details →</button>
                </div>
            </div>

            <!-- Tab 2: Job Details -->
            <div id="tab-job" class="tab-content">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="emp_code">Employee Code *</label>
                        <input type="text" name="emp_code" id="emp_code" class="form-control" required placeholder="e.g. ADV-007">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="date_of_joining">Date of Joining *</label>
                        <input type="date" name="date_of_joining" id="date_of_joining" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="department_id">Department *</label>
                        <select name="department_id" id="department_id" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= e($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="designation_id">Designation *</label>
                        <select name="designation_id" id="designation_id" class="form-control" required>
                            <option value="">Select Designation</option>
                            <?php foreach ($designations as $desig): ?>
                                <option value="<?= $desig['id'] ?>"><?= e($desig['title']) ?> (<?= e($desig['department_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="manager_id">Reporting Manager</label>
                        <select name="manager_id" id="manager_id" class="form-control">
                            <option value="">None (Top-Level Executive)</option>
                            <?php foreach ($managers as $mgr): ?>
                                <option value="<?= $mgr['id'] ?>"><?= e($mgr['name']) ?> (<?= e($mgr['designation']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="employment_type">Employment Type</label>
                        <select name="employment_type" id="employment_type" class="form-control">
                            <option value="full_time">Full Time</option>
                            <option value="probation">Probation</option>
                            <option value="contract">Contract</option>
                            <option value="intern">Intern</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="role">System Access Role *</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="employee">Employee (Self-Service ESS)</option>
                            <option value="manager">Department Manager (Team Approvals)</option>
                            <option value="hr_admin">HR Admin</option>
                            <option value="super_admin">Super Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Initial Login Password *</label>
                        <input type="password" name="password" id="password" class="form-control" value="Advancells@123" required>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" onclick="document.querySelector('[data-tab=\'tab-personal\']').click()">← Back to Personal</button>
                    <button type="button" class="btn btn-primary" onclick="document.querySelector('[data-tab=\'tab-bank\']').click()">Next: Bank & Salary →</button>
                </div>
            </div>

            <!-- Tab 3: Banking & Salary Details -->
            <div id="tab-bank" class="tab-content">
                <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #1e293b;">🏦 Bank & Statutory Identifiers</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="bank_name">Bank Name</label>
                        <input type="text" name="bank_name" id="bank_name" class="form-control" placeholder="e.g. HDFC Bank">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="account_number">Bank Account Number</label>
                        <input type="text" name="account_number" id="account_number" class="form-control" placeholder="e.g. 501002345678">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ifsc_code">IFSC Code</label>
                        <input type="text" name="ifsc_code" id="ifsc_code" class="form-control" placeholder="e.g. HDFC0000123">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="pan_number">PAN Number</label>
                        <input type="text" name="pan_number" id="pan_number" class="form-control" placeholder="e.g. ABCDE1234F">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="uan_number">UAN / PF Number</label>
                        <input type="text" name="uan_number" id="uan_number" class="form-control" placeholder="e.g. 100902345678">
                    </div>
                </div>

                <h3 style="font-size: 15px; font-weight: 700; margin: 24px 0 16px; color: #1e293b;"><i class="fa-solid fa-coins text-primary"></i> Monthly Salary Structure (CTC Breakdown)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="basic_salary">Basic Salary (₹ / month) *</label>
                        <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" placeholder="e.g. 35000" oninput="calculateSalary()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hra">HRA (40% of Basic)</label>
                        <input type="number" step="0.01" name="hra" id="hra" class="form-control" placeholder="e.g. 14000">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="special_allowance">Special Allowance</label>
                        <input type="number" step="0.01" name="special_allowance" id="special_allowance" class="form-control" placeholder="e.g. 10000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="pf_deduction">PF Deduction (12% of Basic)</label>
                        <input type="number" step="0.01" name="pf_deduction" id="pf_deduction" class="form-control" placeholder="e.g. 4200">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="professional_tax">Professional Tax (PT)</label>
                        <input type="number" step="0.01" name="professional_tax" id="professional_tax" class="form-control" value="200.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tds">TDS / Income Tax (₹)</label>
                        <input type="number" step="0.01" name="tds" id="tds" class="form-control" value="0.00">
                    </div>
                </div>

                <div style="margin-top: 28px; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" onclick="document.querySelector('[data-tab=\'tab-job\']').click()"><i class="fa-solid fa-arrow-left"></i> Back to Job Details</button>
                    <button type="submit" class="btn btn-success btn-lg"><i class="fa-solid fa-check"></i> Complete & Register Employee</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calculateSalary() {
    const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
    if (basic > 0) {
        document.getElementById('hra').value = (basic * 0.40).toFixed(2);
        document.getElementById('pf_deduction').value = (basic * 0.12).toFixed(2);
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
