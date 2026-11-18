<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('doctor');
$uid = (int)$user['id'];

$transitions = [
    'confirm'  => ['from' => ['pending'],              'to' => 'confirmed', 'msg' => 'confirmed'],
    'complete' => ['from' => ['confirmed'],            'to' => 'completed', 'msg' => 'marked as completed'],
    'cancel'   => ['from' => ['pending', 'confirmed'], 'to' => 'cancelled', 'msg' => 'cancelled by the doctor'],
];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);
    $id = post_int('id');
    $appt = q('SELECT id, patient_id, status, appointment_date, appointment_time FROM appointments WHERE id = ? AND doctor_id = ?', [$id, $uid])->fetch();
    if ($appt && isset($transitions[$action]) && in_array($appt['status'], $transitions[$action]['from'], true)) {
        $t = $transitions[$action];
        q('UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?', [$t['to'], $id, $uid]);
        notify((int)$appt['patient_id'], 'appointment',
            'Your appointment on ' . fmt_date($appt['appointment_date']) . ' at ' . fmt_time($appt['appointment_time']) . ' was ' . $t['msg'] . '.',
            'patient/appointments.php');
        audit('Appointment #' . $id . ' -> ' . $t['to']);
        flash('success', 'Appointment ' . $t['to'] . '.');
    } else {
        flash('error', 'That action is not allowed for this appointment.');
    }
    redirect('doctor/appointments.php');
}

$status = $_GET['status'] ?? 'upcoming';
$filters = ['upcoming', 'pending', 'confirmed', 'completed', 'cancelled', 'all'];
if (!in_array($status, $filters, true)) {
    $status = 'upcoming';
}
$sql = 'SELECT a.*, u.full_name AS patient, u.phone FROM appointments a JOIN users u ON u.id = a.patient_id WHERE a.doctor_id = ?';
$params = [$uid];
if ($status === 'upcoming') {
    $sql .= " AND a.appointment_date >= CURDATE() AND a.status IN ('pending','confirmed')";
} elseif ($status !== 'all') {
    $sql .= ' AND a.status = ?';
    $params[] = $status;
}
$sql .= $status === 'upcoming' ? ' ORDER BY a.appointment_date, a.appointment_time' : ' ORDER BY a.appointment_date DESC, a.appointment_time DESC';
$appts = q($sql . ' LIMIT 200', $params)->fetchAll();

$pageTitle = 'Appointments';
require __DIR__ . '/../includes/header.php';
?>
<div class="tabs">
    <?php foreach ($filters as $f): ?>
        <a class="<?= $status === $f ? 'active' : '' ?>" href="<?= e(url('doctor/appointments.php?status=' . $f)) ?>"><?= e(ucfirst($f)) ?></a>
    <?php endforeach; ?>
</div>
<div class="card">
    <?php if (!$appts): ?>
        <div class="empty-state"><i class="fa-regular fa-calendar fa-3x"></i><p>No appointments in this view.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
                <tr>
                    <td data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></td>
                    <td data-label="Time"><?= e(fmt_time($a['appointment_time'])) ?></td>
                    <td data-label="Patient"><a href="<?= e(url('doctor/patients.php?id=' . (int)$a['patient_id'])) ?>"><?= e($a['patient']) ?></a>
                        <br><small class="muted"><?= e($a['phone'] ?? '') ?></small></td>
                    <td data-label="Reason"><?= e($a['reason']) ?></td>
                    <td data-label="Status"><?= badge($a['status']) ?></td>
                    <td><div class="btn-row">
                        <?php foreach ($transitions as $act => $t): if (!in_array($a['status'], $t['from'], true)) continue; ?>
                            <form method="post" class="inline-form" <?= $act === 'cancel' ? 'data-confirm="Cancel this appointment?"' : '' ?>>
                                <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($act) ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $act === 'cancel' ? 'btn-outline' : ($act === 'confirm' ? 'btn-primary' : 'btn-success') ?>">
                                    <i class="fa-solid <?= $act === 'confirm' ? 'fa-check' : ($act === 'complete' ? 'fa-check-double' : 'fa-xmark') ?>"></i> <?= e(ucfirst($act)) ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
