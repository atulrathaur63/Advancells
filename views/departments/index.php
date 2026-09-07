<?php
$pageTitle = 'Departments & Designations';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="grid-2">
    <!-- Departments Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-building text-primary"></i> Departments (<?= count($departments) ?>)</h2>
        </div>
        <div class="card-body">
            <!-- Add Department Form -->
            <form action="<?= url('departments') ?>" method="POST" style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_department">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="dept_name">Department Name *</label>
                        <input type="text" name="name" id="dept_name" class="form-control" placeholder="e.g. Stem Cell Bioengineering" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="dept_code">Code *</label>
                        <input type="text" name="code" id="dept_code" class="form-control" placeholder="e.g. SCB" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="dept_desc">Department Function</label>
                    <input type="text" name="description" id="dept_desc" class="form-control" placeholder="Primary responsibility and mandate">
                </div>

                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Department</button>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name & Code</th>
                            <th>Headcount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td>
                                    <strong><?= e($d['name']) ?></strong>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($d['code']) ?> • <?= e($d['description']) ?></div>
                                </td>
                                <td><span class="badge badge-info"><i class="fa-solid fa-users" style="font-size: 10px; margin-right: 3px;"></i><?= $d['employee_count'] ?> Staff</span></td>
                                <td><?= status_badge($d['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Designations Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-tags text-primary"></i> Designations & Grades (<?= count($designations) ?>)</h2>
        </div>
        <div class="card-body">
            <!-- Add Designation Form -->
            <form action="<?= url('departments') ?>" method="POST" style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_designation">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="desig_title">Job Title *</label>
                        <input type="text" name="title" id="desig_title" class="form-control" placeholder="e.g. Senior Bio-Analyst" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="desig_dept">Department *</label>
                        <select name="department_id" id="desig_dept" class="form-control" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="grade">Grade / Band</label>
                        <input type="text" name="grade" id="grade" class="form-control" placeholder="L1, L2, L3..." value="L2">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Designation</button>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title & Department</th>
                            <th>Grade</th>
                            <th>Active Staff</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($designations as $des): ?>
                            <tr>
                                <td>
                                    <strong><?= e($des['title']) ?></strong>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($des['department_name']) ?></div>
                                </td>
                                <td><span class="badge badge-secondary"><?= e($des['grade']) ?></span></td>
                                <td><?= $des['employee_count'] ?> Employees</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
