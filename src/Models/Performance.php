<?php
/**
 * Performance & Goals Management Model
 */

require_once __DIR__ . '/../Database.php';

class Performance {
    public static function getGoals(int $employeeId): array {
        return Database::fetchAll("SELECT * FROM performance_goals WHERE employee_id = ? ORDER BY target_date ASC", [$employeeId]);
    }

    public static function addGoal(int $employeeId, array $data): int {
        return Database::insert('performance_goals', [
            'employee_id' => $employeeId,
            'title' => trim($data['title']),
            'description' => trim($data['description'] ?? ''),
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'target_date' => $data['target_date'],
            'progress' => (int)($data['progress'] ?? 0),
            'status' => $data['status'] ?? 'not_started'
        ]);
    }

    public static function updateGoalProgress(int $goalId, int $progress, string $status): bool {
        return Database::update('performance_goals', [
            'progress' => min(100, max(0, $progress)),
            'status' => $status
        ], "id = ?", [$goalId]) > 0;
    }

    public static function getReviews(?int $employeeId = null, ?int $reviewerId = null): array {
        $sql = "SELECT pr.*, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name, des.title AS designation_title,
                       CONCAT(r.first_name, ' ', r.last_name) AS reviewer_name
                FROM performance_reviews pr
                JOIN employees e ON pr.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN employees r ON pr.reviewer_id = r.id
                WHERE 1=1";
        $params = [];

        if ($employeeId !== null) {
            $sql .= " AND pr.employee_id = ?";
            $params[] = $employeeId;
        } elseif ($reviewerId !== null) {
            $sql .= " AND pr.reviewer_id = ?";
            $params[] = $reviewerId;
        }

        $sql .= " ORDER BY pr.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function createReview(array $data): int {
        return Database::insert('performance_reviews', [
            'employee_id' => (int)$data['employee_id'],
            'reviewer_id' => (int)$data['reviewer_id'],
            'review_period' => trim($data['review_period']),
            'self_rating' => !empty($data['self_rating']) ? (int)$data['self_rating'] : null,
            'self_comments' => trim($data['self_comments'] ?? ''),
            'manager_rating' => !empty($data['manager_rating']) ? (int)$data['manager_rating'] : null,
            'manager_comments' => trim($data['manager_comments'] ?? ''),
            'final_rating' => !empty($data['final_rating']) ? (float)$data['final_rating'] : null,
            'status' => $data['status'] ?? 'submitted'
        ]);
    }
}
