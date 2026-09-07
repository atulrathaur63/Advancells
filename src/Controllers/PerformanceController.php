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
                if (Performance::updateGoalProgress($goalId, $progress, $status, $empId)) {
                    flash('success', 'Goal progress updated!');
                } else {
                    flash('danger', 'Unauthorized or invalid goal identifier.');
                }
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
        $isHR = Auth::isHR();
        $isManager = ($role === 'manager');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('performance/reviews');
            }

            $action = $_POST['action'] ?? 'create';

            if ($action === 'grade') {
                // Grading / evaluating an existing appraisal review (Managers / HR only)
                if (!$isHR && !$isManager) {
                    flash('danger', 'Unauthorized access: You do not have permission to grade appraisals.');
                    redirect('performance/reviews');
                }

                $reviewId = (int)($_POST['review_id'] ?? 0);
                $review = Performance::getReviewById($reviewId);
                if (!$review) {
                    flash('danger', 'Appraisal review record not found.');
                    redirect('performance/reviews');
                }

                // IDOR check: Is the authenticated manager authorized for this employee or review?
                if (!$isHR) {
                    $targetEmpId = (int)$review['employee_id'];
                    $isReviewer = ((int)$review['reviewer_id'] === $empId);
                    $isSubordinate = Employee::isSubordinateOf($targetEmpId, $empId);

                    if (!$isReviewer && !$isSubordinate) {
                        flash('danger', 'Unauthorized: You can only evaluate appraisals for your team members.');
                        redirect('performance/reviews');
                    }
                }

                $mgrRating = isset($_POST['manager_rating']) && $_POST['manager_rating'] !== '' ? max(1, min(5, (int)$_POST['manager_rating'])) : null;
                $mgrComments = trim($_POST['manager_comments'] ?? '');
                $finalRating = isset($_POST['final_rating']) && $_POST['final_rating'] !== '' ? max(1.0, min(5.0, (float)$_POST['final_rating'])) : ($mgrRating ? (float)$mgrRating : null);

                Performance::updateReview($reviewId, [
                    'manager_rating' => $mgrRating,
                    'manager_comments' => $mgrComments,
                    'final_rating' => $finalRating,
                    'status' => 'reviewed'
                ]);

                flash('success', 'Appraisal evaluation saved successfully!');
                redirect('performance/reviews');
            }

            // Default action: create review
            if (!$isHR && !$isManager) {
                // Regular employees can ONLY submit self-appraisal for themselves
                $targetEmpId = $empId;
                $reviewerId = !empty($_POST['reviewer_id']) ? (int)$_POST['reviewer_id'] : 0;
                $selfRating = isset($_POST['self_rating']) && $_POST['self_rating'] !== '' ? max(1, min(5, (int)$_POST['self_rating'])) : null;
                $selfComments = trim($_POST['self_comments'] ?? '');

                Performance::createReview([
                    'employee_id' => $targetEmpId,
                    'reviewer_id' => $reviewerId,
                    'review_period' => trim($_POST['review_period'] ?? ('Q3 ' . date('Y'))),
                    'self_rating' => $selfRating,
                    'self_comments' => $selfComments,
                    'manager_rating' => null, // Privilege escalation protection: employee cannot set manager rating
                    'manager_comments' => null,
                    'final_rating' => null,
                    'status' => 'submitted'
                ]);

                flash('success', 'Self-appraisal submitted successfully!');
                redirect('performance/reviews');
            } else {
                // Manager or HR creating a review
                $targetEmpId = (int)($_POST['employee_id'] ?? $empId);

                // If Manager (not HR) and target is another employee, verify reporting hierarchy
                if ($isManager && !$isHR && $targetEmpId !== $empId) {
                    if (!Employee::isSubordinateOf($targetEmpId, $empId)) {
                        flash('danger', 'Unauthorized: You can only conduct reviews for employees reporting to you.');
                        redirect('performance/reviews');
                    }
                }

                if ($targetEmpId === $empId) {
                    // Manager submitting self-appraisal
                    $reviewerId = !empty($_POST['reviewer_id']) ? (int)$_POST['reviewer_id'] : 0;
                    $selfRating = isset($_POST['self_rating']) && $_POST['self_rating'] !== '' ? max(1, min(5, (int)$_POST['self_rating'])) : null;
                    $selfComments = trim($_POST['self_comments'] ?? '');

                    Performance::createReview([
                        'employee_id' => $targetEmpId,
                        'reviewer_id' => $reviewerId,
                        'review_period' => trim($_POST['review_period'] ?? ('Q3 ' . date('Y'))),
                        'self_rating' => $selfRating,
                        'self_comments' => $selfComments,
                        'manager_rating' => null,
                        'manager_comments' => null,
                        'final_rating' => null,
                        'status' => 'submitted'
                    ]);
                    flash('success', 'Self-appraisal submitted successfully!');
                } else {
                    // Manager or HR conducting appraisal for employee
                    $reviewerId = $empId; // Authenticated manager is reviewer
                    $mgrRating = isset($_POST['manager_rating']) && $_POST['manager_rating'] !== '' ? max(1, min(5, (int)$_POST['manager_rating'])) : null;
                    $mgrComments = trim($_POST['manager_comments'] ?? '');
                    $finalRating = isset($_POST['final_rating']) && $_POST['final_rating'] !== '' ? (float)$_POST['final_rating'] : ($mgrRating ? (float)$mgrRating : null);

                    Performance::createReview([
                        'employee_id' => $targetEmpId,
                        'reviewer_id' => $reviewerId,
                        'review_period' => trim($_POST['review_period'] ?? ('Q3 ' . date('Y'))),
                        'self_rating' => null,
                        'self_comments' => '',
                        'manager_rating' => $mgrRating,
                        'manager_comments' => $mgrComments,
                        'final_rating' => $finalRating,
                        'status' => 'reviewed'
                    ]);
                    flash('success', 'Performance appraisal submitted successfully!');
                }
                redirect('performance/reviews');
            }
        }

        // GET Request: Load scoped reviews and employees
        if ($isHR) {
            $reviews = Performance::getReviews();
            $employees = Employee::getAll(['status' => 'active']);
        } elseif ($isManager) {
            $subordinateIds = Employee::getSubordinateIds($empId, true);
            $reviews = Performance::getReviews(null, $empId, $subordinateIds);
            $subordinateOnlyIds = Employee::getSubordinateIds($empId, false);
            $employees = !empty($subordinateOnlyIds) ? Employee::getAll(['employee_ids' => $subordinateOnlyIds, 'status' => 'active']) : [];
        } else {
            $reviews = Performance::getReviews($empId);
            $employees = [];
        }

        require_once BASE_PATH . '/views/performance/reviews.php';
    }
}
