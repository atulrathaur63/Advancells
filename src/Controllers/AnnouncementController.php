<?php
/**
 * Announcement Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Announcement.php';

class AnnouncementController {
    public function index(): void {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isHR()) {
            if (!validate_csrf()) {
                redirect('announcements');
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                Announcement::create($_POST, Auth::id());
                flash('success', 'Announcement published company-wide!');
            } elseif ($action === 'delete') {
                $id = (int)$_POST['id'];
                Announcement::delete($id);
                flash('info', 'Announcement deleted.');
            }
            redirect('announcements');
        }

        $announcements = Announcement::getActive();
        require_once BASE_PATH . '/views/announcements/index.php';
    }
}
