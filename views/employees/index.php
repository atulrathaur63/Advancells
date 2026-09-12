<?php
$pageTitle = 'Employee Directory';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <h2 class="card-title">
            <i class="fa-solid fa-users" style="color: var(--primary);"></i>
            Employee Directory (<?= $pagination['total_items'] ?? count($employees) ?> Staff Members)
        </h2>
        <div style="display: flex; gap: 10px;">
            <a href="<?= url('employees?export=csv' . (!empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '') . (!empty($filters['department_id']) ? '&department_id=' . $filters['department_id'] : '') . (!empty($filters['status']) ? '&status=' . $filters['status'] : '')) ?>" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <?php if (Auth::isHR()): ?>
                <a href="<?= url('employees/create') ?>" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-user-plus"></i> Add New Employee
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="padding: 16px 24px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
        <form method="GET" action="<?= url('employees') ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 220px; position: relative;">
                <input type="text" name="search" class="form-control" placeholder="Search by name, emp code, email, phone..." value="<?= e($filters['search']) ?>" style="padding-left: 36px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 12px; color: #94a3b8;"></i>
            </div>


            <div style="width: 200px;">
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($filters['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                            <?= e($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width: 160px;">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="on_leave" <?= ($filters['status'] === 'on_leave') ? 'selected' : '' ?>>On Leave</option>
                    <option value="resigned" <?= ($filters['status'] === 'resigned') ? 'selected' : '' ?>>Resigned</option>
                    <option value="terminated" <?= ($filters['status'] === 'terminated') ? 'selected' : '' ?>>Terminated</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['status'])): ?>
                <a href="<?= url('employees') ?>" class="btn btn-sm btn-secondary" style="padding: 9px 12px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (empty($employees)): ?>
            <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-user-xmark" style="font-size: 38px; color: #94a3b8; margin-bottom: 10px;"></i>
                <h3>No employee records found</h3>
                <p style="font-size: 13px;">Try adjusting your search criteria or filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Department & Role</th>
                            <th>Contact Information</th>
                            <th>Reporting Manager</th>
                            <th>Joining Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if (!empty($emp['avatar']) && file_exists(BASE_PATH . '/' . $emp['avatar'])): ?>
                                            <img src="<?= url($emp['avatar']) ?>?t=<?= time() ?>" alt="<?= e($emp['first_name']) ?>" style="width: 38px; height: 38px; border-radius: var(--radius-full); object-fit: cover; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <?php else: ?>
                                            <div style="width: 38px; height: 38px; border-radius: var(--radius-full); background: linear-gradient(135deg, #93206c, #0284c7); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                <?= strtoupper(substr($emp['first_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= url('employees/view?id=' . $emp['id']) ?>" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
                                                <?= e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                            </a>
                                            <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">
                                                <?= e($emp['emp_code']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= e($emp['designation_title'] ?? 'Staff') ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= e($emp['department_name'] ?? 'General') ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;"><i class="fa-regular fa-envelope" style="color: #94a3b8; margin-right: 4px;"></i> <?= e($emp['email']) ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><i class="fa-solid fa-phone" style="color: #94a3b8; margin-right: 4px;"></i> <?= e($emp['phone']) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($emp['manager_name'])): ?>
                                        <strong><?= e($emp['manager_name']) ?></strong>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" style="font-weight: 500; font-size: 11px;">Direct Head</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_date($emp['date_of_joining']) ?></td>
                                <td><?= status_badge($emp['employment_type']) ?></td>
                                <td><?= status_badge($emp['status']) ?></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= url('employees/view?id=' . $emp['id']) ?>" class="btn btn-sm btn-secondary" title="View Profile">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <?php if (Auth::isHR()): ?>
                                            <a href="<?= url('employees/edit?id=' . $emp['id']) ?>" class="btn btn-sm btn-secondary" title="Edit Employee">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
