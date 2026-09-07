<?php
/**
 * Document Management & Digital Locker Model
 */

require_once __DIR__ . '/../Database.php';

class Document {
    public const TYPES = [
        'aadhaar_card'        => ['label' => 'Aadhaar Card (National ID)', 'mandatory' => true, 'icon' => 'fa-id-card'],
        'pan_card'            => ['label' => 'PAN Card (Tax ID)', 'mandatory' => true, 'icon' => 'fa-credit-card'],
        'educational_degree'  => ['label' => 'Degree / Educational Certificate', 'mandatory' => true, 'icon' => 'fa-graduation-cap'],
        'bank_passbook'       => ['label' => 'Bank Passbook / Cancelled Cheque', 'mandatory' => true, 'icon' => 'fa-building-columns'],
        'signed_nda'          => ['label' => 'Signed NDA & Confidentiality', 'mandatory' => true, 'icon' => 'fa-file-shield'],
        'offer_letter'        => ['label' => 'Official Offer Letter', 'mandatory' => false, 'icon' => 'fa-file-signature'],
        'appointment_letter'  => ['label' => 'Appointment Letter', 'mandatory' => false, 'icon' => 'fa-file-contract'],
        'previous_relieving'  => ['label' => 'Previous Relieving / Experience', 'mandatory' => false, 'icon' => 'fa-briefcase'],
        'passport'            => ['label' => 'Passport', 'mandatory' => false, 'icon' => 'fa-passport'],
        'other'               => ['label' => 'Other Certificate / Document', 'mandatory' => false, 'icon' => 'fa-folder-open']
    ];

    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp'
    ];
    public const MAX_FILE_SIZE = 5242880; // 5 MB

    public static function getTypes(): array {
        return self::TYPES;
    }

    public static function getCategories(): array {
        return self::TYPES;
    }

    /**
     * Upload and register a document in the locker
     */
    public static function upload(array $file, int $employeeId, string $docType, string $title, int $uploadedBy, ?string $expiryDate = null): int {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . ($file['error'] ?? 'unknown'));
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new Exception("File size exceeds 5MB limit. Please upload a smaller file.");
        }

        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception("Invalid file type. Only PDF, JPG, PNG, and WebP files are permitted.");
        }

        // Real binary MIME-type inspection (magic bytes sniffing)
        $tmpRaw = $file['tmp_name'] ?? '';
        $tmpPath = !empty($tmpRaw) ? (realpath($tmpRaw) ?: $tmpRaw) : '';
        if (empty($tmpPath) || !file_exists($tmpPath) || !is_readable($tmpPath)) {
            throw new Exception("Uploaded temporary file is inaccessible.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = @$finfo->file($tmpPath);

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new Exception("Security Alert: Disguised or unauthorized file content detected ({$detectedMime}). Only genuine PDF, JPG, PNG, or WebP documents are permitted.");
        }

        // Ensure file extension strictly aligns with detected binary MIME type
        $mimeExtMap = [
            'application/pdf' => ['pdf'],
            'image/jpeg'      => ['jpg', 'jpeg'],
            'image/png'       => ['png'],
            'image/webp'      => ['webp']
        ];

        if (!isset($mimeExtMap[$detectedMime]) || !in_array($ext, $mimeExtMap[$detectedMime], true)) {
            throw new Exception("Security Alert: File extension (.{$ext}) does not match genuine binary format ({$detectedMime}).");
        }

        if (!array_key_exists($docType, self::TYPES)) {
            $docType = 'other';
        }

        if (empty(trim($title))) {
            $title = self::TYPES[$docType]['label'] ?? 'Employee Document';
        }

        // Generate safe unique filename
        $safeFileName = 'doc_' . $employeeId . '_' . $docType . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $relativeDir = 'assets/uploads/documents';
        $targetDir = BASE_PATH . '/' . $relativeDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $safeFileName;
        $relativeFilePath = $relativeDir . '/' . $safeFileName;

        $stored = is_uploaded_file($file['tmp_name']) 
            ? move_uploaded_file($file['tmp_name'], $targetPath) 
            : copy($file['tmp_name'], $targetPath);

        if (!$stored) {
            throw new Exception("Failed to store uploaded file on the server.");
        }

        $validExpiry = !empty($expiryDate) ? $expiryDate : null;

        // Insert document record
        $sql = "INSERT INTO `employee_documents` 
                (`employee_id`, `document_type`, `title`, `file_path`, `file_name`, `file_size`, `file_ext`, `expiry_date`, `status`, `uploaded_by`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        
        Database::query($sql, [
            $employeeId,
            $docType,
            trim($title),
            $relativeFilePath,
            $originalName,
            (int)$file['size'],
            $ext,
            $validExpiry,
            $uploadedBy
        ]);

        $docId = (int)Database::pdo()->lastInsertId();

        // Audit log
        Database::query("INSERT INTO activity_logs (user_id, action, module, details) VALUES (?, 'upload_document', 'documents', ?)", [
            $uploadedBy,
            "Uploaded {$docType} for Employee ID #{$employeeId}"
        ]);

        return $docId;
    }

    /**
     * Get all documents for a specific employee
     */
    public static function getByEmployee(int $employeeId): array {
        $sql = "SELECT d.*, u.name AS uploader_name, v.name AS verifier_name
                FROM employee_documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                LEFT JOIN users v ON d.verified_by = v.id
                WHERE d.employee_id = ?
                ORDER BY d.created_at DESC";
        return Database::fetchAll($sql, [$employeeId]);
    }

    /**
     * Compute KYC & Onboarding compliance score for an employee
     */
    public static function getComplianceSummary(int $employeeId): array {
        $docs = self::getByEmployee($employeeId);
        
        $mandatoryTypes = array_keys(array_filter(self::TYPES, fn($t) => $t['mandatory']));
        $uploadedTypes = [];
        $verifiedTypes = [];

        foreach ($docs as $d) {
            $t = $d['document_type'];
            if (!in_array($t, $uploadedTypes, true)) {
                $uploadedTypes[] = $t;
            }
            if ($d['status'] === 'verified' && !in_array($t, $verifiedTypes, true)) {
                $verifiedTypes[] = $t;
            }
        }

        $totalMandatory = count($mandatoryTypes);
        $uploadedMandatory = count(array_intersect($mandatoryTypes, $uploadedTypes));
        $verifiedMandatory = count(array_intersect($mandatoryTypes, $verifiedTypes));

        $percentage = $totalMandatory > 0 ? round(($verifiedMandatory / $totalMandatory) * 100) : 100;

        return [
            'total_mandatory'    => $totalMandatory,
            'mandatory_total'    => $totalMandatory,
            'uploaded_mandatory' => $uploadedMandatory,
            'verified_mandatory' => $verifiedMandatory,
            'verified'           => $verifiedMandatory,
            'missing'            => max(0, $totalMandatory - $verifiedMandatory),
            'percentage'         => (int)$percentage,
            'is_compliant'       => ($verifiedMandatory === $totalMandatory),
            'uploaded_types'     => $uploadedTypes,
            'verified_types'     => $verifiedTypes
        ];
    }

    /**
     * Master query for HR Document Hub
     */
    public static function getAll(array $filters = []): array {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "d.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['document_type'])) {
            $where[] = "d.document_type = ?";
            $params[] = $filters['document_type'];
        }

        if (!empty($filters['department_id'])) {
            $where[] = "e.department_id = ?";
            $params[] = (int)$filters['department_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.emp_code LIKE ? OR d.title LIKE ? OR d.file_name LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT d.*, 
                       e.first_name, e.last_name, e.emp_code, e.department_id,
                       dept.name AS department_name,
                       u.name AS uploader_name,
                       v.name AS verifier_name
                FROM employee_documents d
                JOIN employees e ON d.employee_id = e.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN users u ON d.uploaded_by = u.id
                LEFT JOIN users v ON d.verified_by = v.id
                {$whereClause}
                ORDER BY d.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Find single document by ID
     */
    public static function findById(int $id): ?array {
        $sql = "SELECT d.*, e.first_name, e.last_name, e.emp_code, e.user_id, e.user_id AS emp_user_id
                FROM employee_documents d
                JOIN employees e ON d.employee_id = e.id
                WHERE d.id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    /**
     * Mark document as verified
     */
    public static function verify(int $id, int $verifiedBy): bool {
        $sql = "UPDATE employee_documents 
                SET status = 'verified', rejection_reason = NULL, verified_by = ?, verified_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        Database::query($sql, [$verifiedBy, $id]);

        Database::query("INSERT INTO activity_logs (user_id, action, module, details) VALUES (?, 'verify_document', 'documents', ?)", [
            $verifiedBy,
            "Verified document #{$id}"
        ]);

        return true;
    }

    /**
     * Reject document with remarks
     */
    public static function reject(int $id, string $reason, int $verifiedBy): bool {
        $sql = "UPDATE employee_documents 
                SET status = 'rejected', rejection_reason = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        Database::query($sql, [trim($reason), $verifiedBy, $id]);

        Database::query("INSERT INTO activity_logs (user_id, action, module, details) VALUES (?, 'reject_document', 'documents', ?)", [
            $verifiedBy,
            "Rejected document #{$id}: {$reason}"
        ]);

        return true;
    }

    /**
     * Delete document and file
     */
    public static function delete(int $id): bool {
        $doc = self::findById($id);
        if (!$doc) {
            return false;
        }

        $fullPath = BASE_PATH . '/' . $doc['file_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        Database::query("DELETE FROM employee_documents WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Count pending document approvals
     */
    public static function getPendingCount(): int {
        $res = Database::fetchOne("SELECT COUNT(*) AS c FROM employee_documents WHERE status = 'pending'");
        return (int)($res['c'] ?? 0);
    }
}
