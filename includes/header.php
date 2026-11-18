<?php
/** Page header. Set $pageTitle before including. */
$user = current_user();
if ($user && $user['role'] === 'patient') {
    generate_reminders((int)$user['id']);
}
$flash = get_flash();
$notifCount = $user ? unread_notifications((int)$user['id']) : 0;
$showApp = $user && empty($publicLayout);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($pageTitle ?? 'Welcome') . ' | ' . APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="<?= $showApp ? 'app' : 'public' ?>" data-base="<?= e(BASE_URL) ?>">
<?php if ($showApp): ?>
<div class="layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="main">
        <header class="topbar">
            <button class="icon-btn" id="sidebarToggle" type="button" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
            <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
            <div class="topbar-right">
                <a class="icon-btn bell" href="<?= e(url('notifications.php')) ?>" aria-label="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="bell-count<?= $notifCount ? '' : ' hidden' ?>" id="bellCount"><?= (int)$notifCount ?></span>
                </a>
                <div class="user-chip">
                    <span class="avatar"><i class="fa-solid <?= $user['role'] === 'doctor' ? 'fa-user-doctor' : ($user['role'] === 'admin' ? 'fa-user-shield' : 'fa-user') ?>"></i></span>
                    <span class="user-name"><?= e($user['full_name']) ?><small><?= e(ucfirst($user['role'])) ?></small></span>
                </div>
            </div>
        </header>
        <main class="content">
<?php else: ?>
<header class="public-nav">
    <a class="brand" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-heart-pulse"></i><span>CarePoint</span></a>
    <div class="btn-row">
        <?php if ($user): ?>
            <a class="btn btn-primary" href="<?= e(url(dashboard_path($user['role']))) ?>"><i class="fa-solid fa-gauge-high"></i> My Dashboard</a>
        <?php else: ?>
            <a class="btn btn-outline" href="<?= e(url('register.php')) ?>"><i class="fa-solid fa-user-plus"></i> Register</a>
            <a class="btn btn-primary" href="<?= e(url('login.php')) ?>"><i class="fa-solid fa-right-to-bracket"></i> Sign in</a>
        <?php endif; ?>
    </div>
</header>
<main class="public-main">
<?php endif; ?>
<?php if ($flash): ?>
            <div class="alert alert-<?= e(in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info') ?>">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
                <span><?= e($flash['msg']) ?></span>
            </div>
<?php endif; ?>
