<?php
/**
 * Database Migration & Seeder Script
 */

require_once __DIR__ . '/config.php';

function runMigration($cliMode = false): void {
    try {
        // Connect to server
        $rootPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Connect to the specific database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Disable foreign key checks for clean recreation if needed
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 1. Departments Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `code` VARCHAR(20) NOT NULL UNIQUE,
            `description` TEXT,
            `head_id` INT NULL,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 2. Designations Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `designations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT NOT NULL,
            `title` VARCHAR(100) NOT NULL,
            `grade` VARCHAR(20) DEFAULT 'L1',
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`department_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 3. Users Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('super_admin', 'hr_admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
            `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            `avatar` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 4. Employees Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `employees` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL UNIQUE,
            `emp_code` VARCHAR(30) NOT NULL UNIQUE,
            `first_name` VARCHAR(50) NOT NULL,
            `last_name` VARCHAR(50) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `phone` VARCHAR(20) NOT NULL,
            `gender` ENUM('male', 'female', 'other') DEFAULT 'male',
            `dob` DATE NULL,
            `blood_group` VARCHAR(10) NULL,
            `marital_status` ENUM('single', 'married', 'divorced') DEFAULT 'single',
            `address` TEXT NULL,
            `emergency_contact_name` VARCHAR(100) NULL,
            `emergency_contact_phone` VARCHAR(20) NULL,
            `department_id` INT NULL,
            `designation_id` INT NULL,
            `manager_id` INT NULL,
            `date_of_joining` DATE NOT NULL,
            `date_of_exit` DATE NULL,
            `employment_type` ENUM('full_time', 'probation', 'contract', 'intern') DEFAULT 'full_time',
            `status` ENUM('active', 'on_leave', 'resigned', 'terminated') DEFAULT 'active',
            `bank_name` VARCHAR(100) NULL,
            `account_number` VARCHAR(50) NULL,
            `ifsc_code` VARCHAR(20) NULL,
            `pan_number` VARCHAR(20) NULL,
            `uan_number` VARCHAR(30) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`department_id`),
            INDEX (`designation_id`),
            INDEX (`manager_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 5. Attendance Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `date` DATE NOT NULL,
            `punch_in` TIME NULL,
            `punch_out` TIME NULL,
            `punch_in_ip` VARCHAR(45) NULL,
            `punch_out_ip` VARCHAR(45) NULL,
            `total_hours` DECIMAL(5,2) DEFAULT 0.00,
            `status` ENUM('present', 'absent', 'late', 'half_day', 'holiday', 'leave') DEFAULT 'present',
            `is_regularized` TINYINT(1) DEFAULT 0,
            `notes` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `emp_date_unique` (`employee_id`, `date`),
            INDEX (`date`),
            INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 6. Attendance Regularizations Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance_regularizations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `attendance_id` INT NULL,
            `employee_id` INT NOT NULL,
            `date` DATE NOT NULL,
            `requested_punch_in` TIME NOT NULL,
            `requested_punch_out` TIME NOT NULL,
            `reason` TEXT NOT NULL,
            `manager_id` INT NULL,
            `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            `admin_remarks` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`employee_id`),
            INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 7. Leave Types Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `leave_types` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL,
            `code` VARCHAR(20) NOT NULL UNIQUE,
            `days_per_year` INT NOT NULL DEFAULT 12,
            `is_paid` TINYINT(1) DEFAULT 1,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 8. Leave Balances Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `leave_balances` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `leave_type_id` INT NOT NULL,
            `year` INT NOT NULL,
            `total_allocated` DECIMAL(5,1) NOT NULL DEFAULT 0.0,
            `used` DECIMAL(5,1) NOT NULL DEFAULT 0.0,
            `pending` DECIMAL(5,1) NOT NULL DEFAULT 0.0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `emp_leave_year` (`employee_id`, `leave_type_id`, `year`),
            INDEX (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 9. Leave Requests Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `leave_requests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `leave_type_id` INT NOT NULL,
            `from_date` DATE NOT NULL,
            `to_date` DATE NOT NULL,
            `total_days` DECIMAL(5,1) NOT NULL DEFAULT 1.0,
            `is_half_day` TINYINT(1) DEFAULT 0,
            `half_day_type` ENUM('first_half', 'second_half') NULL,
            `reason` TEXT NOT NULL,
            `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
            `manager_id` INT NULL,
            `approved_by` INT NULL,
            `approver_remarks` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`employee_id`),
            INDEX (`status`),
            INDEX (`from_date`),
            INDEX (`to_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 10. Salary Structures Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `salary_structures` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL UNIQUE,
            `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `hra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `special_allowance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `conveyance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `medical_allowance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `other_allowances` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `pf_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `esi_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `professional_tax` DECIMAL(10,2) NOT NULL DEFAULT 200.00,
            `tds` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `gross_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `net_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `effective_date` DATE NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 11. Payrolls Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `payrolls` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `month` TINYINT NOT NULL,
            `year` SMALLINT NOT NULL,
            `basic_salary` DECIMAL(10,2) NOT NULL,
            `hra` DECIMAL(10,2) NOT NULL,
            `allowances` DECIMAL(10,2) NOT NULL,
            `overtime_pay` DECIMAL(10,2) DEFAULT 0.00,
            `bonus` DECIMAL(10,2) DEFAULT 0.00,
            `gross_earnings` DECIMAL(10,2) NOT NULL,
            `pf_deduction` DECIMAL(10,2) NOT NULL,
            `esi_deduction` DECIMAL(10,2) NOT NULL,
            `tax_deduction` DECIMAL(10,2) NOT NULL,
            `lop_deduction` DECIMAL(10,2) DEFAULT 0.00,
            `other_deductions` DECIMAL(10,2) DEFAULT 0.00,
            `total_deductions` DECIMAL(10,2) NOT NULL,
            `net_salary` DECIMAL(10,2) NOT NULL,
            `working_days` TINYINT DEFAULT 30,
            `present_days` DECIMAL(4,1) DEFAULT 30.0,
            `lop_days` DECIMAL(4,1) DEFAULT 0.0,
            `payment_status` ENUM('draft', 'generated', 'paid') DEFAULT 'draft',
            `payment_date` DATE NULL,
            `payment_method` ENUM('bank_transfer', 'cheque', 'cash') DEFAULT 'bank_transfer',
            `payslip_number` VARCHAR(50) NOT NULL UNIQUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `emp_month_year` (`employee_id`, `month`, `year`),
            INDEX (`month`, `year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 12. Holidays Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `holidays` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(100) NOT NULL,
            `holiday_date` DATE NOT NULL,
            `type` ENUM('mandatory', 'optional') DEFAULT 'mandatory',
            `description` TEXT,
            `year` SMALLINT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 13. Announcements Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `announcements` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            `target_role` VARCHAR(50) DEFAULT 'all',
            `posted_by` INT NOT NULL,
            `expires_at` DATE NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 14. Performance Goals Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `performance_goals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `start_date` DATE NOT NULL,
            `target_date` DATE NOT NULL,
            `progress` TINYINT UNSIGNED DEFAULT 0,
            `status` ENUM('not_started', 'in_progress', 'completed', 'deferred') DEFAULT 'not_started',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 15. Performance Reviews Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `performance_reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `reviewer_id` INT NOT NULL,
            `review_period` VARCHAR(50) NOT NULL,
            `self_rating` TINYINT NULL,
            `self_comments` TEXT NULL,
            `manager_rating` TINYINT NULL,
            `manager_comments` TEXT NULL,
            `hr_rating` TINYINT NULL,
            `final_rating` DECIMAL(3,1) NULL,
            `status` ENUM('draft', 'submitted', 'reviewed', 'completed') DEFAULT 'draft',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`employee_id`),
            INDEX (`reviewer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 16. Resignations Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `resignations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL UNIQUE,
            `resignation_date` DATE NOT NULL,
            `reason` TEXT NOT NULL,
            `desired_last_working_day` DATE NOT NULL,
            `approved_last_working_day` DATE NULL,
            `status` ENUM('submitted', 'manager_approved', 'hr_approved', 'rejected', 'completed') DEFAULT 'submitted',
            `exit_interview_notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 17. Activity Logs Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `action` VARCHAR(100) NOT NULL,
            `module` VARCHAR(50) NOT NULL,
            `details` TEXT NULL,
            `ip_address` VARCHAR(45) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`user_id`),
            INDEX (`module`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 18. Employee Documents Table (Document Locker)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `employee_documents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            `document_type` ENUM(
                'aadhaar_card', 
                'pan_card', 
                'educational_degree', 
                'offer_letter', 
                'appointment_letter', 
                'signed_nda', 
                'previous_relieving', 
                'bank_passbook', 
                'passport', 
                'other'
            ) NOT NULL DEFAULT 'other',
            `title` VARCHAR(150) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `file_size` INT NOT NULL,
            `file_ext` VARCHAR(10) NOT NULL,
            `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            `rejection_reason` TEXT NULL,
            `uploaded_by` INT NOT NULL,
            `verified_by` INT NULL,
            `verified_at` TIMESTAMP NULL,
            `expiry_date` DATE NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`employee_id`),
            INDEX (`document_type`),
            INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Seed Default Data
        seedData($pdo);

        if ($cliMode) {
            echo "Migration & Seeding completed successfully!\n";
        }
    } catch (PDOException $e) {
        if ($cliMode) {
            echo "Migration error: " . $e->getMessage() . "\n";
        } else {
            throw $e;
        }
    }
}

function seedData(PDO $pdo): void {
    // 1. Seed Departments
    $deptStmt = $pdo->prepare("INSERT IGNORE INTO `departments` (`id`, `name`, `code`, `description`, `status`) VALUES (?, ?, ?, ?, 'active')");
    $departments = [
        [1, 'Executive Management', 'EXEC', 'Executive and strategic leadership'],
        [2, 'Human Resources', 'HR', 'HR management, talent, and payroll'],
        [3, 'Clinical Research & Therapy', 'CRT', 'Stem cell isolation, processing, and clinical trials'],
        [4, 'Quality Control & Lab Ops', 'QC', 'Lab quality assurance and compliance'],
        [5, 'IT & Software Systems', 'IT', 'Internal software, infrastructure, and web portal'],
        [6, 'Sales & Business Development', 'SBD', 'Domestic and international healthcare partnerships'],
        [7, 'Finance & Accounting', 'FIN', 'Corporate finance, billing, and accounts']
    ];
    foreach ($departments as $d) {
        $deptStmt->execute($d);
    }

    // 2. Seed Designations
    $desigStmt = $pdo->prepare("INSERT IGNORE INTO `designations` (`id`, `department_id`, `title`, `grade`, `description`) VALUES (?, ?, ?, ?, ?)");
    $designations = [
        [1, 1, 'Chief Executive Officer (CEO)', 'L6', 'Executive head'],
        [2, 2, 'HR Manager', 'L4', 'HR Department Head'],
        [3, 2, 'HR Executive', 'L2', 'HR operations and onboarding'],
        [4, 3, 'Principal Research Scientist', 'L5', 'Clinical research head'],
        [5, 3, 'Stem Cell Specialist', 'L3', 'Senior researcher and therapist'],
        [6, 3, 'Lab Research Associate', 'L2', 'Laboratory research specialist'],
        [7, 4, 'Quality Assurance Manager', 'L4', 'Quality and compliance lead'],
        [8, 5, 'Senior Software Engineer', 'L3', 'Full stack developer & sysadmin'],
        [9, 5, 'IT Support Engineer', 'L2', 'Network and desktop support'],
        [10, 6, 'Business Development Manager', 'L4', 'Client relations and partnerships'],
        [11, 7, 'Senior Finance Accountant', 'L3', 'Ledger and tax handling']
    ];
    foreach ($designations as $des) {
        $desigStmt->execute($des);
    }

    // 3. Seed Users
    $passwordAdmin = password_hash('Admin@123', PASSWORD_BCRYPT);
    $passwordHr = password_hash('Hr@123', PASSWORD_BCRYPT);
    $passwordManager = password_hash('Manager@123', PASSWORD_BCRYPT);
    $passwordEmp = password_hash('Emp@123', PASSWORD_BCRYPT);

    $userStmt = $pdo->prepare("INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES (?, ?, ?, ?, ?, 'active')");
    $users = [
        [1, 'Dr. Vipul Jain', 'admin@advancells.com', $passwordAdmin, 'super_admin'],
        [2, 'Priya Sharma', 'hr@advancells.com', $passwordHr, 'hr_admin'],
        [3, 'Rajesh Verma', 'manager@advancells.com', $passwordManager, 'manager'],
        [4, 'Ananya Singh', 'employee@advancells.com', $passwordEmp, 'employee'],
        [5, 'Amit Patel', 'tech@advancells.com', $passwordEmp, 'employee'],
        [6, 'Sneha Kapoor', 'qc@advancells.com', $passwordEmp, 'employee']
    ];
    foreach ($users as $u) {
        $userStmt->execute($u);
    }

    // 4. Seed Employees
    $empStmt = $pdo->prepare("INSERT IGNORE INTO `employees` (
        `id`, `user_id`, `emp_code`, `first_name`, `last_name`, `email`, `phone`, `gender`, `dob`, `blood_group`,
        `marital_status`, `address`, `emergency_contact_name`, `emergency_contact_phone`,
        `department_id`, `designation_id`, `manager_id`, `date_of_joining`, `employment_type`, `status`,
        `bank_name`, `account_number`, `ifsc_code`, `pan_number`, `uan_number`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $employees = [
        [
            1, 1, 'ADV-001', 'Vipul', 'Jain', 'admin@advancells.com', '+91 98100 11223', 'male', '1980-04-12', 'O+',
            'married', 'B-14 Sector 15, Noida, UP', 'Mrs. Jain', '+91 98100 99887',
            1, 1, NULL, '2015-01-10', 'full_time', 'active',
            'HDFC Bank', '50100234567891', 'HDFC0000123', 'ABCDE1234F', '100902345678'
        ],
        [
            2, 2, 'ADV-002', 'Priya', 'Sharma', 'hr@advancells.com', '+91 98765 43210', 'female', '1989-08-23', 'B+',
            'married', 'A-42 Indirapuram, Ghaziabad, UP', 'Rohan Sharma', '+91 98765 00000',
            2, 2, 1, '2018-05-15', 'full_time', 'active',
            'ICICI Bank', '002101567890', 'ICIC0000021', 'FGHIJ5678K', '100903456789'
        ],
        [
            3, 3, 'ADV-003', 'Rajesh', 'Verma', 'manager@advancells.com', '+91 98234 56789', 'male', '1984-11-17', 'A+',
            'married', 'Flat 304, Green Heights, Greater Noida', 'Sunita Verma', '+91 98234 11111',
            3, 4, 1, '2017-03-01', 'full_time', 'active',
            'State Bank of India', '30245678901', 'SBIN0004523', 'KLMNO9012P', '100904567890'
        ],
        [
            4, 4, 'ADV-004', 'Ananya', 'Singh', 'employee@advancells.com', '+91 97123 45678', 'female', '1995-02-14', 'AB+',
            'single', 'Tower C, Express View Apts, Sector 93, Noida', 'R. K. Singh', '+91 97123 22222',
            3, 5, 3, '2021-08-16', 'full_time', 'active',
            'Axis Bank', '918010045678912', 'UTIB0000543', 'PQRST3456U', '100905678901'
        ],
        [
            5, 5, 'ADV-005', 'Amit', 'Patel', 'tech@advancells.com', '+91 99988 77665', 'male', '1993-07-30', 'O+',
            'single', 'E-88 Sector 50, Noida, UP', 'K. Patel', '+91 99988 33333',
            5, 8, 1, '2022-01-10', 'full_time', 'active',
            'Kotak Mahindra Bank', '4312567890', 'KKBK0000234', 'UVWXY7890Z', '100906789012'
        ],
        [
            6, 6, 'ADV-006', 'Sneha', 'Kapoor', 'qc@advancells.com', '+91 98456 12345', 'female', '1996-09-05', 'A-',
            'single', 'Pocket D, Mayur Vihar Phase 1, Delhi', 'D. Kapoor', '+91 98456 44444',
            4, 7, 3, '2023-04-01', 'probation', 'active',
            'Punjab National Bank', '1234000100234567', 'PUNB0123400', 'BCDEF2345G', '100907890123'
        ]
    ];
    foreach ($employees as $emp) {
        $empStmt->execute($emp);
    }


    // 5. Seed Leave Types
    $ltStmt = $pdo->prepare("INSERT IGNORE INTO `leave_types` (`id`, `name`, `code`, `days_per_year`, `is_paid`, `description`) VALUES (?, ?, ?, ?, ?, ?)");
    $leaveTypes = [
        [1, 'Casual Leave', 'CL', 12, 1, 'For personal tasks and short emergencies'],
        [2, 'Sick Leave', 'SL', 10, 1, 'For medical illness or doctor visits'],
        [3, 'Earned / Privilege Leave', 'EL', 15, 1, 'Planned vacation or extended personal time off'],
        [4, 'Maternity Leave', 'ML', 90, 1, 'Applicable for eligible female employees'],
        [5, 'Loss of Pay / Unpaid', 'LOP', 0, 0, 'Unpaid absence beyond accrued leave balance']
    ];
    foreach ($leaveTypes as $lt) {
        $ltStmt->execute($lt);
    }

    // 6. Seed Leave Balances for 2026
    $currentYear = (int)date('Y');
    $lbStmt = $pdo->prepare("INSERT IGNORE INTO `leave_balances` (`employee_id`, `leave_type_id`, `year`, `total_allocated`, `used`, `pending`) VALUES (?, ?, ?, ?, ?, ?)");
    for ($eId = 1; $eId <= 6; $eId++) {
        $lbStmt->execute([$eId, 1, $currentYear, 12.0, 2.0, 0.0]); // CL: 12, 2 used
        $lbStmt->execute([$eId, 2, $currentYear, 10.0, 1.0, 0.0]); // SL: 10, 1 used
        $lbStmt->execute([$eId, 3, $currentYear, 15.0, 3.0, 0.0]); // EL: 15, 3 used
        $lbStmt->execute([$eId, 4, $currentYear, 90.0, 0.0, 0.0]); // ML
        $lbStmt->execute([$eId, 5, $currentYear, 0.0, 0.0, 0.0]);  // LOP
    }

    // 7. Seed Holidays for Current Year
    $hStmt = $pdo->prepare("INSERT IGNORE INTO `holidays` (`title`, `holiday_date`, `type`, `description`, `year`) VALUES (?, ?, ?, ?, ?)");
    $holidays = [
        ['Republic Day', $currentYear . '-01-26', 'mandatory', 'National Holiday', $currentYear],
        ['Maha Shivratri', $currentYear . '-03-08', 'mandatory', 'Gazetted Holiday', $currentYear],
        ['Holi', $currentYear . '-03-25', 'mandatory', 'Festival of Colours', $currentYear],
        ['Id-ul-Fitr', $currentYear . '-04-11', 'mandatory', 'Islamic Holiday', $currentYear],
        ['Independence Day', $currentYear . '-08-15', 'mandatory', 'National Holiday', $currentYear],
        ['Gandhi Jayanti', $currentYear . '-10-02', 'mandatory', 'National Holiday', $currentYear],
        ['Dussehra', $currentYear . '-10-12', 'mandatory', 'Vijayadashami', $currentYear],
        ['Diwali', $currentYear . '-11-01', 'mandatory', 'Festival of Lights', $currentYear],
        ['Guru Nanak Jayanti', $currentYear . '-11-15', 'mandatory', 'Gazetted Holiday', $currentYear],
        ['Christmas', $currentYear . '-12-25', 'mandatory', 'Gazetted Holiday', $currentYear]
    ];
    foreach ($holidays as $h) {
        $hStmt->execute($h);
    }

    // 8. Seed Salary Structures
    $salStmt = $pdo->prepare("INSERT IGNORE INTO `salary_structures` (
        `employee_id`, `basic_salary`, `hra`, `special_allowance`, `conveyance`, `medical_allowance`,
        `other_allowances`, `pf_deduction`, `esi_deduction`, `professional_tax`, `tds`,
        `gross_salary`, `net_salary`, `effective_date`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $salaries = [
        // Emp 1 (Dr Vipul) CTC ~ 1,80,000 / mo
        [1, 90000, 36000, 30000, 8000, 6000, 10000, 10800, 0, 200, 15000, 180000, 154000, '2024-01-01'],
        // Emp 2 (Priya HR) CTC ~ 85,000 / mo
        [2, 42500, 17000, 15000, 4500, 3000, 3000, 5100, 0, 200, 5000, 85000, 74700, '2024-01-01'],
        // Emp 3 (Rajesh Manager) CTC ~ 1,10,000 / mo
        [3, 55000, 22000, 20000, 6000, 4000, 3000, 6600, 0, 200, 8000, 110000, 95200, '2024-01-01'],
        // Emp 4 (Ananya Stem Cell Specialist) CTC ~ 65,000 / mo
        [4, 32500, 13000, 11500, 4000, 2000, 2000, 3900, 0, 200, 3000, 65000, 57900, '2024-01-01'],
        // Emp 5 (Amit IT Tech) CTC ~ 55,000 / mo
        [5, 27500, 11000, 9500, 3500, 2000, 1500, 3300, 0, 200, 2000, 55000, 49500, '2024-01-01'],
        // Emp 6 (Sneha QC) CTC ~ 45,000 / mo
        [6, 22500, 9000, 7500, 3000, 1500, 1500, 2700, 337.5, 200, 1000, 45000, 40762.5, '2024-01-01']
    ];
    foreach ($salaries as $sal) {
        $salStmt->execute($sal);
    }

    // 9. Seed Sample Attendance for Today & Recent Days
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $dayBefore = date('Y-m-d', strtotime('-2 days'));

    $attStmt = $pdo->prepare("INSERT IGNORE INTO `attendance` (
        `employee_id`, `date`, `punch_in`, `punch_out`, `punch_in_ip`, `punch_out_ip`, `total_hours`, `status`, `notes`
    ) VALUES (?, ?, ?, ?, '127.0.0.1', '127.0.0.1', ?, ?, ?)");

    // Today's attendance
    $attStmt->execute([1, $today, '09:05:00', NULL, 0.00, 'present', 'Checked in via web']);
    $attStmt->execute([2, $today, '08:58:00', NULL, 0.00, 'present', 'Checked in on time']);
    $attStmt->execute([3, $today, '09:12:00', NULL, 0.00, 'present', 'Lab round']);
    $attStmt->execute([4, $today, '09:35:00', NULL, 0.00, 'late', 'Traffic delay']);
    $attStmt->execute([5, $today, '08:50:00', NULL, 0.00, 'present', 'Server monitoring']);

    // Yesterday's attendance
    for ($i = 1; $i <= 5; $i++) {
        $attStmt->execute([$i, $yesterday, '09:00:00', '18:05:00', 9.08, 'present', 'Full day worked']);
    }
    // Emp 6 was on leave yesterday
    $attStmt->execute([6, $yesterday, NULL, NULL, 0.00, 'leave', 'Approved Casual Leave']);

    // Day before attendance
    for ($i = 1; $i <= 6; $i++) {
        $attStmt->execute([$i, $dayBefore, '09:02:00', '18:10:00', 9.13, 'present', 'Full day worked']);
    }

    // 10. Seed Announcements
    $annStmt = $pdo->prepare("INSERT IGNORE INTO `announcements` (`title`, `content`, `priority`, `target_role`, `posted_by`, `created_at`) VALUES (?, ?, ?, ?, ?, ?)");
    $announcements = [
        [
            'Welcome to Advancells HRMS Portal!',
            'We are proud to introduce our centralized HRMS portal for seamless attendance tracking, leave requests, payroll access, and employee self-service. Please explore your dashboard and ensure your bank and profile details are up to date.',
            'high',
            'all',
            1,
            date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'Stem Cell Research Quarterly Review Scheduled',
            'The Q3 clinical trial progress and lab review meeting will be held on Friday at 3:00 PM in Conference Room A. All research and QC teams are requested to bring their experiment logs.',
            'normal',
            'all',
            3,
            date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'Annual Health Check-Up Camp - Next Week',
            'Advancells will be hosting a comprehensive health checkup camp for all staff members next Tuesday from 10:00 AM to 4:00 PM. Registration is free and mandatory.',
            'normal',
            'all',
            2,
            date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
    foreach ($announcements as $ann) {
        $annStmt->execute($ann);
    }

    // 11. Seed Performance Goals
    $goalStmt = $pdo->prepare("INSERT IGNORE INTO `performance_goals` (`employee_id`, `title`, `description`, `start_date`, `target_date`, `progress`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $goals = [
        [4, 'Optimize Mesenchymal Stem Cell Isolation Protocol', 'Increase cell yield by 18% with reduced reagent consumption in lab tests.', date('Y-01-01'), date('Y-12-31'), 75, 'in_progress'],
        [4, 'Complete ISO-13485 Lab Compliance Training', 'Undergo quality management certification for medical devices & biologics.', date('Y-02-01'), date('Y-06-30'), 100, 'completed'],
        [5, 'Upgrade Internal HRMS & Clinical Trial Portal', 'Deploy modern responsive web interface with role-based access control.', date('Y-01-15'), date('Y-09-30'), 85, 'in_progress']
    ];
    foreach ($goals as $g) {
        $goalStmt->execute($g);
    }

    // 12. Seed Sample Leave Request
    $lrStmt = $pdo->prepare("INSERT IGNORE INTO `leave_requests` (
        `employee_id`, `leave_type_id`, `from_date`, `to_date`, `total_days`, `is_half_day`, `reason`, `status`, `manager_id`
    ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)");
    $lrStmt->execute([
        4, 1, date('Y-m-d', strtotime('+3 days')), date('Y-m-d', strtotime('+4 days')), 2.0,
        'Attending family function in hometown.', 'pending', 3
    ]);
    $lrStmt->execute([
        5, 2, date('Y-m-d', strtotime('-4 days')), date('Y-m-d', strtotime('-4 days')), 1.0,
        'Severe viral fever and doctor consultation.', 'approved', 1
    ]);

    // 13. Seed Sample Regularization Request
    $regStmt = $pdo->prepare("INSERT IGNORE INTO `attendance_regularizations` (
        `employee_id`, `date`, `requested_punch_in`, `requested_punch_out`, `reason`, `manager_id`, `status`
    ) VALUES (?, ?, '09:00:00', '18:15:00', ?, 3, 'pending')");
    $regStmt->execute([
        4, date('Y-m-d', strtotime('-3 days')), 'Card reader was temporarily offline during morning entry.'
    ]);
}

// If invoked from CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Running Advancells HRMS Migration...\n";
    runMigration(true);
}
