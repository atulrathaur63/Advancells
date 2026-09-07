<?php
$pageTitle = 'Organization Hierarchy & Org Chart';
require_once BASE_PATH . '/views/layouts/header.php';

// Department color palette
$deptColors = [
    1 => ['bg' => '#fdf2f8', 'text' => '#93206c', 'border' => '#fbcfe8'], // Executive
    2 => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0'], // HR
    3 => ['bg' => '#f0f9ff', 'text' => '#0284c7', 'border' => '#bae6fd'], // CRT
    4 => ['bg' => '#faf5ff', 'text' => '#7c3aed', 'border' => '#ddd6fe'], // QC
    5 => ['bg' => '#fffbeb', 'text' => '#d97706', 'border' => '#fde68a'], // IT
    6 => ['bg' => '#fff1f2', 'text' => '#e11d48', 'border' => '#fecdd3'], // Sales
    7 => ['bg' => '#f0fdfa', 'text' => '#0d9488', 'border' => '#99f6e4'], // Finance
];

// Helper to get department colors safely
if (!function_exists('getDeptStyle')) {
    function getDeptStyle($deptId, $deptColors) {
        return $deptColors[$deptId] ?? ['bg' => '#f8fafc', 'text' => '#475569', 'border' => '#e2e8f0'];
    }
}
?>

