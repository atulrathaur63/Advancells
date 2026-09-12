<?php
/**
 * Advancells HRMS - Annual Leave Rollover CLI Automation
 *
 * Automates year-end leave balance management:
 * 1. Unused Earned Leaves (EL) roll over up to statutory caps (default 30 days).
 * 2. Unused Sick Leave (SL) and Casual Leave (CL) reset for the new calendar year.
 * 3. Initializes fresh leave quota records in `leave_balances` for all active employees.
 *
 * Usage:
 *   php cron/year_end_leave_rollover.php [options]
 *
 * Options:
 *   --from-year=YYYY     Source year to read remaining balances from (default: current year - 1)
 *   --to-year=YYYY       Target year to initialize balances for (default: current year)
 *   --max-el-carry=N     Maximum Earned Leave days allowed to carry forward (default: 30)
 *   --emp-id=N           Process only a specific employee ID (optional)
 *   --dry-run            Simulate calculations without writing to database
 *   --force              Overwrite/update existing records if target year already exists
 *   --help               Display this help manual
 */

// 1. Security check: Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Denied: This script can only be executed via the command-line interface (CLI).\n");
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/database.php';
require_once $rootDir . '/src/Database.php';
require_once $rootDir . '/src/Models/Leave.php';
require_once $rootDir . '/src/Models/Employee.php';

// 2. Parse command-line arguments
$longopts = [
    'from-year::',
    'to-year::',
    'max-el-carry::',
    'emp-id::',
    'dry-run',
    'force',
    'help'
];
$options = getopt('', $longopts);

if (isset($options['help'])) {
    echo "Advancells HRMS - Annual Leave Rollover CLI Tool\n";
    echo "=================================================\n\n";
    echo "Usage:\n";
    echo "  php cron/year_end_leave_rollover.php [options]\n\n";
    echo "Options:\n";
    echo "  --from-year=YYYY    Source year (default: previous year)\n";
    echo "  --to-year=YYYY      Target year (default: current year)\n";
    echo "  --max-el-carry=N    Max EL carry-forward cap in days (default: 30)\n";
    echo "  --emp-id=N          Target specific employee ID (default: all active)\n";
    echo "  --dry-run           Simulate without modifying database records\n";
    echo "  --force             Update existing records for target year\n";
    echo "  --help              Show this help message\n\n";
    echo "Examples:\n";
    echo "  php cron/year_end_leave_rollover.php --dry-run\n";
    echo "  php cron/year_end_leave_rollover.php --from-year=2026 --to-year=2027\n";
    echo "  php cron/year_end_leave_rollover.php --from-year=2026 --to-year=2027 --max-el-carry=30\n";
    exit(0);
}

$currentYear = (int)date('Y');
$fromYear = isset($options['from-year']) ? (int)$options['from-year'] : ($currentYear - 1);
$toYear = isset($options['to-year']) ? (int)$options['to-year'] : $currentYear;
$maxElCarry = isset($options['max-el-carry']) ? (float)$options['max-el-carry'] : 30.0;
$targetEmpId = isset($options['emp-id']) ? (int)$options['emp-id'] : null;
$isDryRun = isset($options['dry-run']);
$isForce = isset($options['force']);

if ($fromYear >= $toYear) {
    fwrite(STDERR, "Error: --from-year ({$fromYear}) must be strictly less than --to-year ({$toYear}).\n");
    exit(1);
}

echo "===============================================================================\n";
echo " ADVANCELLS HRMS: ANNUAL LEAVE ROLLOVER AUTOMATION\n";
echo "===============================================================================\n";
echo " Mode             : " . ($isDryRun ? "DRY-RUN (Simulation only, zero DB writes)" : "LIVE (Writing to Database)") . "\n";
echo " Rollover Period  : From Year {$fromYear} -> To Year {$toYear}\n";
echo " Max EL Cap       : {$maxElCarry} days\n";
echo " Overwrite Force  : " . ($isForce ? "YES (--force)" : "NO") . "\n";
if ($targetEmpId) {
    echo " Target Employee  : Employee ID #{$targetEmpId}\n";
}
echo " Timestamp        : " . date('Y-m-d H:i:s') . "\n";
echo "-------------------------------------------------------------------------------\n\n";

// 3. Fetch active employees
$empSql = "SELECT id, emp_code, first_name, last_name, date_of_joining, status FROM employees WHERE 1=1";
$params = [];
if ($targetEmpId) {
    $empSql .= " AND id = ?";
    $params[] = $targetEmpId;
} else {
    $empSql .= " AND status = 'active'";
}
$empSql .= " ORDER BY id ASC";
$employees = Database::fetchAll($empSql, $params);

if (empty($employees)) {
    echo "No matching employees found to process.\n";
    exit(0);
}

// 4. Fetch leave types
$leaveTypes = Database::fetchAll("SELECT * FROM leave_types ORDER BY id ASC");
if (empty($leaveTypes)) {
    fwrite(STDERR, "Error: No leave types configured in database.\n");
    exit(1);
}

// Table Header
printf(
    "%-8s | %-16s | %-4s | %6s | %6s | %6s | %6s | %6s | %6s | %s\n",
    "EmpCode", "Name", "Type", "PrevTot", "PrevUse", "Unused", "Carried", "Lapsed", "NewTot", "Action"
);
echo str_repeat("-", 95) . "\n";

