<?php
/**
 * Company Asset & Device Management Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Asset.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Department.php';

class AssetController {
    /**
     * Master Asset Registry & Hardware Dashboard
     */
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $filters = [
            'category'    => $_GET['category'] ?? null,
            'status'      => $_GET['status'] ?? null,
            'condition'   => $_GET['condition'] ?? null,
            'employee_id' => !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null,
            'search'      => trim($_GET['search'] ?? '')
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 15)));

        $totalAssets = Asset::countAll($filters);
        $pagination = paginate($totalAssets, $page, $perPage);
        $assets = Asset::getAll($filters, $pagination['limit'], $pagination['offset']);
        $stats = Asset::getStats();
        $categories = Asset::getCategories();
        $conditions = Asset::getConditions();
        $availableAssets = Asset::getAvailable();
        $employees = Employee::getAll(['status' => 'active']);

        require_once BASE_PATH . '/views/assets/index.php';
    }

    /**
     * Employee Self-Service (ESS): My Assigned Assets
     */
    public function myAssets(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();

        if (!$empId) {
            flash('warning', 'Your user account is not linked to an employee profile.');
            redirect('dashboard');
        }

        $employee = Employee::findById($empId);
        $activeAssets = Asset::getByEmployee($empId, true);
        $pastAssets = Asset::getByEmployee($empId, false);
        $categories = Asset::getCategories();
        $conditions = Asset::getConditions();

        require_once BASE_PATH . '/views/assets/my_assets.php';
    }

    /**
     * Register a new asset into inventory
     */
    public function create(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid or expired request.');
            redirect('assets');
        }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            flash('danger', 'Asset name is required.');
            redirect('assets');
        }

        try {
            $assetId = Asset::create($_POST);
            flash('success', "Asset registered successfully into inventory!");
        } catch (Exception $e) {
            flash('danger', 'Failed to register asset: ' . $e->getMessage());
        }

        redirect('assets');
    }

    /**
     * Update asset specifications
     */
    public function edit(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $asset = Asset::findById($id);
        if (!$asset) {
            flash('danger', 'Asset record not found.');
            redirect('assets');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                flash('danger', 'Security validation failed.');
                redirect("assets/view?id={$id}");
            }

            try {
                Asset::update($id, $_POST);
                flash('success', "Asset specifications for {$asset['asset_code']} updated successfully!");
            } catch (Exception $e) {
                flash('danger', 'Update failed: ' . $e->getMessage());
            }

            redirect("assets/view?id={$id}");
        }

        $categories = Asset::getCategories();
        $conditions = Asset::getConditions();
        require_once BASE_PATH . '/views/assets/edit.php';
    }

    /**
     * Delete an unallocated asset
     */
    public function delete(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid request.');
            redirect('assets');
        }

        $id = (int)($_POST['id'] ?? 0);
        $asset = Asset::findById($id);

        if (!$asset) {
            flash('danger', 'Asset not found.');
            redirect('assets');
        }

        if ($asset['status'] === 'allocated') {
            flash('danger', "Cannot delete {$asset['asset_code']} because it is currently assigned to an employee. Please return the asset first.");
            redirect('assets');
        }

        if (Asset::delete($id)) {
            flash('success', "Asset {$asset['asset_code']} has been removed from the registry.");
        } else {
            flash('danger', 'Failed to delete asset record.');
        }

        redirect('assets');
    }

    /**
     * Allocate an available asset to an employee
     */
    public function allocate(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid request.');
            redirect('assets');
        }

        $assetId = (int)($_POST['asset_id'] ?? 0);
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $date = !empty($_POST['allocated_date']) ? trim($_POST['allocated_date']) : date('Y-m-d');
        $expectedReturn = !empty($_POST['expected_return_date']) ? trim($_POST['expected_return_date']) : null;
        $notes = trim($_POST['notes'] ?? '');

        if (!$assetId || !$employeeId) {
            flash('danger', 'Please select both an asset and an employee.');
            $this->redirectBack();
            return;
        }

        $result = Asset::allocate($assetId, $employeeId, (int)Auth::id(), $date, $expectedReturn, $notes);
        flash($result['success'] ? 'success' : 'danger', $result['message']);

        $this->redirectBack();
    }

    /**
     * Process return of an allocated asset
     */
    public function returnAsset(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid request.');
            redirect('assets');
        }

        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        $returnDate = !empty($_POST['returned_date']) ? trim($_POST['returned_date']) : date('Y-m-d');
        $condition = $_POST['return_condition'] ?? 'good';
        $nextStatus = $_POST['next_status'] ?? 'available';
        $notes = trim($_POST['return_notes'] ?? '');

        if (!$allocationId) {
            flash('danger', 'Allocation identifier is missing.');
            $this->redirectBack();
            return;
        }

        $result = Asset::returnAsset($allocationId, (int)Auth::id(), $returnDate, $condition, $notes, $nextStatus);
        flash($result['success'] ? 'success' : 'danger', $result['message']);

        $this->redirectBack();
    }

    /**
     * Single asset detail view with lifecycle history
     */
    public function view(): void {
        Auth::requireRole(['super_admin', 'hr_admin', 'manager']);

        $id = (int)($_GET['id'] ?? 0);
        $asset = Asset::findById($id);

        if (!$asset) {
            flash('danger', 'Asset record not found.');
            redirect('assets');
        }

        $history = Asset::getAllocationsByAsset($id);
        $categories = Asset::getCategories();
        $conditions = Asset::getConditions();
        $employees = Employee::getAll(['status' => 'active']);

        require_once BASE_PATH . '/views/assets/view.php';
    }

    private function redirectBack(): void {
        $target = $_POST['redirect_to'] ?? '';
        if (!empty($target) && str_starts_with($target, BASE_URL)) {
            header("Location: " . $target);
            exit;
        }
        redirect('assets');
    }

    private function exportCsv(array $filters): void {
        $assets = Asset::getAll($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=advancells_assets_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Asset Code', 'Device / Equipment Name', 'Category', 'Brand', 'Model',
            'Serial Number', 'Purchase Date', 'Cost (INR)', 'Warranty Expiry',
            'Status', 'Condition', 'Current Custodian', 'Emp Code', 'Department'
        ]);

        foreach ($assets as $a) {
            fputcsv($out, [
                $a['asset_code'],
                $a['name'],
                ucwords(str_replace('_', ' ', $a['category'])),
                $a['brand'] ?? 'N/A',
                $a['model'] ?? 'N/A',
                $a['serial_number'] ?? 'N/A',
                $a['purchase_date'] ?? 'N/A',
                $a['purchase_cost'],
                $a['warranty_expiry'] ?? 'N/A',
                ucwords(str_replace('_', ' ', $a['status'])),
                ucwords(str_replace('_', ' ', $a['condition'])),
                $a['employee_name'] ?? 'Unassigned (In Pool)',
                $a['emp_code'] ?? '',
                $a['department_name'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }
}