<div class="page-container">
    <!-- Header Banner -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 24px 28px; margin-bottom: 24px; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span class="badge" style="background: #fdf2f8; color: #93206c; font-weight: 700;">
                    <i class="fa-solid fa-sitemap" style="margin-right: 5px;"></i> Enterprise Hierarchy
                </span>
                <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 700;">
                    <i class="fa-solid fa-bolt" style="margin-right: 4px;"></i> Live Structure
                </span>
            </div>
            <h1 style="font-size: 24px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px; margin: 0 0 6px 0;">
                Organization Chart & Reporting Tree
            </h1>
            <p style="font-size: 13.5px; color: #64748b; margin: 0;">
                Visual reporting hierarchy, executive leadership tree, and team reporting lines across all divisions.
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="btn-group" style="background: #f1f5f9; padding: 4px; border-radius: 10px; display: inline-flex;">
                <button type="button" class="btn btn-sm btn-view-toggle active" id="btnViewTree" onclick="switchOrgView('tree')" style="border-radius: 8px; font-size: 12.5px; font-weight: 600; padding: 6px 14px;">
                    <i class="fa-solid fa-diagram-project" style="margin-right: 6px;"></i> Visual Tree
                </button>
                <button type="button" class="btn btn-sm btn-view-toggle" id="btnViewMatrix" onclick="switchOrgView('matrix')" style="border-radius: 8px; font-size: 12.5px; font-weight: 600; padding: 6px 14px; background: transparent; color: #64748b; border: none;">
                    <i class="fa-solid fa-table-cells-large" style="margin-right: 6px;"></i> Department Matrix
                </button>
            </div>
        </div>
    </div>

    <!-- Executive KPI Summary -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['total_workforce'] ?></div>
                <div class="stat-label">Total Workforce</div>
                <div class="stat-trend" style="color: #10b981;">
                    <i class="fa-solid fa-circle-check"></i> 100% Active in Directory
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-user-tie"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['total_managers'] ?></div>
                <div class="stat-label">People Managers</div>
                <div class="stat-trend" style="color: #0284c7;">
                    <i class="fa-solid fa-sitemap"></i> Direct Team Leads
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber"><i class="fa-solid fa-building-user"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['departments_count'] ?></div>
                <div class="stat-label">Functional Divisions</div>
                <div class="stat-trend" style="color: #f59e0b;">
                    <i class="fa-solid fa-layer-group"></i> Active Departments
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-crown"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['top_executives'] ?></div>
                <div class="stat-label">Executive Leadership</div>
                <div class="stat-trend" style="color: #93206c;">
                    <i class="fa-solid fa-shield"></i> Apex Root Node (CEO)
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar & Canvas Control Bar -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 14px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; gap: 12px; align-items: center; flex: 1; min-width: 320px; max-width: 600px;">
            <!-- Autocomplete Search -->
            <div style="position: relative; flex: 1;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
                <input type="text" id="orgSearchInput" class="form-control" placeholder="Search employee by name, code, designation..." style="padding-left: 36px; font-size: 13px;" autocomplete="off">
                <div id="orgSearchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); z-index: 1000; max-height: 250px; overflow-y: auto; margin-top: 4px;"></div>
            </div>


            <!-- Department Filter -->
            <div style="width: 210px;">
                <select id="orgDeptFilter" class="form-control" style="font-size: 13px;" onchange="filterOrgDepartment(this.value)">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($departmentId === (int)$d['id']) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Canvas Zoom & Fit Action Buttons -->
        <div id="treeActionToolbar" style="display: flex; gap: 8px; align-items: center;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="zoomOrgTree(1.15)" title="Zoom In (+)">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="zoomOrgTree(0.85)" title="Zoom Out (-)">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="resetOrgTreeZoom()" title="Reset Zoom (100%)">
                <i class="fa-solid fa-arrows-rotate"></i> 100%
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="expandAllNodes()" title="Expand All Branches">
                <i class="fa-solid fa-expand"></i> Expand All
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="collapseAllNodes()" title="Collapse to Leads">
                <i class="fa-solid fa-compress"></i> Collapse
            </button>
        </div>
    </div>

    <!-- Tree View Container -->
    <div id="viewContainerTree" style="display: block;">
        <div id="orgCanvasOuter" style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; position: relative; height: 680px; box-shadow: inset 0 2px 6px rgba(0,0,0,0.02); cursor: grab;">
            
            <!-- Floating Navigation Hint -->
            <div style="position: absolute; bottom: 16px; left: 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); padding: 6px 14px; border-radius: 20px; font-size: 11.5px; color: #64748b; border: 1px solid #e2e8f0; pointer-events: none; z-index: 10; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-hand text-primary"></i> Click and drag to pan canvas &bull; Scroll to zoom
            </div>

            <!-- Movable & Scalable Stage -->
            <div id="orgCanvasStage" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; transform-origin: 50% 50px; transition: transform 0.15s ease-out; display: flex; justify-content: center; padding: 40px 60px 100px;">
                <div class="org-tree-root-wrapper" id="orgTreeWrapper">
                    <?php
                    // Recursive function to render Tree HTML
                    if (!function_exists('renderOrgTreeNode')) {
                        function renderOrgTreeNode($node, $deptColors) {
                            $style = getDeptStyle($node['department_id'], $deptColors);
                            $hasChildren = !empty($node['children']);
                            $avatarInitial = strtoupper(substr($node['first_name'], 0, 1));
                            ?>
                            <div class="org-node-branch <?= $hasChildren ? 'has-children' : 'is-leaf' ?>" data-node-id="<?= $node['id'] ?>">
                                <div class="org-node-card" id="emp-node-<?= $node['id'] ?>" onclick="openProfileDrawer(<?= htmlspecialchars(json_encode($node), ENT_QUOTES, 'UTF-8') ?>)" style="border-top: 4px solid <?= $style['text'] ?>;">
                                    <div class="org-node-avatar-wrap">
                                        <div class="org-node-avatar" style="background: <?= $style['bg'] ?>; color: <?= $style['text'] ?>; border: 2px solid <?= $style['border'] ?>; overflow: hidden;">
                                            <?php if (!empty($node['avatar']) && file_exists(BASE_PATH . '/' . $node['avatar'])): ?>
                                                <img src="<?= url($node['avatar']) ?>" alt="<?= e($node['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <?= $avatarInitial ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="org-status-indicator" title="Active Workforce"></span>
                                    </div>

                                    <div class="org-node-name" title="<?= e($node['name']) ?>">
                                        <?= e($node['name']) ?>
                                    </div>
                                    <div class="org-node-title" title="<?= e($node['designation_title']) ?>">
                                        <?= e($node['designation_title']) ?>
                                    </div>

                                    <div style="display: flex; gap: 4px; justify-content: center; align-items: center; margin-top: 6px; flex-wrap: wrap;">
                                        <span class="org-dept-pill" style="background: <?= $style['bg'] ?>; color: <?= $style['text'] ?>; border: 1px solid <?= $style['border'] ?>;">
                                            <?= e($node['department_code'] ?: $node['department_name']) ?>
                                        </span>
                                        <span class="org-code-pill">
                                            <?= e($node['emp_code']) ?>
                                        </span>
                                    </div>

                                    <?php if ($hasChildren): ?>
                                        <div class="org-reportees-badge">
                                            <i class="fa-solid fa-users" style="font-size: 10px; margin-right: 4px;"></i> <?= $node['direct_reports_count'] ?> Reportees
                                        </div>
                                        <button type="button" class="org-toggle-btn" onclick="toggleNodeBranch(event, <?= $node['id'] ?>)" title="Expand / Collapse">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($hasChildren): ?>
                                    <div class="org-children-container" id="children-<?= $node['id'] ?>">
                                        <?php foreach ($node['children'] as $child): ?>
                                            <?php renderOrgTreeNode($child, $deptColors); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    }

                    if (!empty($tree)):
                        foreach ($tree as $rootNode) {
                            renderOrgTreeNode($rootNode, $deptColors);
                        }
                    else:
                        ?>
                        <div style="padding: 60px; text-align: center; color: #64748b;">
                            <i class="fa-solid fa-sitemap" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                            <h3 style="font-weight: 700; color: #334155; margin-bottom: 8px;">No Reporting Lines Found</h3>
                            <p style="font-size: 13.5px; margin-bottom: 16px;">There are no active employees under the selected filter criteria.</p>
                            <a href="<?= url('org-chart') ?>" class="btn btn-secondary btn-sm">Reset Filter</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Matrix View Container -->
    <div id="viewContainerMatrix" style="display: none;">
        <div class="grid-3" style="gap: 20px;">
            <?php foreach ($departments as $dept): 
                $deptStyle = getDeptStyle($dept['id'], $deptColors);
                $deptMembers = array_filter($flat, fn($e) => (int)$e['department_id'] === (int)$dept['id']);
                $deptHead = null;
                foreach ($deptMembers as $m) {
                    if (str_contains(strtolower($m['designation_title']), 'chief') || 
                        str_contains(strtolower($m['designation_title']), 'head') || 
                        str_contains(strtolower($m['designation_title']), 'manager') ||
                        str_contains(strtolower($m['designation_title']), 'director')) {
                        $deptHead = $m;
                        break;
                    }
                }
                if (!$deptHead && !empty($deptMembers)) {
                    $deptHead = reset($deptMembers);
                }
            ?>
                <div class="card" style="border-top: 4px solid <?= $deptStyle['text'] ?>; box-shadow: var(--shadow-sm); height: 100%; display: flex; flex-direction: column;">
                    <div class="card-body" style="flex: 1; padding: 22px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <span class="badge" style="background: <?= $deptStyle['bg'] ?>; color: <?= $deptStyle['text'] ?>; font-weight: 700;">
                                    <?= e($dept['code'] ?: 'DIV') ?>
                                </span>
                                <h3 style="font-size: 16px; font-weight: 800; color: #1e293b; margin: 8px 0 4px 0;">
                                    <?= e($dept['name']) ?>
                                </h3>
                            </div>
                            <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700;">
                                <?= count($deptMembers) ?> Members
                            </span>
                        </div>

                        <p style="font-size: 12.5px; color: #64748b; margin-bottom: 16px; line-height: 1.4;">
                            <?= e($dept['description'] ?: 'Functional organizational division at Advancells.') ?>
                        </p>

                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                            <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                                Division Lead / HOD
                            </div>
                            <?php if ($deptHead): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $deptStyle['bg'] ?>; color: <?= $deptStyle['text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; overflow: hidden;">
                                        <?php if (!empty($deptHead['avatar']) && file_exists(BASE_PATH . '/' . $deptHead['avatar'])): ?>
                                            <img src="<?= url($deptHead['avatar']) ?>" alt="<?= e($deptHead['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($deptHead['first_name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 700; color: #1e293b;"><?= e($deptHead['name']) ?></div>
                                        <div style="font-size: 11.5px; color: #64748b;"><?= e($deptHead['designation_title']) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #94a3b8; font-style: italic;">No designated lead</span>
                            <?php endif; ?>
                        </div>

                        <!-- Team Roster Pill List -->
                        <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">
                            Team Members (<?= count($deptMembers) ?>)
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 180px; overflow-y: auto;">
                            <?php foreach ($deptMembers as $m): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; background: #ffffff; border: 1px solid #f1f5f9; border-radius: 6px; font-size: 12px;">
                                    <div style="font-weight: 600; color: #334155;"><?= e($m['name']) ?></div>
                                    <a href="<?= url('employees/view?id=' . $m['id']) ?>" class="btn btn-outline btn-sm" style="padding: 2px 8px; font-size: 10.5px;" title="View Profile">
                                        View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Slide-Over Quick Profile Drawer -->
<div id="profileDrawerOverlay" onclick="closeProfileDrawer()" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(3px); z-index: 9998; transition: opacity 0.3s ease;"></div>

<div id="profileDrawer" style="position: fixed; top: 0; right: -420px; width: 400px; max-width: 90vw; height: 100vh; background: #ffffff; box-shadow: -10px 0 30px rgba(0,0,0,0.15); z-index: 9999; transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; border-left: 1px solid #e2e8f0;">
    <!-- Drawer Header -->
    <div style="padding: 22px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-address-card text-primary"></i> Employee Snapshot
        </h3>
        <button type="button" onclick="closeProfileDrawer()" style="background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer;">&times;</button>
    </div>

    <!-- Drawer Body -->
    <div style="padding: 24px; overflow-y: auto; flex: 1;">
        <!-- Employee Header -->
        <div style="text-align: center; margin-bottom: 24px;">
            <div id="drawerAvatar" style="width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; color: #ffffff; background: linear-gradient(135deg, #93206c, #0284c7); box-shadow: 0 4px 14px rgba(147,32,108,0.25); overflow: hidden;">
                U
            </div>
            <h2 id="drawerName" style="font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;"></h2>
            <div id="drawerTitle" style="font-size: 13.5px; font-weight: 600; color: #64748b; margin-bottom: 10px;"></div>
            
            <div style="display: flex; gap: 6px; justify-content: center;">
                <span id="drawerDept" class="badge" style="background: #fdf2f8; color: #93206c; font-weight: 700;"></span>
                <span id="drawerCode" class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700;"></span>
            </div>
        </div>

        <!-- Contact & Profile Details Card -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <i class="fa-solid fa-envelope text-primary" style="width: 18px; font-size: 14px;"></i>
                <div style="font-size: 13px; color: #334155; word-break: break-all;" id="drawerEmail"></div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <i class="fa-solid fa-phone text-primary" style="width: 18px; font-size: 14px;"></i>
                <div style="font-size: 13px; color: #334155;" id="drawerPhone"></div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fa-solid fa-calendar text-primary" style="width: 18px; font-size: 14px;"></i>
                <div style="font-size: 13px; color: #334155;" id="drawerDoj"></div>
            </div>
        </div>

        <!-- Reporting Manager Section -->
        <div style="margin-bottom: 20px;">
            <div style="font-size: 11.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                Reports To (Direct Manager)
            </div>
            <div id="drawerManagerBox" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <div id="drawerManagerName" style="font-size: 13px; font-weight: 700; color: #1e293b;">None (Direct / CEO)</div>
                        <div id="drawerManagerRole" style="font-size: 11px; color: #64748b;">Reporting Authority</div>
                    </div>
                </div>
                <?php if (Auth::isHR()): ?>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openChangeManagerModal()" title="Change Reporting Line" style="padding: 3px 8px; font-size: 11px;">
                        <i class="fa-solid fa-pen-to-square"></i> Change
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Direct Team Members Section -->
        <div id="drawerTeamSection" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div style="font-size: 11.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">
                    Direct Reportees (<span id="drawerReporteesCount">0</span>)
                </div>
            </div>
            <div id="drawerTeamList" style="display: flex; flex-direction: column; gap: 6px; max-height: 160px; overflow-y: auto;">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>

    <!-- Drawer Footer Actions -->
    <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; gap: 10px;">
        <a id="drawerProfileBtn" href="#" class="btn btn-primary" style="flex: 1; text-align: center; justify-content: center;">
            <i class="fa-solid fa-id-card"></i> View Full 360 Profile
        </a>
    </div>
</div>

<!-- Change Manager Modal (HR Admin / Super Admin) -->
<?php if (Auth::isHR()): ?>
<div id="changeManagerModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 10000; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                <i class="fa-solid fa-user-pen text-primary" style="margin-right: 8px;"></i> Reassign Reporting Manager
            </h3>
            <button type="button" onclick="document.getElementById('changeManagerModal').style.display='none'" style="background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= url('organization/update-manager') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" id="reassign_employee_id" value="">
            
            <p style="font-size: 13px; color: #475569; margin-bottom: 16px;">
                Updating manager for <strong id="reassign_employee_name" style="color: #1e293b;"></strong>. The reporting lines in the Org Chart will reflect this change immediately.
            </p>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 600; font-size: 13px;">Select New Manager <span class="text-danger">*</span></label>
                <select name="manager_id" id="reassign_manager_select" class="form-control" style="font-size: 13px;">
                    <option value="">-- Direct Report to CEO / No Manager --</option>
                    <?php foreach ($flat as $mgr): ?>
                        <option value="<?= $mgr['id'] ?>" class="mgr-opt-<?= $mgr['id'] ?>">
                            <?= e($mgr['name']) ?> (<?= e($mgr['designation_title']) ?> - <?= e($mgr['department_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('changeManagerModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save New Reporting Line</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Organization Chart Custom Styles -->
<style>
/* Tree Hierarchy Styling */
.org-tree-root-wrapper {
    display: flex;
    justify-content: center;
    min-width: 100%;
}

.org-node-branch {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    padding: 0 16px;
}

/* Node Card */
.org-node-card {
    width: 210px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 12px 14px;
    text-align: center;
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.06);
    position: relative;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 5;
}

.org-node-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -4px rgba(147, 32, 108, 0.15);
    border-color: #93206c;
}

.org-node-card.highlighted {
    border-color: #93206c !important;
    box-shadow: 0 0 0 3px rgba(147, 32, 108, 0.25), 0 12px 24px -4px rgba(147, 32, 108, 0.2) !important;
    animation: nodePulse 1.5s infinite alternate;
}

@keyframes nodePulse {
    from { transform: translateY(-4px) scale(1.02); }
    to { transform: translateY(-4px) scale(1.05); }
}

.org-node-avatar-wrap {
    position: relative;
    display: inline-block;
    margin-bottom: 8px;
}

.org-node-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 15px;
    margin: 0 auto;
}

.org-status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #ffffff;
}

.org-node-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.org-node-title {
    font-size: 11.5px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 3px;
    line-height: 1.2;
}

.org-dept-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
}

