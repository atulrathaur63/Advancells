<?php
require_once BASE_PATH . '/src/Models/Attendance.php';

$currentUser = Auth::user();
$empId = Auth::employeeId();
$todayPunch = $empId ? Attendance::getToday($empId) : null;
$pageTitle = $pageTitle ?? 'Dashboard';
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
