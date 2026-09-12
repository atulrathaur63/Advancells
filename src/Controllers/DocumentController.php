<?php
/**
 * Document Locker & Compliance Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Document.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Models/Notification.php';

class DocumentController {
    /**
     * Master HR Document Locker & Compliance Matrix
     */
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        $filters = [
            'status'        => $_GET['status'] ?? null,
            'document_type' => $_GET['document_type'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'search'        => trim($_GET['search'] ?? '')
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 15)));

        $totalDocs = Document::countAll($filters);
        $pagination = paginate($totalDocs, $page, $perPage);
        $documents = Document::getAll($filters, $pagination['limit'], $pagination['offset']);
        $departments = Department::getAll();
        
        // Calculate master stats via single aggregate query
        $stats = Document::getStats();

        $employees = Employee::getAll(['status' => 'active']);

        require_once BASE_PATH . '/views/documents/index.php';
    }

    /**
     * Employee Self-Service Document Locker
     */
    public function myDocuments(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();

        if (!$empId) {
            flash('warning', 'Employee profile not associated with this account.');
            redirect('dashboard');
        }

        $employee = Employee::findById($empId);
        $documents = Document::getByEmployee($empId);
        $compliance = Document::getComplianceSummary($empId);

        require_once BASE_PATH . '/views/documents/my_documents.php';
    }

    /**
     * Handle document file upload (multipart/form-data)
     */
    public function upload(): void {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid or expired upload request.');
            redirect('documents/my-documents');
        }

        $currentUser = Auth::user();
        $isHR = Auth::isHR();
        $currentEmpId = Auth::employeeId();

        // Target employee ID
        $targetEmpId = isset($_POST['employee_id']) && $isHR ? (int)$_POST['employee_id'] : $currentEmpId;

        if (!$targetEmpId) {
            flash('danger', 'Target employee identifier is missing.');
            redirect('documents');
        }

        $docType = $_POST['document_type'] ?? 'other';
        $title = trim($_POST['title'] ?? '');

        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] === UPLOAD_ERR_NO_FILE) {
            flash('danger', 'Please select a valid document file to upload.');
            $this->redirectBack($targetEmpId);
            return;
        }

        $expiryDate = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;

        try {
            Document::upload($_FILES['document_file'], $targetEmpId, $docType, $title, Auth::id(), $expiryDate);
            flash('success', 'Document successfully uploaded and queued for HR verification!');
        } catch (Exception $e) {
            flash('danger', 'Upload failed: ' . $e->getMessage());
        }

        $this->redirectBack($targetEmpId);
    }

    /**
     * Verify document (HR Admin / Super Admin)
     */
    public function verify(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid request.');
            redirect('documents');
        }

        $id = (int)($_POST['id'] ?? 0);
        $doc = Document::findById($id);

        if (!$doc) {
            flash('danger', 'Document record not found.');
            redirect('documents');
        }

        Document::verify($id, Auth::id());
        $docUserId = (int)($doc['emp_user_id'] ?? $doc['user_id'] ?? 0);
        if ($docUserId > 0) {
            Notification::send($docUserId, 'document', 'Document Verified', "Your document '{$doc['title']}' has been verified by HR.", 'documents/my-documents', 'fa-file-circle-check', '#16a34a');
        }
        flash('success', "Document '{$doc['title']}' for {$doc['first_name']} {$doc['last_name']} marked as Verified!");

        $this->safeRedirect($_POST['redirect_to'] ?? null, 'documents');
    }

    /**
     * Reject document with explanatory reason
     */
    public function reject(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid request.');
            redirect('documents');
        }

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? $_POST['remarks'] ?? '');
        if (empty($reason)) {
            $reason = 'Document could not be verified.';
        }
        $doc = Document::findById($id);

        if (!$doc) {
            flash('danger', 'Document record not found.');
            redirect('documents');
        }

        Document::reject($id, $reason, Auth::id());
        $docUserId = (int)($doc['emp_user_id'] ?? $doc['user_id'] ?? 0);
        if ($docUserId > 0) {
            Notification::send($docUserId, 'document', 'Document Action Required', "Your document '{$doc['title']}' was rejected: {$reason}", 'documents/my-documents', 'fa-file-circle-xmark', '#dc2626');
        }
        flash('warning', "Document '{$doc['title']}' has been marked as Rejected.");

        $this->safeRedirect($_POST['redirect_to'] ?? null, 'documents');
    }

    /**
     * Securely stream/preview document file
     */
    public function download(): void {
        Auth::requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $doc = Document::findById($id);

        if (!$doc) {
            http_response_code(404);
            die("Document not found.");
        }

        // Authorization check: Super Admin, HR, Manager or document owner
        $currentRole = Auth::role();
        $currentEmpId = (int)Auth::employeeId();
        $docOwnerEmpId = (int)$doc['employee_id'];

        if ($currentRole === 'employee' && $docOwnerEmpId !== $currentEmpId) {
            http_response_code(403);
            die("Access Denied: You do not have permission to access this document.");
        }

        if ($currentRole === 'manager') {
            if ($docOwnerEmpId !== $currentEmpId && !Employee::isSubordinateOf($docOwnerEmpId, $currentEmpId)) {
                http_response_code(403);
                die("Access Denied: You can only access documents of your direct or indirect team reportees.");
            }
        }

        $fullPath = BASE_PATH . '/' . $doc['file_path'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            die("Physical document file does not exist on storage.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath) ?: 'application/octet-stream';
        finfo_close($finfo);

        $isInline = isset($_GET['preview']) && $_GET['preview'] == '1';
        $disposition = $isInline ? 'inline' : 'attachment';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($doc['file_name']) . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, must-revalidate, max-age=0');
        header('Pragma: public');

        readfile($fullPath);
        exit;
    }

    /**
     * Delete document
     */
    public function delete(): void {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf()) {
            flash('danger', 'Invalid request.');
            redirect('documents');
        }

        $id = (int)($_POST['id'] ?? 0);
        $doc = Document::findById($id);

        if (!$doc) {
            flash('danger', 'Document not found.');
            redirect('documents');
        }

        $isHR = Auth::isHR();
        $isOwner = ((int)$doc['employee_id'] === (int)Auth::employeeId() && $doc['status'] === 'pending');

        if (!$isHR && !$isOwner) {
            flash('danger', 'You do not have permission to delete this verified document.');
            redirect('documents');
        }

        Document::delete($id);
        flash('success', "Document deleted successfully.");

        $this->safeRedirect($_POST['redirect_to'] ?? null, 'documents');
    }

    private function safeRedirect(?string $target, string $defaultRoute = 'documents'): void {
        if (!empty($target) && str_starts_with($target, BASE_URL)) {
            header("Location: " . $target);
            exit;
        }
        redirect($defaultRoute);
    }

    private function redirectBack(int $targetEmpId): void {
        $target = $_POST['redirect_to'] ?? '';
        if (!empty($target) && str_starts_with($target, BASE_URL)) {
            header("Location: " . $target);
            exit;
        }

        if (Auth::isHR()) {
            redirect('employees/view?id=' . $targetEmpId);
        } else {
            redirect('documents/my-documents');
        }
    }

    private function exportCsv(array $filters): void {
        $documents = Document::getAll($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=advancells_document_compliance_' . date('Ymd_His') . '.csv');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Emp Code', 'Employee Name', 'Department', 'Document Type', 'Document Title', 'Original File', 'File Size (KB)', 'Status', 'Uploaded On', 'Verified By', 'Verified At', 'Expiry Date', 'Rejection Reason']);

        foreach ($documents as $d) {
            $typeMeta = Document::TYPES[$d['document_type']] ?? ['label' => ucfirst($d['document_type'])];
            fputcsv($out, [
                $d['emp_code'] ?? 'N/A',
                ($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''),
                $d['department_name'] ?? 'N/A',
                $typeMeta['label'],
                $d['title'],
                $d['file_name'],
                round($d['file_size'] / 1024, 1),
                strtoupper($d['status']),
                $d['created_at'],
                $d['verifier_name'] ?? 'N/A',
                $d['verified_at'] ?? 'N/A',
                $d['expiry_date'] ?? 'N/A',
                $d['rejection_reason'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }
}
