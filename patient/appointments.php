<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');
$uid = (int)$user['id'];

if (is_post()) {
    verify_csrf();
    if (post_str('action', 20) === 'cancel') {
        $id = post_int('id');
        $appt = q("SELECT a.id, a.doctor_id, a.appointment_date FROM appointments a
                   WHERE a.id = ? AND a.patient_id = ? AND a.status IN ('pending','confirmed')", [$id, $uid])->fetch();
        if ($appt) {
            q("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ?", [$id, $uid]);
            notify((int)$appt['doctor_id'], 'appointment',
                $user['full_name'] . ' cancelled the appointment on ' . fmt_date($appt['appointment_date']) . '.', 'doctor/appointments.php');
            audit('Cancelled appointment #' . $id);
            flash('success', 'Appointment cancelled.');
        } else {
            flash('error', 'Appointment not found or cannot be cancelled.');
        }
    }
    redirect('patient/appointments.php');
}

$appts = q('SELECT a.*, u.full_name AS doctor, dp.specialization
            FROM appointments a JOIN users u ON u.id = a.doctor_id
            LEFT JOIN doctors_profile dp ON dp.user_id = a.doctor_id
            WHERE a.patient_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC', [$uid])->fetchAll();

$pageTitle = 'Appointments';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <h2><i class="fa-solid fa-calendar-check"></i> My appointments</h2>
        <a class="btn btn-primary" href="<?= e(url('patient/book-appointment.php')) ?>"><i class="fa-solid fa-calendar-plus"></i> Book appointment</a>
    </div>
    <?php if (!$appts): ?>
        <div class="empty-state"><i class="fa-regular fa-calendar fa-3x"></i><p>You have no appointments yet.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
                <tr>
                    <td data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></td>
                    <td data-label="Time"><?= e(fmt_time($a['appointment_time'])) ?></td>
                    <td data-label="Doctor"><?= e($a['doctor']) ?><br><small class="muted"><?= e($a['specialization'] ?? '') ?></small></td>
                    <td data-label="Reason"><?= e($a['reason']) ?></td>
                    <td data-label="Status"><?= badge($a['status']) ?></td>
                    <td>
                        <?php if (in_array($a['status'], ['pending', 'confirmed'], true) && $a['appointment_date'] >= date('Y-m-d')): ?>
                            <form method="post" class="inline-form" data-confirm="Cancel this appointment?">
                                <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