.org-code-pill {
    font-size: 10px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 2px 6px;
    border-radius: 6px;
    font-family: monospace;
}

.org-reportees-badge {
    margin-top: 8px;
    font-size: 10.5px;
    font-weight: 700;
    color: #0284c7;
    background: #f0f9ff;
    border-radius: 20px;
    padding: 2px 8px;
    display: inline-flex;
    align-items: center;
}

/* Expand / Collapse Button */
.org-toggle-btn {
    position: absolute;
    bottom: -13px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #64748b;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: all 0.15s ease;
    z-index: 6;
}

.org-toggle-btn:hover {
    background: #93206c;
    color: #ffffff;
    border-color: #93206c;
}

/* Hierarchy Connecting Lines */
.org-children-container {
    display: flex;
    justify-content: center;
    padding-top: 36px;
    position: relative;
    transition: all 0.3s ease;
}

/* Down line from parent card */
.org-children-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 2px;
    height: 20px;
    background: #cbd5e1;
}

/* Horizontal line across children */
.org-children-container > .org-node-branch::before {
    content: '';
    position: absolute;
    top: 20px;
    width: 50%;
    height: 2px;
    background: #cbd5e1;
}

.org-children-container > .org-node-branch::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    width: 2px;
    height: 16px;
    background: #cbd5e1;
}