$stats = [
    'employees_processed' => 0,
    'records_created' => 0,
    'records_updated' => 0,
    'records_skipped' => 0,
    'total_el_carried' => 0.0,
    'total_days_lapsed' => 0.0,
];

foreach ($employees as $emp) {
    $stats['employees_processed']++;
    $empFullName = trim($emp['first_name'] . ' ' . $emp['last_name']);

    foreach ($leaveTypes as $lt) {
        $typeCode = strtoupper($lt['code']);

        // Check if employee had balance in from-year
        $prevBalance = Database::fetchOne(
            "SELECT * FROM leave_balances WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
            [$emp['id'], $lt['id'], $fromYear]
        );

        $prevTotal = $prevBalance ? (float)$prevBalance['total_allocated'] : 0.0;
        $prevUsed = $prevBalance ? (float)$prevBalance['used'] : 0.0;
        $unused = max(0.0, $prevTotal - $prevUsed);

        // Determine carry forward and lapsed amounts
        $carried = 0.0;
        $lapsed = 0.0;

        $typeCap = !empty($lt['max_carry_forward']) ? (float)$lt['max_carry_forward'] : ($typeCode === 'EL' ? $maxElCarry : 0.0);

        if ($typeCap > 0 && $unused > 0) {
            $carried = min($unused, $typeCap);
            $lapsed = max(0.0, $unused - $carried);
        } else {
            $carried = 0.0;
            $lapsed = $unused;
        }

        // New year fresh annual quota
        $newAnnualQuota = (float)$lt['days_per_year'];
        $newTotalAllocated = $carried + $newAnnualQuota;

        // Check if record already exists for target year
        $existingTarget = Database::fetchOne(
            "SELECT id, carried_forward, total_allocated, used, pending FROM leave_balances WHERE employee_id = ? AND leave_type_id = ? AND year = ?",
            [$emp['id'], $lt['id'], $toYear]
        );

        $action = '';

        if ($existingTarget) {
            if ($isForce) {
                $action = $isDryRun ? "[SIM-UPDATE]" : "UPDATED";
                if (!$isDryRun) {
                    Database::update('leave_balances', [
                        'carried_forward' => $carried,
                        'total_allocated' => max($newTotalAllocated, (float)$existingTarget['used'] + (float)$existingTarget['pending'])
                    ], "id = ?", [$existingTarget['id']]);
                }
                $stats['records_updated']++;
                $stats['total_el_carried'] += $carried;
                $stats['total_days_lapsed'] += $lapsed;
            } else {
                $action = "SKIPPED (Exists)";
                $stats['records_skipped']++;
            }
        } else {
            $action = $isDryRun ? "[SIM-CREATE]" : "CREATED";
            if (!$isDryRun) {
                Database::insert('leave_balances', [
                    'employee_id' => $emp['id'],
                    'leave_type_id' => $lt['id'],
                    'year' => $toYear,
                    'carried_forward' => $carried,
                    'total_allocated' => $newTotalAllocated,
                    'used' => 0.0,
                    'pending' => 0.0
                ]);
            }
            $stats['records_created']++;
            $stats['total_el_carried'] += $carried;
            $stats['total_days_lapsed'] += $lapsed;
        }

        printf(
            "%-8s | %-16s | %-4s | %6.1f | %6.1f | %6.1f | %6.1f | %6.1f | %6.1f | %s\n",
            $emp['emp_code'],
            mb_strimwidth($empFullName, 0, 16, '..'),
            $typeCode,
            $prevTotal,
            $prevUsed,
            $unused,
            $carried,
            $lapsed,
            $newTotalAllocated,
            $action
        );
    }
}

echo str_repeat("-", 95) . "\n\n";

// 5. Audit Logging (if live execution)
if (!$isDryRun) {
    try {
        Database::logActivity(
            1, // System / Super Admin
            'ROLLOVER',
            'LEAVE_BALANCES',
            sprintf(
                "Annual leave rollover executed from %d to %d: %d created, %d updated, %d skipped. Total EL carried: %.1f days, Lapsed: %.1f days.",
                $fromYear,
                $toYear,
                $stats['records_created'],
                $stats['records_updated'],
                $stats['records_skipped'],
                $stats['total_el_carried'],
                $stats['total_days_lapsed']
            )
        );
    } catch (Throwable $e) {
        // Safe fail-safe for CLI activity logging
    }
}

// 6. Print Summary
echo "===============================================================================\n";
echo " ROLLOVER EXECUTION SUMMARY\n";
echo "===============================================================================\n";
echo " Employees Processed        : " . $stats['employees_processed'] . "\n";
echo " Target Year Balances Made  : " . ($isDryRun ? ($stats['records_created'] . " (simulated)") : $stats['records_created']) . "\n";
echo " Balances Updated (--force) : " . $stats['records_updated'] . "\n";
echo " Balances Skipped (Existed) : " . $stats['records_skipped'] . "\n";
echo " Total EL Days Carried Over : " . number_format($stats['total_el_carried'], 1) . " days\n";
echo " Total Unused Days Lapsed   : " . number_format($stats['total_days_lapsed'], 1) . " days\n";
echo " Status                     : " . ($isDryRun ? "DRY RUN COMPLETED (No changes written)" : "SUCCESS (Database updated successfully)") . "\n";
echo "===============================================================================\n";

exit(0);
