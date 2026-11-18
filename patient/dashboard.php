<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');
$uid = (int)$user['id'];

$upcoming = q("SELECT a.*, u.full_name AS doctor FROM appointments a JOIN users u ON u.id = a.doctor_id
               WHERE a.patient_id = ? AND a.status IN ('pending','confirmed') AND a.appointment_date >= CURDATE()
               ORDER BY a.appointment_date, a.appointment_time LIMIT 5", [$uid])->fetchAll();
$unpaid = q("SELECT COALESCE(SUM(b.amount - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.bill_id = b.id), 0)), 0)
             FROM bills b WHERE b.patient_id = ? AND b.status <> 'paid'", [$uid])->fetchColumn();
$labCount = q('SELECT COUNT(*) FROM lab_results WHERE patient_id = ?', [$uid])->fetchColumn();
$msgCount = unread_messages($uid);
$labs = q('SELECT test_name, result, status, test_date FROM lab_results WHERE patient_id = ? ORDER BY test_date DESC, id DESC LIMIT 4', [$uid])->fetchAll();
$reminders = q('SELECT type, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5', [$uid])->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<p class="muted">Hello <strong><?= e($user['full_name']) ?></strong>, here is an overview of your health information.</p>

<div class="stats">
    <a class="stat" href="<?= e(url('patient/appointments.php')) ?>">
        <span class="stat-icon blue"><i class="fa-solid fa-calendar-check"></i></span>
        <span><span class="stat-value"><?= count($upcoming) ?></span><br><span class="stat-label">Upcoming appointments</span></span>
    </a>
    <a class="stat" href="<?= e(url('patient/lab-results.php')) ?>">
        <span class="stat-icon teal"><i class="fa-solid fa-flask"></i></span>
        <span><span class="stat-value"><?= (int)$labCount ?></span><br><span class="stat-label">Lab results</span></span>
    </a>
    <a class="stat" href="<?= e(url('patient/messages.php')) ?>">
        <span class="stat-icon amber"><i class="fa-solid fa-envelope"></i></span>
        <span><span class="stat-value"><?= (int)$msgCount ?></span><br><span class="stat-label">Unread messages</span></span>
    </a>
    <a class="stat" href="<?= e(url('patient/bills.php')) ?>">
        <span class="stat-icon red"><i class="fa-solid fa-file-invoice-dollar"></i></span>
        <span><span class="stat-value"><?= e(money($unpaid)) ?></span><br><span class="stat-label">Outstanding balance</span></span>
    </a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-head">
            <h2><i class="fa-solid fa-calendar-days"></i> Upcoming appointments</h2>
            <a class="btn btn-primary btn-sm" href="<?= e(url('patient/book-appointment.php')) ?>"><i class="fa-solid fa-plus"></i> Book</a>
        </div>
        <?php if (!$upcoming): ?>
            <div class="empty-state"><i class="fa-regular fa-calendar fa-2x"></i><p>No upcoming appointments.</p></div>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($upcoming as $a): ?>
                    <li>
                        <span class="li-icon"><i class="fa-solid fa-user-doctor"></i></span>
                        <div class="li-body">
                            <div class="li-title"><?= e($a['doctor']) ?></div>
                            <div class="li-meta"><?= e(fmt_date($a['appointment_date'])) ?> at <?= e(fmt_time($a['appointment_time'])) ?> - <?= e($a['reason']) ?></div>
                        </div>
                        <?= badge($a['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head">
            <h2><i class="fa-solid fa-bell"></i> Reminders</h2>
            <a class="btn btn-outline btn-sm" href="<?= e(url('notifications.php')) ?>">View all</a>
        </div>
        <?php if (!$reminders): ?>
            <div class="empty-state"><i class="fa-regular fa-bell-slash fa-2x"></i><p>You are all caught up.</p></div>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($reminders as $r): ?>
                    <li class="unread">
                        <span class="li-icon"><i class="fa-solid <?= $r['type'] === 'medication' ? 'fa-pills' : ($r['type'] === 'bill' ? 'fa-file-invoice-dollar' : 'fa-bell') ?>"></i></span>
                        <div class="li-body">
                            <div class="li-title"><?= e($r['message']) ?></div>
                            <div class="li-meta"><?= e(fmt_datetime($r['created_at'])) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="card mt">
    <div class="card-head">
        <h2><i class="fa-solid fa-flask"></i> Recent lab results</h2>
        <a class="btn btn-outline btn-sm" href="<?= e(url('patient/lab-results.php')) ?>">View all</a>
    </div>
    <?php if (!$labs): ?>
        <div class="empty-state"><p>No lab results yet.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Test</th><th>Result</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($labs as $l): ?>
                <tr>
                    <td data-label="Test"><?= e($l['test_name']) ?></td>
                    <td data-label="Result"><?= e($l['result']) ?></td>
                    <td data-label="Date"><?= e(fmt_date($l['test_date'])) ?></td>
                    <td data-label="Status"><?= badge($l['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