.org-children-container > .org-node-branch:first-child::before {
    left: 50%;
}

.org-children-container > .org-node-branch:last-child::before {
    right: 50%;
    width: 50%;
}

.org-children-container > .org-node-branch:only-child::before {
    display: none;
}

.org-children-container > .org-node-branch:not(:first-child):not(:last-child)::before {
    left: 0;
    width: 100%;
}

/* Collapsed State */
.org-children-container.collapsed {
    display: none !important;
}
</style>

<!-- Organization Chart JavaScript Logic -->
<script>
// Flat employee data from backend for instant search & drawer
const orgFlatEmployees = <?= json_encode($flat) ?>;
const BASE_URL = '<?= rtrim(url(""), "/") ?>';
let activeDrawerNode = null;

// Zoom & Pan state
let orgScale = 1.0;
let orgPanX = 0;
let orgPanY = 0;
let isPanning = false;
let startX = 0;
let startY = 0;

const outerCanvas = document.getElementById('orgCanvasOuter');
const stage = document.getElementById('orgCanvasStage');

function updateCanvasTransform() {
    if (stage) {
        stage.style.transform = `translate(${orgPanX}px, ${orgPanY}px) scale(${orgScale})`;
    }
}

// Canvas Mouse Dragging (Panning)
if (outerCanvas) {
    outerCanvas.addEventListener('mousedown', (e) => {
        // Prevent pan when clicking inside node cards or buttons
        if (e.target.closest('.org-node-card') || e.target.closest('button')) return;
        isPanning = true;
        startX = e.clientX - orgPanX;
        startY = e.clientY - orgPanY;
        outerCanvas.style.cursor = 'grabbing';
    });

    window.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        orgPanX = e.clientX - startX;
        orgPanY = e.clientY - startY;
        updateCanvasTransform();
    });

    window.addEventListener('mouseup', () => {
        if (isPanning) {
            isPanning = false;
            outerCanvas.style.cursor = 'grab';
        }
    });

    // Mouse Wheel Zoom
    outerCanvas.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        zoomOrgTree(delta);
    }, { passive: false });
}

