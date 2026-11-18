<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');
$uid = (int)$user['id'];

$doctors = q("SELECT u.id, u.full_name, dp.specialization FROM users u
              LEFT JOIN doctors_profile dp ON dp.user_id = u.id
              WHERE u.role = 'doctor' AND u.is_active = 1 ORDER BY u.full_name")->fetchAll();
$slots = [];
for ($h = 8; $h < 17; $h++) {
    foreach (['00', '30'] as $m) {
        $slots[] = sprintf('%02d:%s', $h, $m);
    }
}

$errors = [];
$old = ['doctor_id' => 0, 'date' => '', 'time' => '', 'reason' => ''];

if (is_post()) {
    verify_csrf();
    $old['doctor_id'] = post_int('doctor_id');
    $old['date']      = post_str('date', 10);
    $old['time']      = post_str('time', 5);
    $old['reason']    = post_str('reason', 255);

    $doctorIds = array_map('intval', array_column($doctors, 'id'));
    if (!in_array($old['doctor_id'], $doctorIds, true)) {
        $errors[] = 'Select a doctor.';
    }
    if (!valid_date($old['date']) || $old['date'] < date('Y-m-d') || $old['date'] > date('Y-m-d', strtotime('+90 days'))) {
        $errors[] = 'Choose a date between today and 90 days from now.';
    } elseif (date('N', strtotime($old['date'])) == 7) {
        $errors[] = 'The hospital outpatient clinic is closed on Sundays.';
    }
    if (!in_array($old['time'], $slots, true)) {
        $errors[] = 'Choose a valid time slot.';
    } elseif ($old['date'] === date('Y-m-d') && $old['time'] <= date('H:i')) {
        $errors[] = 'That time has already passed today.';
    }
    if (mb_strlen($old['reason']) < 3) {
        $errors[] = 'Briefly describe the reason for your visit.';
    }
    if (!$errors) {
        $taken = q("SELECT 1 FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
                    AND status IN ('pending','confirmed')", [$old['doctor_id'], $old['date'], $old['time'] . ':00'])->fetchColumn();
        $mine = q("SELECT 1 FROM appointments WHERE patient_id = ? AND appointment_date = ? AND appointment_time = ?
                   AND status IN ('pending','confirmed')", [$uid, $old['date'], $old['time'] . ':00'])->fetchColumn();
        if ($taken) {
            $errors[] = 'That time slot is already booked for this doctor. Please choose another time.';
        } elseif ($mine) {
            $errors[] = 'You already have an appointment at that time.';
        }
    }
    if (!$errors) {
        q('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?)',
            [$uid, $old['doctor_id'], $old['date'], $old['time'] . ':00', $old['reason']]);
        notify($old['doctor_id'], 'appointment',
            'New appointment request from ' . $user['full_name'] . ' on ' . fmt_date($old['date']) . ' at ' . fmt_time($old['time']) . '.',
            'doctor/appointments.php');
        audit('Booked appointment for ' . $old['date'] . ' ' . $old['time']);
        flash('success', 'Appointment requested. You will be notified when the doctor confirms it.');
        redirect('patient/appointments.php');
    }
}

$pageTitle = 'Book Appointment';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-calendar-plus"></i> Schedule a hospital visit</h2></div>
    <?php if ($errors): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if (!$doctors): ?>
        <div class="empty-state"><p>No doctors are available for booking right now.</p></div>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field full">
                <label for="doctor_id">Doctor</label>
                <select id="doctor_id" name="doctor_id" required>
                    <option value="">Select a doctor</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= $old['doctor_id'] === (int)$d['id'] ? 'selected' : '' ?>>
                            <?= e($d['full_name'] . ($d['specialization'] ? ' - ' . $d['specialization'] : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required data-future value="<?= e($old['date']) ?>">
                <span class="hint">Monday to Saturday</span>
            </div>
            <div class="field">
                <label for="time">Time</label>
                <select id="time" name="time" required>
                    <option value="">Select a time</option>
                    <?php foreach ($slots as $s): ?>
                        <option value="<?= e($s) ?>" <?= $old['time'] === $s ? 'selected' : '' ?>><?= e(fmt_time($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field full">
                <label for="reason">Reason for visit</label>
                <textarea id="reason" name="reason" maxlength="255" required><?= e($old['reason']) ?></textarea>
            </div>
        </div>
        <div class="btn-row mt">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calendar-check"></i> Request appointment</button>
            <a class="btn btn-outline" href="<?= e(url('patient/appointments.php')) ?>">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
