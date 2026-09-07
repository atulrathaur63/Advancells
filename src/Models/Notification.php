<?php
/**
 * In-App Notification Model
 */

require_once __DIR__ . '/../Database.php';

class Notification {
    /**
     * Send a notification to a specific user
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $icon = 'fa-bell',
        string $iconColor = '#93206c'
    ): int {
        return Database::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon' => $icon,
            'icon_color' => $iconColor,
            'is_read' => 0
        ]);
    }

    /**
     * Broadcast a notification to all users with specific role(s)
     */
    public static function sendToRole(
        $roles,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $icon = 'fa-bell',
        string $iconColor = '#93206c'
    ): void {
        $rolesArray = is_array($roles) ? $roles : [$roles];
        if (empty($rolesArray)) return;

        $placeholders = implode(',', array_fill(0, count($rolesArray), '?'));
        $users = Database::fetchAll("SELECT id FROM users WHERE role IN ($placeholders) AND status = 'active'", $rolesArray);

        foreach ($users as $u) {
            self::send((int)$u['id'], $type, $title, $message, $link, $icon, $iconColor);
        }
    }

    /**
     * Get recent notifications for a user
     */
    public static function getForUser(int $userId, int $limit = 10): array {
        $limit = max(1, (int)$limit);
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT {$limit}";
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Get count of unread notifications for a user
     */
    public static function getUnreadCount(int $userId): int {
        $row = Database::fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
        return (int)($row['count'] ?? 0);
    }

    /**
     * Mark a single notification as read
     */
    public static function markAsRead(int $id, int $userId): bool {
        return Database::update('notifications', ['is_read' => 1], "id = ? AND user_id = ?", [$id, $userId]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead(int $userId): bool {
        return Database::update('notifications', ['is_read' => 1], "user_id = ? AND is_read = 0", [$userId]);
    }
}