function zoomOrgTree(factor) {
    orgScale = Math.min(Math.max(0.4, orgScale * factor), 1.8);
    updateCanvasTransform();
}

function resetOrgTreeZoom() {
    orgScale = 1.0;
    orgPanX = 0;
    orgPanY = 0;
    updateCanvasTransform();
}

// Expand / Collapse Single Node
function toggleNodeBranch(event, nodeId) {
    event.stopPropagation();
    const container = document.getElementById('children-' + nodeId);
    const btn = event.currentTarget;
    if (!container) return;

    if (container.classList.contains('collapsed')) {
        container.classList.remove('collapsed');
        btn.innerHTML = '<i class="fa-solid fa-minus"></i>';
    } else {
        container.classList.add('collapsed');
        btn.innerHTML = '<i class="fa-solid fa-plus"></i>';
    }
}

// Expand All
function expandAllNodes() {
    document.querySelectorAll('.org-children-container').forEach(c => c.classList.remove('collapsed'));
    document.querySelectorAll('.org-toggle-btn').forEach(b => b.innerHTML = '<i class="fa-solid fa-minus"></i>');
}

// Collapse to Leads
function collapseAllNodes() {
    document.querySelectorAll('.org-children-container').forEach(c => c.classList.add('collapsed'));
    document.querySelectorAll('.org-toggle-btn').forEach(b => b.innerHTML = '<i class="fa-solid fa-plus"></i>');
}

