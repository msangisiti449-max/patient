<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('admin');

$counts = q("SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'patient') AS patients,
    (SELECT COUNT(*) FROM users WHERE role = 'doctor')  AS doctors,
    (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status IN ('pending','confirmed')) AS today,
    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())) AS revenue,
    (SELECT COALESCE(SUM(b.amount), 0) - COALESCE((SELECT SUM(p.amount) FROM payments p JOIN bills b2 ON b2.id = p.bill_id WHERE b2.status <> 'paid'), 0)
       FROM bills b WHERE b.status <> 'paid') AS outstanding,
    (SELECT COUNT(*) FROM login_attempts WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) AS failed_logins")->fetch();
$recent = q('SELECT a.action, a.ip, a.created_at, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8')->fetchAll();
$locked = q("SELECT id, full_name, email, locked_until FROM users WHERE locked_until > NOW()")->fetchAll();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <a class="stat" href="<?= e(url('admin/users.php?role=patient')) ?>"><span class="stat-icon blue"><i class="fa-solid fa-hospital-user"></i></span>
        <span><span class="stat-value"><?= (int)$counts['patients'] ?></span><br><span class="stat-label">Patients</span></span></a>
    <a class="stat" href="<?= e(url('admin/users.php?role=doctor')) ?>"><span class="stat-icon teal"><i class="fa-solid fa-user-doctor"></i></span>
        <span><span class="stat-value"><?= (int)$counts['doctors'] ?></span><br><span class="stat-label">Doctors</span></span></a>
    <a class="stat" href="<?= e(url('admin/appointments.php')) ?>"><span class="stat-icon amber"><i class="fa-solid fa-calendar-day"></i></span>
        <span><span class="stat-value"><?= (int)$counts['today'] ?></span><br><span class="stat-label">Appointments today</span></span></a>
    <a class="stat" href="<?= e(url('admin/bills.php')) ?>"><span class="stat-icon teal"><i class="fa-solid fa-sack-dollar"></i></span>
        <span><span class="stat-value"><?= e(money($counts['revenue'])) ?></span><br><span class="stat-label">Collected this month</span></span></a>
    <a class="stat" href="<?= e(url('admin/bills.php?status=unpaid')) ?>"><span class="stat-icon red"><i class="fa-solid fa-file-invoice-dollar"></i></span>
        <span><span class="stat-value"><?= e(money($counts['outstanding'])) ?></span><br><span class="stat-label">Outstanding</span></span></a>
    <a class="stat" href="<?= e(url('admin/audit-log.php?q=Failed')) ?>"><span class="stat-icon red"><i class="fa-solid fa-user-lock"></i></span>
        <span><span class="stat-value"><?= (int)$counts['failed_logins'] ?></span><br><span class="stat-label">Failed sign-ins (24h)</span></span></a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-lock"></i> Locked accounts</h2></div>
        <?php if (!$locked): ?><p class="muted">No accounts are locked.</p><?php else: ?>
            <ul class="list"><?php foreach ($locked as $l): ?>
                <li><span class="li-icon"><i class="fa-solid fa-user-lock"></i></span>
                    <div class="li-body"><div class="li-title"><?= e($l['full_name']) ?></div>
                        <div class="li-meta"><?= e($l['email']) ?> &middot; until <?= e(fmt_datetime($l['locked_until'])) ?></div></div>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('admin/users.php?q=' . rawurlencode($l['email']))) ?>">Manage</a></li>
            <?php endforeach; ?></ul>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-clipboard-list"></i> Recent activity</h2>
            <a class="btn btn-outline btn-sm" href="<?= e(url('admin/audit-log.php')) ?>">Full log</a></div>
        <ul class="list"><?php foreach ($recent as $r): ?>
            <li><div class="li-body"><div class="li-title"><?= e($r['action']) ?></div>
                <div class="li-meta"><?= e($r['full_name'] ?? 'Guest') ?> &middot; <?= e($r['ip']) ?> &middot; <?= e(fmt_datetime($r['created_at'])) ?></div></div></li>
        <?php endforeach; ?></ul>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
