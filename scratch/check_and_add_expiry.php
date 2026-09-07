<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';

$cols = Database::fetchAll("SHOW COLUMNS FROM employee_documents");
$fields = array_column($cols, 'Field');

if (!in_array('expiry_date', $fields, true)) {
    Database::query("ALTER TABLE employee_documents ADD COLUMN expiry_date DATE NULL AFTER verified_at");
    echo "Added expiry_date column\n";
} else {
    echo "expiry_date already exists\n";
}

$cols = Database::fetchAll("SHOW COLUMNS FROM employee_documents");
print_r(array_column($cols, 'Field'));