// Switch between Tree and Department Matrix View
function switchOrgView(view) {
    const treeView = document.getElementById('viewContainerTree');
    const matrixView = document.getElementById('viewContainerMatrix');
    const btnTree = document.getElementById('btnViewTree');
    const btnMatrix = document.getElementById('btnViewMatrix');
    const treeTools = document.getElementById('treeActionToolbar');

    if (view === 'tree') {
        treeView.style.display = 'block';
        matrixView.style.display = 'none';
        btnTree.classList.add('active');
        btnTree.style.background = '#ffffff';
        btnTree.style.color = '#1e293b';
        btnMatrix.classList.remove('active');
        btnMatrix.style.background = 'transparent';
        btnMatrix.style.color = '#64748b';
        if (treeTools) treeTools.style.display = 'flex';
    } else {
        treeView.style.display = 'none';
        matrixView.style.display = 'block';
        btnMatrix.classList.add('active');
        btnMatrix.style.background = '#ffffff';
        btnMatrix.style.color = '#1e293b';
        btnTree.classList.remove('active');
        btnTree.style.background = 'transparent';
        btnTree.style.color = '#64748b';
        if (treeTools) treeTools.style.display = 'none';
    }
}

// Filter Department
function filterOrgDepartment(deptId) {
    if (deptId) {
        window.location.href = '<?= url("org-chart") ?>?department_id=' + deptId;
    } else {
        window.location.href = '<?= url("org-chart") ?>';
    }
}

