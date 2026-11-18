<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('doctor');
$uid = (int)$user['id'];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);
    $rid = post_int('request_id');
    $note = post_str('doctor_note', 255);
    // Only requests for prescriptions written by this doctor
    $req = q("SELECT r.id, r.patient_id, p.id AS rx_id, p.medication, p.dosage, p.frequency, p.start_date, p.end_date
              FROM prescription_requests r JOIN prescriptions p ON p.id = r.prescription_id
              WHERE r.id = ? AND p.doctor_id = ? AND r.status = 'pending'", [$rid, $uid])->fetch();
    if (!$req || !in_array($action, ['approve', 'deny'], true)) {
        flash('error', 'Request not found or already handled.');
        redirect('doctor/prescriptions.php');
    }
    if ($action === 'approve') {
        $days = max(7, (int)post_int('days') ?: 30);
        $days = min($days, 180);
        db()->beginTransaction();
        q("UPDATE prescription_requests SET status = 'approved', doctor_note = ? WHERE id = ?", [$note ?: null, $rid]);
        // Renewal = new prescription period starting today (or after the current one ends)
        $start = ($req['end_date'] && $req['end_date'] >= date('Y-m-d')) ? date('Y-m-d', strtotime($req['end_date'] . ' +1 day')) : date('Y-m-d');
        q('INSERT INTO prescriptions (patient_id, doctor_id, medication, dosage, frequency, start_date, end_date, renewable) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
            [$req['patient_id'], $uid, $req['medication'], $req['dosage'], $req['frequency'], $start, date('Y-m-d', strtotime($start . " +$days days"))]);
        db()->commit();
        notify((int)$req['patient_id'], 'prescription', 'Your renewal of ' . $req['medication'] . ' was approved.', 'patient/prescriptions.php');
        audit('Approved renewal request #' . $rid);
        flash('success', 'Renewal approved for ' . $days . ' days.');
    } else {
        q("UPDATE prescription_requests SET status = 'denied', doctor_note = ? WHERE id = ?", [$note ?: null, $rid]);
        notify((int)$req['patient_id'], 'prescription', 'Your renewal of ' . $req['medication'] . ' was not approved. See the doctor\'s note.', 'patient/prescriptions.php');
        audit('Denied renewal request #' . $rid);
        flash('success', 'Renewal request denied.');
    }
    redirect('doctor/prescriptions.php');
}

$pending = q("SELECT r.*, p.medication, p.dosage, p.frequency, p.end_date, u.full_name AS patient
              FROM prescription_requests r JOIN prescriptions p ON p.id = r.prescription_id JOIN users u ON u.id = r.patient_id
              WHERE p.doctor_id = ? AND r.status = 'pending' ORDER BY r.created_at", [$uid])->fetchAll();
$history = q("SELECT r.*, p.medication, u.full_name AS patient
              FROM prescription_requests r JOIN prescriptions p ON p.id = r.prescription_id JOIN users u ON u.id = r.patient_id
              WHERE p.doctor_id = ? AND r.status <> 'pending' ORDER BY r.updated_at DESC, r.id DESC LIMIT 50", [$uid])->fetchAll();

$pageTitle = 'Prescription Renewals';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-rotate"></i> Pending renewal requests</h2></div>
    <?php if (!$pending): ?>
        <div class="empty-state"><i class="fa-solid fa-circle-check fa-3x"></i><p>No pending requests.</p></div>
    <?php else: ?>
        <ul class="list">
        <?php foreach ($pending as $r): ?>
            <li>
                <span class="li-icon"><i class="fa-solid fa-prescription-bottle-medical"></i></span>
                <div class="li-body">
                    <div class="li-title"><?= e($r['patient']) ?> - <?= e($r['medication']) ?> <?= e($r['dosage']) ?></div>
                    <div class="li-meta"><?= e($r['frequency']) ?> &middot; current end date <?= e(fmt_date($r['end_date'])) ?> &middot; requested <?= e(fmt_datetime($r['created_at'])) ?></div>
                    <?php if ($r['patient_note']): ?><p><i class="fa-solid fa-quote-left muted"></i> <?= e($r['patient_note']) ?></p><?php endif; ?>
                    <form method="post" class="btn-row mt">
                        <?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                        <input type="text" name="doctor_note" maxlength="255" placeholder="Note to patient (optional)" aria-label="Note">
                        <select name="days" aria-label="Renewal length"><option value="30">30 days</option><option value="14">14 days</option><option value="60">60 days</option><option value="90">90 days</option></select>
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Approve</button>
                        <button type="submit" name="action" value="deny" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i> Deny</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> Recent decisions</h2></div>
    <?php if (!$history): ?><p class="muted">None yet.</p><?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Patient</th><th>Medication</th><th>Status</th><th>Note</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr><td data-label="Patient"><?= e($h['patient']) ?></td><td data-label="Medication"><?= e($h['medication']) ?></td>
                    <td data-label="Status"><?= badge($h['status']) ?></td><td data-label="Note"><?= e($h['doctor_note'] ?? '-') ?></td>
                    <td data-label="Date"><?= e(fmt_datetime($h['updated_at'] ?? $h['created_at'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
