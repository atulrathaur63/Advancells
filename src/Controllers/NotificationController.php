<?php
/**
 * In-App Notification Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Notification.php';

class NotificationController {
    /**
     * Mark a single notification as read and redirect to its target link
     */
    public function markRead(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $userId = Auth::id();

        $notif = Database::fetchOne("SELECT * FROM notifications WHERE id = ? AND user_id = ?", [$id, $userId]);
        if ($notif) {
            Notification::markAsRead($id, $userId);
            if (!empty($notif['link'])) {
                redirect($notif['link']);
            }
        }

        $this->safeRedirectBack();
    }

    /**
     * Mark all notifications as read for the logged in user
     */
    public function markAllRead(): void {
        Auth::requireLogin();
        $userId = Auth::id();

        Notification::markAllAsRead($userId);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_GET['json'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        $this->safeRedirectBack();
    }

    private function safeRedirectBack(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!empty($referer) && str_starts_with($referer, BASE_URL)) {
            header("Location: " . $referer);
            exit;
        }
        redirect('dashboard');
    }

    /**
     * Live API to fetch unread count & recent notification items
     */
    public function unreadCount(): void {
        Auth::requireLogin();
        $userId = Auth::id();

        header('Content-Type: application/json');
        $count = Notification::getUnreadCount($userId);
        $items = Notification::getForUser($userId, 7);

        // Format items with time_ago
        $formatted = array_map(function($n) {
            $n['time_ago'] = time_ago($n['created_at']);
            return $n;
        }, $items);

        echo json_encode([
            'count' => $count,
            'items' => $formatted
        ]);
        exit;
    }
}
