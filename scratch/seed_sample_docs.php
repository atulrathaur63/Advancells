<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Models/Document.php';

$uploadDir = BASE_PATH . '/assets/uploads/documents';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$sampleSrc = BASE_PATH . '/scratch/sample_aadhaar.png';
if (file_exists($sampleSrc)) {
    $destName1 = 'doc_1_aadhaar_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
    copy($sampleSrc, $uploadDir . '/' . $destName1);
    
    Database::query("INSERT INTO employee_documents (employee_id, document_type, title, file_path, file_name, file_size, file_ext, status, uploaded_by) VALUES (?, 'aadhaar_card', 'National Aadhaar Card (Front & Back)', ?, 'sample_aadhaar.png', ?, 'png', 'pending', 1)", [
        1,
        'assets/uploads/documents/' . $destName1,
        filesize($sampleSrc)
    ]);
    echo "Created pending document for Emp 1\n";

    $destName2 = 'doc_4_pan_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
    copy($sampleSrc, $uploadDir . '/' . $destName2);
    
    Database::query("INSERT INTO employee_documents (employee_id, document_type, title, file_path, file_name, file_size, file_ext, status, verified_by, verified_at, uploaded_by) VALUES (?, 'pan_card', 'Permanent Account Number (PAN) Card', ?, 'sample_pan.png', ?, 'png', 'verified', 1, CURRENT_TIMESTAMP, 4)", [
        4,
        'assets/uploads/documents/' . $destName2,
        filesize($sampleSrc)
    ]);
    echo "Created verified document for Emp 4\n";
}
