<?php
/**
 * Performance Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Performance.php';
require_once __DIR__ . '/../Models/Employee.php';

class PerformanceController {
    public function goals(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('performance/goals');
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'add_goal') {
                Performance::addGoal($empId, $_POST);
                flash('success', 'New performance goal added!');
            } elseif ($action === 'update_progress') {
                $goalId = (int)$_POST['goal_id'];
                $progress = (int)$_POST['progress'];
                $status = ($progress >= 100) ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started');
                Performance::updateGoalProgress($goalId, $progress, $status);
                flash('success', 'Goal progress updated!');
            }
            redirect('performance/goals');
        }

        $goals = Performance::getGoals($empId);
        require_once BASE_PATH . '/views/performance/goals.php';
    }

    public function reviews(): void {
        Auth::requireLogin();
        $empId = Auth::employeeId();
        $role = Auth::role();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('performance/reviews');
            }

            Performance::createReview($_POST);
            flash('success', 'Performance appraisal submitted!');
            redirect('performance/reviews');
        }

        if ($role === 'super_admin' || $role === 'hr_admin') {
            $reviews = Performance::getReviews();
        } elseif ($role === 'manager') {
            $reviews = Performance::getReviews(null, $empId);
        } else {
            $reviews = Performance::getReviews($empId);
        }

        $employees = Employee::getAll(['status' => 'active']);
        require_once BASE_PATH . '/views/performance/reviews.php';
    }
}
