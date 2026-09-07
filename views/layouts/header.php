<?php
require_once BASE_PATH . '/src/Models/Attendance.php';
require_once BASE_PATH . '/src/Models/Notification.php';

$currentUser = Auth::user();
$empId = Auth::employeeId();
$todayPunch = $empId ? Attendance::getToday($empId) : null;
$pageTitle = $pageTitle ?? 'Dashboard';

// Notifications
$unreadNotifCount = $currentUser ? Notification::getUnreadCount((int)$currentUser['id']) : 0;
$recentNotifications = $currentUser ? Notification::getForUser((int)$currentUser['id'], 7) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <!-- Official Favicon -->
    <link rel="icon" type="image/png" href="<?= url('assets/img/fav.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= url('assets/img/fav.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/img/fav.png') ?>">
    
    <!-- FontAwesome 6 for Crisp Professional Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/print.css') ?>" media="print">
    
    <!-- Chart.js 4 for High-Definition Analytics Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-container">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <header class="app-header">
            <div class="header-left">
                <button id="sidebar-toggle" class="header-btn" title="Toggle Menu" style="display: none;">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="page-title-wrap">
                    <div class="page-breadcrumb">
                        <span>Advancells HRMS</span>
                        <i class="fa-solid fa-chevron-right" style="font-size: 8px; color: #94a3b8;"></i>
                        <span style="color: var(--primary); font-weight: 600;"><?= e($pageTitle) ?></span>
                    </div>
                    <h1 class="page-title"><?= e($pageTitle) ?></h1>
                </div>
            </div>

            <div class="header-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search employees, leaves, payroll..." aria-label="Global Search">
                <span class="search-kbd">Ctrl K</span>
            </div>

            <div class="header-right">
                <!-- Today Punch Status Pill -->
                <?php if ($empId): ?>
                    <?php if ($todayPunch && !empty($todayPunch['punch_in'])): ?>
                        <?php if (!empty($todayPunch['punch_out'])): ?>
                            <span class="badge badge-secondary" style="padding: 6px 12px; font-size: 11.5px;">
                                <i class="fa-regular fa-circle-check" style="color: #64748b;"></i> Punched Out (<?= $todayPunch['total_hours'] ?>h)
                            </span>
                        <?php else: ?>
                            <a href="<?= url('attendance/punch') ?>" style="text-decoration: none;">
                                <span class="badge badge-success" style="padding: 6px 12px; font-size: 11.5px; cursor: pointer;">
                                    <span class="pulse-dot" style="width: 5px; height: 5px;"></span> In: <?= format_time($todayPunch['punch_in']) ?>
                                </span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= url('attendance/punch') ?>" style="text-decoration: none;">
                            <span class="badge badge-warning" style="padding: 6px 12px; font-size: 11.5px; cursor: pointer;">
                                <i class="fa-solid fa-clock"></i> Not Punched In
                            </span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Real-time Clock Widget with Live Pulse -->
                <div class="clock-widget">
                    <span class="clock-live">--:--:--</span>
                    <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">IST</span>
                </div>

                <!-- In-App Notification Bell & Dropdown -->
                <div class="notification-dropdown-wrapper" style="position: relative;">
                    <button type="button" class="header-btn" id="notifBellBtn" onclick="toggleNotificationDropdown(event)" title="Notifications" style="position: relative; cursor: pointer;">
                        <i class="fa-solid fa-bell" style="font-size: 15px;"></i>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span id="notifBadge" style="position: absolute; top: -2px; right: -2px; background: #e11d48; color: #ffffff; font-size: 10px; font-weight: 800; min-width: 17px; height: 17px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid #ffffff; box-shadow: 0 2px 6px rgba(225,29,72,0.4); animation: notifPulse 2s infinite;">
                                <?= $unreadNotifCount > 9 ? '9+' : $unreadNotifCount ?>
                            </span>
                        <?php else: ?>
                            <span id="notifBadge" style="display: none;"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="notifDropdownMenu" style="display: none; position: absolute; right: 0; top: calc(100% + 12px); width: 350px; max-width: 90vw; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 12px 30px rgba(0,0,0,0.15); z-index: 10000; overflow: hidden; animation: notifSlideDown 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
                        <!-- Dropdown Header -->
                        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                            <div style="font-weight: 700; font-size: 13.5px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-bell text-primary"></i> Notifications
                                <?php if ($unreadNotifCount > 0): ?>
                                    <span class="badge" style="background: #fdf2f8; color: #93206c; font-size: 10.5px; font-weight: 700; padding: 2px 7px;">
                                        <?= $unreadNotifCount ?> new
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($unreadNotifCount > 0): ?>
                                <button type="button" onclick="markAllNotificationsRead()" style="background: none; border: none; font-size: 11.5px; color: #93206c; font-weight: 600; cursor: pointer; padding: 0;" title="Mark all notifications as read">
                                    <i class="fa-solid fa-check-double"></i> Mark all read
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Dropdown Items List -->
                        <div id="notifItemsList" style="max-height: 360px; overflow-y: auto;">
                            <?php if (!empty($recentNotifications)): ?>
                                <?php foreach ($recentNotifications as $n): ?>
                                    <a href="<?= url('notifications/mark-read?id=' . $n['id']) ?>" style="display: flex; gap: 12px; padding: 12px 16px; text-decoration: none; border-bottom: 1px solid #f8fafc; transition: background 0.15s; background: <?= $n['is_read'] ? '#ffffff' : '#fdf8fa' ?>;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='<?= $n['is_read'] ? '#ffffff' : '#fdf8fa' ?>'">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= e($n['icon_color']) ?>18; color: <?= e($n['icon_color']) ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px;">
                                            <i class="fa-solid <?= e($n['icon'] ?: 'fa-bell') ?>"></i>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;">
                                                <div style="font-size: 12.5px; font-weight: <?= $n['is_read'] ? '600' : '700' ?>; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= e($n['title']) ?>
                                                </div>
                                                <span style="font-size: 10px; color: #94a3b8; flex-shrink: 0; white-space: nowrap;">
                                                    <?= time_ago($n['created_at']) ?>
                                                </span>
                                            </div>
                                            <div style="font-size: 11.5px; color: #64748b; line-height: 1.35; margin-top: 2px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?= e($n['message']) ?>
                                            </div>
                                        </div>
                                        <?php if (!$n['is_read']): ?>
                                            <div style="width: 6px; height: 6px; border-radius: 50%; background: #93206c; margin-top: 6px; flex-shrink: 0;"></div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 36px 20px; text-align: center;">
                                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 18px;">
                                        <i class="fa-regular fa-bell-slash"></i>
                                    </div>
                                    <div style="font-size: 13px; font-weight: 600; color: #64748b;">No notifications yet</div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">You're all caught up!</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= url('profile') ?>" class="header-btn" title="<?= e($currentUser['name'] ?? 'My Profile') ?> - Profile & Settings" style="padding: 0; overflow: hidden; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%;">
                    <?php if (!empty($currentUser['avatar']) && file_exists(BASE_PATH . '/' . $currentUser['avatar'])): ?>
                        <img src="<?= url($currentUser['avatar']) ?>?t=<?= time() ?>" alt="<?= e($currentUser['name'] ?? 'User') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fa-solid fa-user-gear"></i>
                    <?php endif; ?>
                </a>
                <a href="<?= url('logout') ?>" class="header-btn" title="Sign Out" onclick="return confirmAction('Sign out from your active session?')">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <main class="content-wrapper">
            <!-- Flash Notification Alerts -->
            <div class="flash-container">
                <?php foreach (get_flash() as $msg): ?>
                    <div class="alert alert-<?= e($msg['type']) ?>">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-info"></i>
                            <?= e($msg['message']) ?>
                        </span>
                        <button type="button" style="background:none;border:none;cursor:pointer;font-size:16px;" onclick="this.parentElement.remove()">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

<style>
@keyframes notifPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.12); }
    100% { transform: scale(1); }
}
@keyframes notifSlideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
function toggleNotificationDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notifDropdownMenu');
    if (dropdown) {
        dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
    }
}

document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notification-dropdown-wrapper');
    const dropdown = document.getElementById('notifDropdownMenu');
    if (dropdown && wrapper && !wrapper.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

function markAllNotificationsRead() {
    fetch('<?= url("notifications/mark-all-read") ?>?json=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => res.json()).then(data => {
        if (data.success) {
            const badge = document.getElementById('notifBadge');
            if (badge) badge.style.display = 'none';
            location.reload();
        }
    }).catch(err => {
        window.location.href = '<?= url("notifications/mark-all-read") ?>';
    });
}
</script>
