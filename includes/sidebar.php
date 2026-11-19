<?php
/** Role-based navigation. */
$menus = [
    'patient' => [
        ['patient/dashboard.php',     'fa-gauge-high',                  'Dashboard'],
        ['patient/records.php',       'fa-notes-medical',               'Medical Records'],
        ['patient/appointments.php',  'fa-calendar-check',              'Appointments'],
        ['patient/lab-results.php',   'fa-flask',                       'Lab Results'],
        ['patient/prescriptions.php', 'fa-prescription-bottle-medical', 'Prescriptions'],
        ['patient/messages.php',      'fa-envelope',                    'Messages'],
        ['patient/bills.php',         'fa-file-invoice-dollar',         'Bills & Payments'],
        ['notifications.php',         'fa-bell',                        'Reminders'],
        ['profile.php',               'fa-id-card',                     'My Profile'],
    ],
    'doctor' => [
        ['doctor/dashboard.php',     'fa-gauge-high',                  'Dashboard'],
        ['doctor/appointments.php',  'fa-calendar-check',              'Appointments'],
        ['doctor/patients.php',      'fa-hospital-user',               'Patients'],
        ['doctor/lab-results.php',   'fa-flask',                       'Lab Results'],
        ['doctor/prescriptions.php', 'fa-prescription-bottle-medical', 'Prescriptions'],
        ['doctor/messages.php',      'fa-envelope',                    'Messages'],
        ['notifications.php',        'fa-bell',                        'Notifications'],
        ['profile.php',              'fa-id-card',                     'My Profile'],
    ],
    'admin' => [
        ['admin/dashboard.php',    'fa-gauge-high',          'Dashboard'],
        ['admin/users.php',        'fa-users-gear',          'Users'],
        ['admin/appointments.php', 'fa-calendar-days',       'Appointments'],
        ['admin/bills.php',        'fa-file-invoice-dollar', 'Billing'],
        ['admin/audit-log.php',    'fa-clipboard-list',      'Audit Log'],
        ['notifications.php',      'fa-bell',                'Notifications'],
        ['profile.php',            'fa-id-card',             'My Profile'],
    ],
];
$current = ltrim(substr($_SERVER['SCRIPT_NAME'] ?? '', strlen(BASE_URL)), '/');
$msgCount = unread_messages((int)$user['id']);
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url(dashboard_path($user['role']))) ?>">
        <i class="fa-solid fa-heart-pulse"></i><span>CarePoint</span>
    </a>
    <nav>
        <?php foreach ($menus[$user['role']] as [$path, $icon, $label]): ?>
            <a href="<?= e(url($path)) ?>" class="<?= $current === $path ? 'active' : '' ?>">
                <i class="fa-solid <?= e($icon) ?>"></i><span><?= e($label) ?></span>
                <?php if (str_ends_with($path, 'messages.php') && $msgCount): ?>
                    <span class="nav-count"><?= (int)$msgCount ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="<?= e(url('logout.php')) ?>" class="logout-form">
        <?= csrf_field() ?>
        <button type="submit" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i><span>Sign out</span></button>
    </form>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
