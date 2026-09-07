<?php
/**
 * Payroll Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Payroll.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Models/Notification.php';

class PayrollController {
    public function index(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $status = $_GET['status'] ?? null;

        $payrolls = Payroll::getPayrolls([
            'month' => $month,
            'year' => $year,
            'status' => $status
        ]);

        $totalGross = array_sum(array_column($payrolls, 'gross_earnings'));
        $totalNet = array_sum(array_column($payrolls, 'net_salary'));
        $totalDeductions = array_sum(array_column($payrolls, 'total_deductions'));

        require_once BASE_PATH . '/views/payroll/index.php';
    }

    public function process(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('payroll');
            }

            $month = (int)($_POST['month'] ?? date('n'));
            $year = (int)($_POST['year'] ?? date('Y'));

            $result = Payroll::processBatch($month, $year);
            if ($result['processed'] > 0) {
                $monthName = date('F', mktime(0, 0, 0, $month, 10));
                Notification::sendToRole(['employee', 'manager', 'hr_admin'], 'payroll', 'Payslip Ready', "Your payslip for {$monthName} {$year} is now available.", 'payroll/my-payslips', 'fa-file-invoice-dollar', '#059669');
            }
            flash('success', "Processed payroll for {$result['processed']} employees successfully!");
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    flash('warning', $err);
                }
            }
            redirect("payroll?month={$month}&year={$year}");
        }

        redirect('payroll');
    }

    public function salarySetup(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);

        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $employee = Employee::findById($employeeId);

        if (!$employee) {
            flash('danger', 'Employee not found!');
            redirect('employees');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect("payroll/salary-setup?employee_id={$employeeId}");
            }

            Payroll::saveSalaryStructure($employeeId, $_POST);
            flash('success', "Salary structure updated for {$employee['emp_code']}!");
            redirect("employees/view?id={$employeeId}");
        }

        $salary = Payroll::getSalaryStructure($employeeId);
        require_once BASE_PATH . '/views/payroll/salary_setup.php';
    }

    public function payslip(): void {
        Auth::requireLogin();
        $payrollId = (int)($_GET['id'] ?? 0);
        $payslip = Payroll::getPayslipById($payrollId);

        if (!$payslip) {
            flash('danger', 'Payslip not found!');
            redirect('payroll');
        }

        // Authorization: HR/Admin or the owner of the payslip
        $currentUser = Auth::user();
        if (!Auth::isHR() && $currentUser['employee_id'] !== $payslip['employee_id']) {
            flash('danger', 'Unauthorized access to this payslip!');
            redirect('dashboard');
        }

        $netInWords = number_to_words_inr($payslip['net_salary']);
        require_once BASE_PATH . '/views/payroll/payslip.php';
    }

    public function myPayslips(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();

        $payrolls = Payroll::getPayrolls(['employee_id' => $empId]);
        require_once BASE_PATH . '/views/payroll/my_payslips.php';
    }

    public function markPaid(): void {
        Auth::requireRole(['super_admin', 'hr_admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf()) {
            $payrollId = (int)($_POST['payroll_id'] ?? 0);
            $method = $_POST['payment_method'] ?? 'bank_transfer';
            $date = $_POST['payment_date'] ?? date('Y-m-d');

            Payroll::markAsPaid($payrollId, $date, $method);
            flash('success', 'Payroll marked as Disbursed/Paid!');
        }
        redirect('payroll');
    }
}
