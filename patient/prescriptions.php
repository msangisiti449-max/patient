<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');
$uid = (int)$user['id'];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);
    $pid = post_int('prescription_id');
    // Ownership check: the prescription must belong to this patient.
    $rx = q('SELECT id, doctor_id, medication, renewable FROM prescriptions WHERE id = ? AND patient_id = ?', [$pid, $uid])->fetch();

    if (!$rx) {
        flash('error', 'Prescription not found.');
    } elseif ($action === 'renew') {
        $pending = q("SELECT 1 FROM prescription_requests WHERE prescription_id = ? AND status = 'pending'", [$pid])->fetchColumn();
        if (!(int)$rx['renewable']) {
            flash('error', 'This prescription cannot be renewed online. Please book an appointment.');
        } elseif ($pending) {
            flash('warning', 'A renewal request for this prescription is already pending.');
        } else {
            q('INSERT INTO prescription_requests (prescription_id, patient_id, patient_note) VALUES (?, ?, ?)',
                [$pid, $uid, post_str('note', 255) ?: null]);
            if ($rx['doctor_id']) {
                notify((int)$rx['doctor_id'], 'prescription',
                    $user['full_name'] . ' requested a renewal of ' . $rx['medication'] . '.', 'doctor/prescriptions.php');
            }
            audit('Requested prescription renewal #' . $pid);
            flash('success', 'Renewal request sent to your doctor.');
        }
    } elseif ($action === 'reminder') {
        $time = post_str('remind_time', 5);
        if (!valid_time($time)) {
            flash('error', 'Enter a valid reminder time.');
        } else {
            q('INSERT INTO medication_reminders (patient_id, prescription_id, remind_time) VALUES (?, ?, ?)', [$uid, $pid, $time . ':00']);
            flash('success', 'Daily medication reminder set for ' . fmt_time($time) . '.');
        }
    } elseif ($action === 'remove_reminder') {
        q('DELETE FROM medication_reminders WHERE id = ? AND patient_id = ?', [post_int('reminder_id'), $uid]);
        flash('success', 'Reminder removed.');
    }
    redirect('patient/prescriptions.php');
}

$rxs = q('SELECT p.*, u.full_name AS doctor,
            (SELECT status FROM prescription_requests r WHERE r.prescription_id = p.id ORDER BY r.id DESC LIMIT 1) AS last_request
          FROM prescriptions p LEFT JOIN users u ON u.id = p.doctor_id
          WHERE p.patient_id = ? ORDER BY p.start_date DESC', [$uid])->fetchAll();
$reminders = q('SELECT r.id, r.prescription_id, r.remind_time FROM medication_reminders r WHERE r.patient_id = ? ORDER BY r.remind_time', [$uid])->fetchAll();
$byRx = [];
foreach ($reminders as $r) {
    $byRx[$r['prescription_id']][] = $r;
}
$requests = q('SELECT r.*, p.medication FROM prescription_requests r JOIN prescriptions p ON p.id = r.prescription_id
               WHERE r.patient_id = ? ORDER BY r.created_at DESC LIMIT 10', [$uid])->fetchAll();

$pageTitle = 'Prescriptions';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-prescription-bottle-medical"></i> My prescriptions</h2></div>
    <?php if (!$rxs): ?>
        <div class="empty-state"><i class="fa-solid fa-pills fa-3x"></i><p>No prescriptions on file.</p></div>
    <?php else: ?>
        <ul class="list">
        <?php foreach ($rxs as $p):
            $active = !$p['end_date'] || $p['end_date'] >= date('Y-m-d'); ?>
            <li>
                <span class="li-icon"><i class="fa-solid fa-capsules"></i></span>
                <div class="li-body">
                    <div class="li-title"><?= e($p['medication']) ?> - <?= e($p['dosage']) ?> <?= badge($active ? 'active' : 'inactive') ?></div>
                    <div class="li-meta"><?= e($p['frequency']) ?> &middot; <?= e(fmt_date($p['start_date'])) ?> to <?= e(fmt_date($p['end_date'])) ?>
                        &middot; <i class="fa-solid fa-user-doctor"></i> <?= e($p['doctor'] ?? '-') ?></div>
                    <?php foreach ($byRx[$p['id']] ?? [] as $rem): ?>
                        <form method="post" class="inline-form" data-confirm="Remove this reminder?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="remove_reminder">
                            <input type="hidden" name="prescription_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="reminder_id" value="<?= (int)$rem['id'] ?>">
                            <button type="submit" class="badge badge-info"><i class="fa-regular fa-clock"></i> <?= e(fmt_time($rem['remind_time'])) ?> <i class="fa-solid fa-xmark"></i></button>
                        </form>
                    <?php endforeach; ?>
                    <div class="btn-row mt">
                        <?php if ($active): ?>
                        <form method="post" class="btn-row">
                            <?= csrf_field() ?><input type="hidden" name="action" value="reminder"><input type="hidden" name="prescription_id" value="<?= (int)$p['id'] ?>">
                            <input type="time" name="remind_time" required aria-label="Reminder time">
                            <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-bell"></i> Add reminder</button>
                        </form>
                        <?php endif; ?>
                        <?php if ((int)$p['renewable'] && $p['last_request'] !== 'pending'): ?>
                        <form method="post" class="btn-row" data-confirm="Send a renewal request to your doctor?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="renew"><input type="hidden" name="prescription_id" value="<?= (int)$p['id'] ?>">
                            <input type="text" name="note" maxlength="255" placeholder="Note for doctor (optional)" aria-label="Note">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-rotate"></i> Request renewal</button>
                        </form>
                        <?php elseif ($p['last_request'] === 'pending'): ?>
                            <?= badge('pending') ?> <span class="muted">Renewal requested</span>
                        <?php else: ?>
                            <span class="muted"><i class="fa-solid fa-circle-info"></i> Not renewable online</span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> Renewal requests</h2></div>
    <?php if (!$requests): ?>
        <p class="muted">No renewal requests yet.</p>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Date</th><th>Medication</th><th>Status</th><th>Doctor's note</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td data-label="Date"><?= e(fmt_date($r['created_at'])) ?></td>
                    <td data-label="Medication"><?= e($r['medication']) ?></td>
                    <td data-label="Status"><?= badge($r['status']) ?></td>
                    <td data-label="Note"><?= e($r['doctor_note'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