// Autocomplete Search & Jump to Node
const searchInput = document.getElementById('orgSearchInput');
const searchResults = document.getElementById('orgSearchResults');

if (searchInput) {
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        if (query.length === 0) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
            return;
        }

        const matches = orgFlatEmployees.filter(e => 
            e.name.toLowerCase().includes(query) || 
            e.emp_code.toLowerCase().includes(query) || 
            e.designation_title.toLowerCase().includes(query) ||
            e.department_name.toLowerCase().includes(query)
        );

        if (matches.length === 0) {
            searchResults.innerHTML = '<div style="padding: 12px; font-size: 13px; color: #94a3b8; text-align: center;">No matching employee</div>';
            searchResults.style.display = 'block';
            return;
        }

        let html = '';
        matches.forEach(m => {
            html += `
                <div onclick="jumpToNode(${m.id})" style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: space-between;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 28px; height: 28px; border-radius: 50%; background: #93206c; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                            ${m.avatar ? `<img src="${BASE_URL}/${m.avatar}" style="width: 100%; height: 100%; object-fit: cover;">` : m.first_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: #1e293b;">${m.name}</div>
                            <div style="font-size: 11.5px; color: #64748b;">${m.designation_title} &bull; ${m.department_name}</div>
                        </div>
                    </div>
                    <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 10.5px;">${m.emp_code}</span>
                </div>
            `;
        });
        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#orgSearchInput') && !e.target.closest('#orgSearchResults')) {
            if (searchResults) searchResults.style.display = 'none';
        }
    });
}

