<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');

if (is_post()) {
    verify_csrf();
    $id = post_int('id');
    $appt = q("SELECT id, patient_id, doctor_id, appointment_date FROM appointments WHERE id = ? AND status IN ('pending','confirmed')", [$id])->fetch();
    if ($appt && post_str('action', 20) === 'cancel') {
        q("UPDATE appointments SET status = 'cancelled' WHERE id = ?", [$id]);
        $msg = 'The appointment on ' . fmt_date($appt['appointment_date']) . ' was cancelled by the hospital.';
        notify((int)$appt['patient_id'], 'appointment', $msg, 'patient/appointments.php');
        notify((int)$appt['doctor_id'], 'appointment', $msg, 'doctor/appointments.php');
        audit('Admin cancelled appointment #' . $id);
        flash('success', 'Appointment cancelled.');
    }
    redirect('admin/appointments.php');
}

$date = $_GET['date'] ?? '';
$date = valid_date($date) ? $date : '';
$status = in_array($_GET['status'] ?? '', ['pending', 'confirmed', 'completed', 'cancelled'], true) ? $_GET['status'] : '';
$sql = 'SELECT a.*, p.full_name AS patient, d.full_name AS doctor FROM appointments a
        JOIN users p ON p.id = a.patient_id JOIN users d ON d.id = a.doctor_id WHERE 1 = 1';
$params = [];
if ($date !== '') { $sql .= ' AND a.appointment_date = ?'; $params[] = $date; }
if ($status !== '') { $sql .= ' AND a.status = ?'; $params[] = $status; }
$appts = q($sql . ' ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 300', $params)->fetchAll();

$pageTitle = 'All Appointments';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <form method="get" class="btn-row">
        <input type="date" name="date" value="<?= e($date) ?>" aria-label="Date">
        <select name="status" aria-label="Status"><option value="">All statuses</option>
            <?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-outline" href="<?= e(url('admin/appointments.php')) ?>">Reset</a>
    </form>
</div>
<div class="card">
    <?php if (!$appts): ?><div class="empty-state"><i class="fa-regular fa-calendar fa-3x"></i><p>No appointments found.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table-stack">
        <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($appts as $a): ?>
            <tr>
                <td data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></td>
                <td data-label="Time"><?= e(fmt_time($a['appointment_time'])) ?></td>
                <td data-label="Patient"><?= e($a['patient']) ?></td>
                <td data-label="Doctor"><?= e($a['doctor']) ?></td>
                <td data-label="Reason"><?= e($a['reason']) ?></td>
                <td data-label="Status"><?= badge($a['status']) ?></td>
                <td><?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?>
                    <form method="post" class="inline-form" data-confirm="Cancel this appointment? Patient and doctor will be notified.">
                        <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Cancel</button></form>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
