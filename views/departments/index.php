<?php
$pageTitle = 'Departments & Designations';
require_once BASE_PATH . '/views/layouts/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <h2 class="card-title" style="margin: 0;">
                <i class="fa-solid fa-building" style="color: var(--primary);"></i>
                Organization Structure
            </h2>

            <!-- Segmented Pill Switcher -->
            <div style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: var(--radius-sm); gap: 2px;">
                <button type="button" class="tab-switch-btn active" id="btnTabDepts" onclick="switchOrgTab('departments')"
                        style="padding: 6px 14px; border: none; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; background: #ffffff; color: var(--text-main); box-shadow: var(--shadow-xs);">
                    Departments (<?= count($departments) ?>)
                </button>
                <button type="button" class="tab-switch-btn" id="btnTabDesigs" onclick="switchOrgTab('designations')"
                        style="padding: 6px 14px; border: none; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; background: transparent; color: var(--text-muted);">
                    Designations (<?= count($designations) ?>)
                </button>
            </div>
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php if (Auth::isHR()): ?>
                <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addDeptModal')">
                    <i class="fa-solid fa-plus"></i> Add Department
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="openModal('addDesigModal')">
                    <i class="fa-solid fa-plus"></i> Add Designation
                </button>
            <?php endif; ?>
            <a href="<?= url('organization/chart') ?>" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-diagram-project"></i> Org Chart
            </a>
        </div>
    </div>

    <!-- DEPARTMENTS VIEW -->
    <div id="view-departments">
        <!-- Filter Toolbar -->
        <div style="padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="position: relative; max-width: 320px; width: 100%;">
                <input type="text" id="searchDeptInput" class="form-control" placeholder="Search department name or code..." style="padding-left: 36px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Showing <strong id="deptVisibleCount"><?= count($departments) ?></strong> of <?= count($departments) ?> departments
            </div>
        </div>

        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Code</th>
                            <th>Department Head</th>
                            <th>Headcount</th>
                            <th>Status</th>
                            <?php if (Auth::isHR()): ?>
                                <th style="text-align: right;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="deptTableBody">
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="<?= Auth::isHR() ? '6' : '5' ?>" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No departments configured yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $d): ?>
                                <tr class="dept-row" data-search="<?= strtolower(e($d['name'] . ' ' . $d['code'] . ' ' . ($d['head_name'] ?? ''))) ?>">
                                    <td>
                                        <strong><?= e($d['name']) ?></strong>
                                        <?php if (!empty($d['description'])): ?>
                                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?= e($d['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code style="font-size: 11.5px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #334155;"><?= e($d['code']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($d['head_name'])): ?>
                                            <span><?= e($d['head_name']) ?></span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info" style="font-size: 11px;">
                                            <?= $d['employee_count'] ?> Staff
                                        </span>
                                    </td>
                                    <td><?= status_badge($d['status'] ?? 'active') ?></td>
                                    <?php if (Auth::isHR()): ?>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="btn btn-sm btn-secondary" 
                                                    onclick='openEditDeptModal(<?= json_encode([
                                                        "id" => (int)$d["id"],
                                                        "name" => $d["name"],
                                                        "code" => $d["code"],
                                                        "head_id" => $d["head_id"] ? (int)$d["head_id"] : "",
                                                        "description" => $d["description"] ?? "",
                                                        "status" => $d["status"] ?? "active"
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' 
                                                    title="Edit Department & Head" style="padding: 4px 8px; margin-right: 4px;">
                                                <i class="fa-solid fa-pen-to-square" style="color: var(--primary); font-size: 12px;"></i>
                                            </button>
                                            <form action="<?= url('departments/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirmAction('Are you sure you want to delete department &quot;<?= e(addslashes($d['name'])) ?>&quot;?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="<?= $d['employee_count'] > 0 ? 'Cannot delete: ' . $d['employee_count'] . ' active staff' : 'Delete Department' ?>" style="padding: 4px 8px;">
                                                    <i class="fa-solid fa-trash" style="color: #e11d48; font-size: 12px;"></i>
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="deptPaginationContainer" class="pagination-container" style="display: none; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <div class="pagination-info" id="deptPaginationInfo"></div>
                <div class="pagination-nav" id="deptPaginationNav"></div>
            </div>
        </div>
    </div>

    <!-- DESIGNATIONS VIEW -->
    <div id="view-designations" style="display: none;">
        <!-- Filter Toolbar -->
        <div style="padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="position: relative; max-width: 320px; width: 100%;">
                <input type="text" id="searchDesigInput" class="form-control" placeholder="Search designation title or department..." style="padding-left: 36px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Showing <strong id="desigVisibleCount"><?= count($designations) ?></strong> of <?= count($designations) ?> designations
            </div>
        </div>

        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th>Grade / Band</th>
                            <th>Active Headcount</th>
                            <?php if (Auth::isHR()): ?>
                                <th style="text-align: right;">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="desigTableBody">
                        <?php if (empty($designations)): ?>
                            <tr>
                                <td colspan="<?= Auth::isHR() ? '5' : '4' ?>" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No designations configured yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($designations as $des): ?>
                                <tr class="desig-row" data-search="<?= strtolower(e($des['title'] . ' ' . $des['department_name'] . ' ' . $des['grade'])) ?>">
                                    <td>
                                        <strong><?= e($des['title']) ?></strong>
                                        <?php if (!empty($des['description'])): ?>
                                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?= e($des['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($des['department_name']) ?></td>
                                    <td>
                                        <span class="badge badge-secondary" style="font-size: 11px;"><?= e($des['grade'] ?: 'L1') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info" style="font-size: 11px;">
                                            <?= $des['employee_count'] ?> Employees
                                        </span>
                                    </td>
                                    <?php if (Auth::isHR()): ?>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                    onclick='openEditDesigModal(<?= json_encode([
                                                        "id" => (int)$des["id"],
                                                        "department_id" => (int)$des["department_id"],
                                                        "title" => $des["title"],
                                                        "grade" => $des["grade"] ?? "L1",
                                                        "description" => $des["description"] ?? ""
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                                    title="Edit Designation" style="padding: 4px 8px; margin-right: 4px;">
                                                <i class="fa-solid fa-pen-to-square" style="color: var(--primary); font-size: 12px;"></i>
                                            </button>
                                            <form action="<?= url('departments/delete-designation') ?>" method="POST" style="display: inline;" onsubmit="return confirmAction('Are you sure you want to delete designation &quot;<?= e(addslashes($des['title'])) ?>&quot;?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $des['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="<?= $des['employee_count'] > 0 ? 'Cannot delete: ' . $des['employee_count'] . ' active staff' : 'Delete Designation' ?>" style="padding: 4px 8px;">
                                                    <i class="fa-solid fa-trash" style="color: #e11d48; font-size: 12px;"></i>
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="desigPaginationContainer" class="pagination-container" style="display: none; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <div class="pagination-info" id="desigPaginationInfo"></div>
                <div class="pagination-nav" id="desigPaginationNav"></div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: ADD DEPARTMENT -->
<!-- ======================================================== -->
<?php if (Auth::isHR()): ?>
<div id="addDeptModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 0; box-shadow: var(--shadow-lg); animation: fadeIn 0.2s ease-out;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
            <h3 class="card-title" style="margin: 0; font-size: 16px;"><i class="fa-solid fa-building text-primary"></i> Add Department</h3>
            <button type="button" onclick="closeModal('addDeptModal')" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form action="<?= url('departments') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_department">
            <div class="card-body" style="padding: 20px;">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Department Name <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Regenerative Medicine" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Code <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. RGM" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Department Head / Manager</label>
                    <select name="head_id" class="form-control">
                        <option value="">-- Select Department Head --</option>
                        <?php foreach ($managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>">
                                <?= e($mgr['name']) ?> (<?= e($mgr['emp_code']) ?> - <?= e($mgr['designation']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief function and scope..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 8px; background: #f8fafc; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('addDeptModal')">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: ADD DESIGNATION -->
<!-- ======================================================== -->
<div id="addDesigModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 0; box-shadow: var(--shadow-lg); animation: fadeIn 0.2s ease-out;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
            <h3 class="card-title" style="margin: 0; font-size: 16px;"><i class="fa-solid fa-tag text-primary"></i> Add Designation</h3>
            <button type="button" onclick="closeModal('addDesigModal')" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form action="<?= url('departments') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_designation">
            <div class="card-body" style="padding: 20px;">
                <div class="form-group">
                    <label class="form-label">Job Title <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Lead Bioprocess Scientist" required>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Department <span style="color:#e11d48;">*</span></label>
                        <select name="department_id" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Band / Level</label>
                        <input type="text" name="grade" class="form-control" placeholder="e.g. L2" value="L2">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of duties..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 8px; background: #f8fafc; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('addDesigModal')">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Designation</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT DEPARTMENT -->
<!-- ======================================================== -->
<div id="editDeptModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 0; box-shadow: var(--shadow-lg); animation: fadeIn 0.2s ease-out;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
            <h3 class="card-title" style="margin: 0; font-size: 16px;"><i class="fa-solid fa-pen-to-square text-primary"></i> Edit Department</h3>
            <button type="button" onclick="closeModal('editDeptModal')" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form action="<?= url('departments') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_department">
            <input type="hidden" name="id" id="edit_dept_id" value="">
            <div class="card-body" style="padding: 20px;">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Department Name <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="name" id="edit_dept_name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Code <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="code" id="edit_dept_code" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Department Head / Manager</label>
                    <select name="head_id" id="edit_dept_head_id" class="form-control">
                        <option value="">-- No Head / Direct --</option>
                        <?php foreach ($managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>">
                                <?= e($mgr['name']) ?> (<?= e($mgr['emp_code']) ?> - <?= e($mgr['designation']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_dept_desc" class="form-control" rows="2" placeholder="Brief function and scope..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_dept_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 8px; background: #f8fafc; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('editDeptModal')">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT DESIGNATION -->
<!-- ======================================================== -->
<div id="editDesigModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 0; box-shadow: var(--shadow-lg); animation: fadeIn 0.2s ease-out;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
            <h3 class="card-title" style="margin: 0; font-size: 16px;"><i class="fa-solid fa-pen-to-square text-primary"></i> Edit Designation</h3>
            <button type="button" onclick="closeModal('editDesigModal')" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form action="<?= url('departments') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_designation">
            <input type="hidden" name="id" id="edit_desig_id" value="">
            <div class="card-body" style="padding: 20px;">
                <div class="form-group">
                    <label class="form-label">Job Title <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="title" id="edit_desig_title" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Department <span style="color:#e11d48;">*</span></label>
                        <select name="department_id" id="edit_desig_dept_id" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Band / Level</label>
                        <input type="text" name="grade" id="edit_desig_grade" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_desig_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 8px; background: #f8fafc; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('editDesigModal')">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openEditDeptModal(dept) {
    document.getElementById('edit_dept_id').value = dept.id || '';
    document.getElementById('edit_dept_name').value = dept.name || '';
    document.getElementById('edit_dept_code').value = dept.code || '';
    document.getElementById('edit_dept_head_id').value = dept.head_id || '';
    document.getElementById('edit_dept_desc').value = dept.description || '';
    document.getElementById('edit_dept_status').value = dept.status || 'active';
    openModal('editDeptModal');
}

function openEditDesigModal(desig) {
    document.getElementById('edit_desig_id').value = desig.id || '';
    document.getElementById('edit_desig_title').value = desig.title || '';
    document.getElementById('edit_desig_dept_id').value = desig.department_id || '';
    document.getElementById('edit_desig_grade').value = desig.grade || 'L1';
    document.getElementById('edit_desig_desc').value = desig.description || '';
    openModal('editDesigModal');
}
function switchOrgTab(tab) {
    const btnDepts = document.getElementById('btnTabDepts');
    const btnDesigs = document.getElementById('btnTabDesigs');
    const viewDepts = document.getElementById('view-departments');
    const viewDesigs = document.getElementById('view-designations');

    if (tab === 'departments') {
        btnDepts.style.background = '#ffffff';
        btnDepts.style.color = 'var(--text-main)';
        btnDepts.style.boxShadow = 'var(--shadow-xs)';
        btnDesigs.style.background = 'transparent';
        btnDesigs.style.color = 'var(--text-muted)';
        btnDesigs.style.boxShadow = 'none';

        viewDepts.style.display = 'block';
        viewDesigs.style.display = 'none';
    } else {
        btnDesigs.style.background = '#ffffff';
        btnDesigs.style.color = 'var(--text-main)';
        btnDesigs.style.boxShadow = 'var(--shadow-xs)';
        btnDepts.style.background = 'transparent';
        btnDepts.style.color = 'var(--text-muted)';
        btnDepts.style.boxShadow = 'none';

        viewDepts.style.display = 'none';
        viewDesigs.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setupTablePagination('deptTableBody', '.dept-row', 'searchDeptInput', 'deptPaginationContainer', 'deptPaginationInfo', 'deptPaginationNav', 'deptVisibleCount', 10);
    setupTablePagination('desigTableBody', '.desig-row', 'searchDesigInput', 'desigPaginationContainer', 'desigPaginationInfo', 'desigPaginationNav', 'desigVisibleCount', 10);
});

function setupTablePagination(tbodyId, rowSelector, searchInputId, paginationContainerId, infoId, navId, countId, pageSize = 10) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll(rowSelector));
    const searchInput = document.getElementById(searchInputId);
    const container = document.getElementById(paginationContainerId);
    const info = document.getElementById(infoId);
    const nav = document.getElementById(navId);
    const countEl = document.getElementById(countId);

    let filteredRows = [...allRows];
    let currentPage = 1;

    function render() {
        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = startIdx + pageSize;

        allRows.forEach(r => r.style.display = 'none');
        filteredRows.slice(startIdx, endIdx).forEach(r => r.style.display = '');

        if (countEl) countEl.innerText = total;

        if (total <= pageSize) {
            if (container) container.style.display = 'none';
            return;
        }

        if (container) container.style.display = 'flex';
        const displayStart = total === 0 ? 0 : startIdx + 1;
        const displayEnd = Math.min(total, endIdx);
        if (info) info.innerHTML = `Showing <strong>${displayStart}</strong> to <strong>${displayEnd}</strong> of <strong>${total}</strong>`;

        if (nav) {
            let html = '';
            html += `<button type="button" class="page-link" ${currentPage === 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''} onclick="changePage('${tbodyId}', ${currentPage - 1})"><i class="fa-solid fa-chevron-left"></i></button>`;

            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    html += `<button type="button" class="page-link ${i === currentPage ? 'active' : ''}" onclick="changePage('${tbodyId}', ${i})">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += `<span class="page-link" style="border:none;cursor:default;">...</span>`;
                }
            }

            html += `<button type="button" class="page-link" ${currentPage === totalPages ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''} onclick="changePage('${tbodyId}', ${currentPage + 1})"><i class="fa-solid fa-chevron-right"></i></button>`;
            nav.innerHTML = html;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            filteredRows = !query ? [...allRows] : allRows.filter(r => (r.getAttribute('data-search') || '').includes(query));
            currentPage = 1;
            render();
        });
    }

    window['pageHandlers'] = window['pageHandlers'] || {};
    window['pageHandlers'][tbodyId] = (p) => {
        currentPage = p;
        render();
    };

    render();
}

function changePage(tbodyId, p) {
    if (window['pageHandlers'] && window['pageHandlers'][tbodyId]) {
        window['pageHandlers'][tbodyId](p);
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