function jumpToNode(nodeId) {
    if (searchResults) searchResults.style.display = 'none';
    if (searchInput) searchInput.value = '';

    switchOrgView('tree');

    // Expand all parents of this node if collapsed
    const nodeEl = document.getElementById('emp-node-' + nodeId);
    if (!nodeEl) return;

    let parentContainer = nodeEl.closest('.org-children-container');
    while (parentContainer) {
        parentContainer.classList.remove('collapsed');
        const parentBranch = parentContainer.closest('.org-node-branch');
        if (parentBranch) {
            const toggle = parentBranch.querySelector('.org-toggle-btn');
            if (toggle) toggle.innerHTML = '<i class="fa-solid fa-minus"></i>';
        }
        parentContainer = parentBranch ? parentBranch.parentElement.closest('.org-children-container') : null;
    }

    // Highlight card
    document.querySelectorAll('.org-node-card').forEach(c => c.classList.remove('highlighted'));
    nodeEl.classList.add('highlighted');

    // Pan canvas to center this node
    const outerRect = outerCanvas.getBoundingClientRect();
    const cardRect = nodeEl.getBoundingClientRect();
    const offsetLeft = (cardRect.left + cardRect.width / 2) - (outerRect.left + outerRect.width / 2);
    const offsetTop = (cardRect.top + cardRect.height / 2) - (outerRect.top + outerRect.height / 2);

    orgPanX -= offsetLeft;
    orgPanY -= offsetTop;
    updateCanvasTransform();

    setTimeout(() => {
        nodeEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 150);

    // Open profile drawer
    const empData = orgFlatEmployees.find(e => e.id === nodeId);
    if (empData) {
        openProfileDrawer(empData);
    }
}

// Open Quick Profile Drawer
function openProfileDrawer(node) {
    activeDrawerNode = node;

    const drawerAvatarEl = document.getElementById('drawerAvatar');
    if (node.avatar) {
        drawerAvatarEl.innerHTML = `<img src="${BASE_URL}/${node.avatar}" alt="${node.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
    } else {
        drawerAvatarEl.textContent = (node.first_name || node.name || 'U').charAt(0).toUpperCase();
    }
    document.getElementById('drawerName').textContent = node.name;
    document.getElementById('drawerTitle').textContent = node.designation_title;
    document.getElementById('drawerDept').textContent = node.department_name;
    document.getElementById('drawerCode').textContent = node.emp_code;
    document.getElementById('drawerEmail').textContent = node.email || 'No email provided';
    document.getElementById('drawerPhone').textContent = node.phone || 'No phone provided';
    document.getElementById('drawerDoj').textContent = 'Joined: ' + (node.date_of_joining || 'N/A');

    // Manager
    const mgrName = node.manager_name ? node.manager_name : 'None (Direct / CEO)';
    document.getElementById('drawerManagerName').textContent = mgrName;

    // Direct Reportees
    const reportees = orgFlatEmployees.filter(e => e.manager_id === node.id);
    document.getElementById('drawerReporteesCount').textContent = reportees.length;
    
    const teamListEl = document.getElementById('drawerTeamList');
    if (reportees.length > 0) {
        let teamHtml = '';
        reportees.forEach(rep => {
            teamHtml += `
                <div onclick="jumpToNode(${rep.id})" style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background: #93206c; color: #fff; font-size: 10.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                            ${rep.avatar ? `<img src="${BASE_URL}/${rep.avatar}" style="width: 100%; height: 100%; object-fit: cover;">` : rep.first_name.charAt(0).toUpperCase()}
                        </div>
                        <div style="font-size: 12.5px; font-weight: 600; color: #1e293b;">${rep.name}</div>
                    </div>
                    <span style="font-size: 11px; color: #64748b;">${rep.designation_title}</span>
                </div>
            `;
        });
        teamListEl.innerHTML = teamHtml;
        document.getElementById('drawerTeamSection').style.display = 'block';
    } else {
        teamListEl.innerHTML = '<div style="font-size: 12px; color: #94a3b8; font-style: italic;">No direct reportees</div>';
    }

    // Profile link
    document.getElementById('drawerProfileBtn').href = '<?= url("employees/view?id=") ?>' + node.id;

    // Show drawer
    document.getElementById('profileDrawerOverlay').style.display = 'block';
    document.getElementById('profileDrawer').style.right = '0';
}

function closeProfileDrawer() {
    document.getElementById('profileDrawerOverlay').style.display = 'none';
    document.getElementById('profileDrawer').style.right = '-420px';
}

// Change Reporting Manager Modal
function openChangeManagerModal() {
    if (!activeDrawerNode) return;
    document.getElementById('reassign_employee_id').value = activeDrawerNode.id;
    document.getElementById('reassign_employee_name').textContent = activeDrawerNode.name;

    // Hide self from manager options
    document.querySelectorAll('#reassign_manager_select option').forEach(opt => {
        opt.style.display = 'block';
    });
    const selfOpt = document.querySelector('.mgr-opt-' + activeDrawerNode.id);
    if (selfOpt) selfOpt.style.display = 'none';

    // Set current manager
    document.getElementById('reassign_manager_select').value = activeDrawerNode.manager_id || '';

    document.getElementById('changeManagerModal').style.display = 'flex';
}


function filterOrgDepartment(deptId) {
    const url = new URL(window.location.href);
    if (deptId) {
        url.searchParams.set('department_id', deptId);
    } else {
        url.searchParams.delete('department_id');
    }
    window.location.href = url.toString();
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
