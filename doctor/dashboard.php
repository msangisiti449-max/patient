<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('doctor');
$uid = (int)$user['id'];

$today = q("SELECT a.*, u.full_name AS patient FROM appointments a JOIN users u ON u.id = a.patient_id
            WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() AND a.status IN ('pending','confirmed')
            ORDER BY a.appointment_time", [$uid])->fetchAll();
$pendingAppts = q("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending' AND appointment_date >= CURDATE()", [$uid])->fetchColumn();
$pendingRx = q("SELECT COUNT(*) FROM prescription_requests r JOIN prescriptions p ON p.id = r.prescription_id
                WHERE p.doctor_id = ? AND r.status = 'pending'", [$uid])->fetchColumn();
$patients = q("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?", [$uid])->fetchColumn();
$msgs = unread_messages($uid);

$pageTitle = 'Doctor Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<p class="muted">Good day, <strong><?= e($user['full_name']) ?></strong>. Here is your schedule for <?= e(date('l, d M Y')) ?>.</p>
<div class="stats">
    <a class="stat" href="<?= e(url('doctor/appointments.php?status=pending')) ?>"><span class="stat-icon amber"><i class="fa-solid fa-hourglass-half"></i></span>
        <span><span class="stat-value"><?= (int)$pendingAppts ?></span><br><span class="stat-label">Appointments to confirm</span></span></a>
    <a class="stat" href="<?= e(url('doctor/prescriptions.php')) ?>"><span class="stat-icon red"><i class="fa-solid fa-prescription-bottle-medical"></i></span>
        <span><span class="stat-value"><?= (int)$pendingRx ?></span><br><span class="stat-label">Renewal requests</span></span></a>
    <a class="stat" href="<?= e(url('doctor/messages.php')) ?>"><span class="stat-icon blue"><i class="fa-solid fa-envelope"></i></span>
        <span><span class="stat-value"><?= (int)$msgs ?></span><br><span class="stat-label">Unread messages</span></span></a>
    <a class="stat" href="<?= e(url('doctor/patients.php')) ?>"><span class="stat-icon teal"><i class="fa-solid fa-hospital-user"></i></span>
        <span><span class="stat-value"><?= (int)$patients ?></span><br><span class="stat-label">My patients</span></span></a>
</div>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-calendar-day"></i> Today's appointments</h2>
        <a class="btn btn-outline btn-sm" href="<?= e(url('doctor/appointments.php')) ?>">All appointments</a></div>
    <?php if (!$today): ?>
        <div class="empty-state"><i class="fa-regular fa-calendar fa-2x"></i><p>No appointments today.</p></div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($today as $a): ?>
                <li>
                    <span class="li-icon"><i class="fa-solid fa-user"></i></span>
                    <div class="li-body">
                        <div class="li-title"><a href="<?= e(url('doctor/patients.php?id=' . (int)$a['patient_id'])) ?>"><?= e($a['patient']) ?></a></div>
                        <div class="li-meta"><?= e(fmt_time($a['appointment_time'])) ?> - <?= e($a['reason']) ?></div>
                    </div>
                    <?= badge($a['status']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
