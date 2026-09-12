<?php
$pageTitle = 'Company Holiday Calendar';
require_once BASE_PATH . '/views/layouts/header.php';

$currentYear = (int)date('Y');
$selectedYear = (int)($year ?? $currentYear);
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <h2 class="card-title" style="margin: 0;">
                <i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i>
                Company Holidays (<?= $selectedYear ?>)
            </h2>

            <!-- Year Selector Pills -->
            <div style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: var(--radius-sm); gap: 2px;">
                <?php foreach ([$currentYear - 1, $currentYear, $currentYear + 1] as $y): ?>
                    <a href="<?= url('leaves/holidays?year=' . $y) ?>" 
                       style="padding: 5px 12px; border-radius: var(--radius-sm); font-size: 12.5px; font-weight: 600; text-decoration: none; transition: all 0.2s; color: <?= $selectedYear === $y ? 'var(--text-main)' : 'var(--text-muted)' ?>; background: <?= $selectedYear === $y ? '#ffffff' : 'transparent' ?>; <?= $selectedYear === $y ? 'box-shadow: var(--shadow-xs);' : '' ?>">
                        <?= $y ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; gap: 8px;">
            <?php if (Auth::isHR()): ?>
                <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addHolidayModal')">
                    <i class="fa-solid fa-plus"></i> Add Holiday
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div style="padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; gap: 10px; align-items: center; flex: 1; max-width: 480px;">
            <div style="position: relative; flex: 1;">
                <input type="text" id="searchHolidayInput" class="form-control" placeholder="Search holiday or festival..." style="padding-left: 36px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 13px;"></i>
            </div>
            <select id="filterHolidayType" class="form-control" style="width: 160px;">
                <option value="">All Categories</option>
                <option value="mandatory">Gazetted</option>
                <option value="optional">Restricted</option>
            </select>
        </div>

        <div style="font-size: 13px; color: var(--text-muted);">
            Showing <strong id="holidayVisibleCount"><?= count($holidays) ?></strong> of <?= count($holidays) ?> holidays
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Occasion</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Classification</th>
                        <th>Notes</th>
                        <?php if (Auth::isHR()): ?>
                            <th style="text-align: right;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="holidayTableBody">
                    <?php if (empty($holidays)): ?>
                        <tr>
                            <td colspan="<?= Auth::isHR() ? '6' : '5' ?>" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No holidays scheduled for <?= $selectedYear ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($holidays as $h): 
                            $hDate = strtotime($h['holiday_date']);
                            $isPast = $hDate < strtotime(date('Y-m-d'));
                            $isSoon = !$isPast && ($hDate <= strtotime('+14 days'));
                        ?>
                            <tr class="holiday-row" 
                                data-search="<?= strtolower(e($h['title'] . ' ' . date('d F Y l', $hDate) . ' ' . ($h['description'] ?? ''))) ?>"
                                data-type="<?= e($h['type'] ?? 'mandatory') ?>"
                                style="<?= $isPast ? 'opacity: 0.6;' : '' ?>">
                                <td>
                                    <strong><?= e($h['title']) ?></strong>
                                    <?php if ($isSoon): ?>
                                        <span class="badge badge-success" style="font-size: 10px; margin-left: 6px;">Upcoming</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= date('d M Y', $hDate) ?></strong>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted); font-size: 13px;"><?= date('l', $hDate) ?></span>
                                </td>
                                <td>
                                    <?php if (($h['type'] ?? '') === 'mandatory'): ?>
                                        <span class="badge badge-teal" style="font-size: 11px;">Gazetted</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" style="font-size: 11px;">Restricted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: var(--text-muted);"><?= e($h['description'] ?? '--') ?></small>
                                </td>
                                <?php if (Auth::isHR()): ?>
                                    <td style="text-align: right;">
                                        <form action="<?= url('leaves/delete-holiday') ?>" method="POST" style="display: inline;" onsubmit="return confirmAction('Are you sure you want to remove &quot;<?= e(addslashes($h['title'])) ?>&quot; from the holiday calendar?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                            <input type="hidden" name="year" value="<?= $selectedYear ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Delete Holiday" style="padding: 4px 8px;">
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

        <div id="holidayPaginationContainer" class="pagination-container" style="display: none; padding: 12px 20px; border-top: 1px solid var(--border-color);">
            <div class="pagination-info" id="holidayPaginationInfo"></div>
            <div class="pagination-nav" id="holidayPaginationNav"></div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: ADD HOLIDAY -->
<!-- ======================================================== -->
<?php if (Auth::isHR()): ?>
<div id="addHolidayModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 480px; margin: 0; box-shadow: var(--shadow-lg); animation: fadeIn 0.2s ease-out;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
            <h3 class="card-title" style="margin: 0; font-size: 16px;"><i class="fa-solid fa-calendar-plus text-primary"></i> Add Holiday</h3>
            <button type="button" onclick="closeModal('addHolidayModal')" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form action="<?= url('leaves/holidays') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="year" value="<?= $selectedYear ?>">
            <div class="card-body" style="padding: 20px;">
                <div class="form-group">
                    <label class="form-label">Occasion / Festival Name <span style="color:#e11d48;">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Independence Day" required>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Date <span style="color:#e11d48;">*</span></label>
                        <input type="date" name="holiday_date" class="form-control" value="<?= $selectedYear ?>-01-01" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Classification</label>
                        <select name="type" class="form-control">
                            <option value="mandatory">Gazetted (Mandatory)</option>
                            <option value="optional">Restricted (Optional)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Notes</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 8px; background: #f8fafc; padding: 12px 20px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeModal('addHolidayModal')">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Holiday</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    setupHolidayPagination(10);
});

function setupHolidayPagination(pageSize = 10) {
    const tbody = document.getElementById('holidayTableBody');
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('.holiday-row'));
    const searchInput = document.getElementById('searchHolidayInput');
    const typeSelect = document.getElementById('filterHolidayType');
    const container = document.getElementById('holidayPaginationContainer');
    const info = document.getElementById('holidayPaginationInfo');
    const nav = document.getElementById('holidayPaginationNav');
    const countEl = document.getElementById('holidayVisibleCount');

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
            html += `<button type="button" class="page-link" ${currentPage === 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''} onclick="changeHolidayPage(${currentPage - 1})"><i class="fa-solid fa-chevron-left"></i></button>`;

            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    html += `<button type="button" class="page-link ${i === currentPage ? 'active' : ''}" onclick="changeHolidayPage(${i})">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += `<span class="page-link" style="border:none;cursor:default;">...</span>`;
                }
            }

            html += `<button type="button" class="page-link ${currentPage === totalPages ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''} onclick="changeHolidayPage(${currentPage + 1})"><i class="fa-solid fa-chevron-right"></i></button>`;
            nav.innerHTML = html;
        }
    }

    function applyFilter() {
        const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const t = typeSelect ? typeSelect.value : '';

        filteredRows = allRows.filter(row => {
            const matchesSearch = !q || (row.getAttribute('data-search') || '').includes(q);
            const matchesType = !t || (row.getAttribute('data-type') || '') === t;
            return matchesSearch && matchesType;
        });

        currentPage = 1;
        render();
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (typeSelect) typeSelect.addEventListener('change', applyFilter);

    window['changeHolidayPage'] = (p) => {
        currentPage = p;
        render();
    };

    render();
}
</script>

<?php require_once BASE_PATH . '/views/layouts/footer.php'; ?>
