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

    public static function updateGoalProgress(int $goalId, int $progress, string $status, ?int $employeeId = null): bool {
        $goal = Database::fetchOne("SELECT id, employee_id FROM performance_goals WHERE id = ?", [$goalId]);
        if (!$goal) {
            return false;
        }

        if ($employeeId !== null && (int)$goal['employee_id'] !== $employeeId) {
            return false;
        }

        Database::update('performance_goals', [
            'progress' => min(100, max(0, $progress)),
            'status' => $status
        ], "id = ?", [$goalId]);

        return true;
    }

    public static function countReviews(?int $employeeId = null, ?int $reviewerId = null, array $employeeIds = []): int {
        $sql = "SELECT count(*) AS cnt
                FROM performance_reviews pr
                JOIN employees e ON pr.employee_id = e.id
                WHERE 1=1";
        $params = [];

        if ($employeeId !== null) {
            $sql .= " AND pr.employee_id = ?";
            $params[] = $employeeId;
        } elseif (!empty($employeeIds)) {
            $empList = array_values(array_filter(array_map('intval', $employeeIds)));
            if (!empty($empList)) {
                $placeholders = implode(',', array_fill(0, count($empList), '?'));
                if ($reviewerId !== null) {
                    $sql .= " AND (pr.employee_id IN ($placeholders) OR pr.reviewer_id = ?)";
                    $params = array_merge($params, $empList, [$reviewerId]);
                } else {
                    $sql .= " AND pr.employee_id IN ($placeholders)";
                    $params = array_merge($params, $empList);
                }
            } else {
                $sql .= " AND 1=0";
            }
        } elseif ($reviewerId !== null) {
            $sql .= " AND pr.reviewer_id = ?";
            $params[] = $reviewerId;
        }

        $row = Database::fetchOne($sql, $params);
        return (int)($row['cnt'] ?? 0);
    }

    public static function getReviews(?int $employeeId = null, ?int $reviewerId = null, array $employeeIds = [], ?int $limit = null, ?int $offset = null): array {
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
        } elseif (!empty($employeeIds)) {
            $empList = array_values(array_filter(array_map('intval', $employeeIds)));
            if (!empty($empList)) {
                $placeholders = implode(',', array_fill(0, count($empList), '?'));
                if ($reviewerId !== null) {
                    $sql .= " AND (pr.employee_id IN ($placeholders) OR pr.reviewer_id = ?)";
                    $params = array_merge($params, $empList, [$reviewerId]);
                } else {
                    $sql .= " AND pr.employee_id IN ($placeholders)";
                    $params = array_merge($params, $empList);
                }
            } else {
                $sql .= " AND 1=0";
            }
        } elseif ($reviewerId !== null) {
            $sql .= " AND pr.reviewer_id = ?";
            $params[] = $reviewerId;
        }

        $sql .= " ORDER BY pr.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return Database::fetchAll($sql, $params);
    }

    public static function getReviewById(int $id): ?array {
        $sql = "SELECT pr.*, e.emp_code, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                       d.name AS department_name, des.title AS designation_title,
                       CONCAT(r.first_name, ' ', r.last_name) AS reviewer_name
                FROM performance_reviews pr
                JOIN employees e ON pr.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN employees r ON pr.reviewer_id = r.id
                WHERE pr.id = ?";
        $res = Database::fetchOne($sql, [$id]);
        return $res ?: null;
    }

    public static function updateReview(int $reviewId, array $data): bool {
        $fields = [];
        $allowed = ['self_rating', 'self_comments', 'manager_rating', 'manager_comments', 'hr_rating', 'final_rating', 'status', 'reviewer_id'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = $data[$field];
            }
        }
        if (empty($fields)) {
            return false;
        }
        Database::update('performance_reviews', $fields, "id = ?", [$reviewId]);
        return true;
    }

    public static function createReview(array $data): int {
        return Database::insert('performance_reviews', [
            'employee_id' => (int)$data['employee_id'],
            'reviewer_id' => (int)$data['reviewer_id'],
            'review_period' => trim($data['review_period'] ?? ('Q3 ' . date('Y'))),
            'self_rating' => isset($data['self_rating']) && $data['self_rating'] !== '' ? (int)$data['self_rating'] : null,
            'self_comments' => trim($data['self_comments'] ?? ''),
            'manager_rating' => isset($data['manager_rating']) && $data['manager_rating'] !== '' ? (int)$data['manager_rating'] : null,
            'manager_comments' => trim($data['manager_comments'] ?? ''),
            'final_rating' => isset($data['final_rating']) && $data['final_rating'] !== '' ? (float)$data['final_rating'] : null,
            'status' => $data['status'] ?? 'submitted'
        ]);
    }
}
