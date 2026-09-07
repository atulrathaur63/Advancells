<?php
/**
 * Announcement Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Announcement.php';
require_once __DIR__ . '/../Models/Notification.php';

class AnnouncementController {
    public function index(): void {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isHR()) {
            if (!validate_csrf()) {
                redirect('announcements');
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $allowedRoles = ['all', 'manager', 'employee'];
                $targetRole = in_array($_POST['target_role'] ?? '', $allowedRoles, true) ? $_POST['target_role'] : 'all';
                $postData = $_POST;
                $postData['target_role'] = $targetRole;

                Announcement::create($postData, Auth::id());
                $annTitle = trim($_POST['title'] ?? 'Company Announcement');
                $roles = ($targetRole === 'all') ? ['super_admin', 'hr_admin', 'manager', 'employee'] : [$targetRole];
                Notification::sendToRole($roles, 'announcement', 'New Announcement', $annTitle, 'announcements', 'fa-bullhorn', '#93206c');
                flash('success', 'Announcement published successfully!');
            } elseif ($action === 'delete') {
                $id = (int)$_POST['id'];
                Announcement::delete($id);
                flash('info', 'Announcement deleted.');
            }
            redirect('announcements');
        }

        $announcements = Announcement::getActive(Auth::role());
        require_once BASE_PATH . '/views/announcements/index.php';
    }
}
