<?php
/**
 * Announcements & Notice Board Model
 */

require_once __DIR__ . '/../Database.php';

class Announcement {
    public static function getActive(?string $role = null): array {
        $sql = "SELECT a.*, u.name AS author_name, u.role AS author_role
                FROM announcements a
                JOIN users u ON a.posted_by = u.id
                WHERE (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
        $params = [];

        if ($role !== null && !in_array($role, ['super_admin', 'hr_admin'], true)) {
            $sql .= " AND (a.target_role = 'all' OR a.target_role = ?)";
            $params[] = $role;
        }

        $sql .= " ORDER BY a.priority = 'urgent' DESC, a.priority = 'high' DESC, a.created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data, int $authorUserId): int {
        return Database::insert('announcements', [
            'title' => trim($data['title']),
            'content' => trim($data['content']),
            'priority' => $data['priority'] ?? 'normal',
            'target_role' => $data['target_role'] ?? 'all',
            'posted_by' => $authorUserId,
            'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null
        ]);
    }

    public static function delete(int $id): bool {
        return Database::delete('announcements', "id = ?", [$id]) > 0;
    }
}
