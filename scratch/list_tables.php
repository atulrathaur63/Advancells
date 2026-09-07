<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';

$tables = Database::fetchAll('SHOW TABLES');
foreach ($tables as $t) {
    echo array_values($t)[0] . "\n";
}
