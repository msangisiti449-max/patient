<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');

// Always filtered by the signed-in patient's id: no one can view another patient's records.
$records = q('SELECT r.diagnosis, r.notes, r.record_date, u.full_name AS doctor
              FROM medical_records r LEFT JOIN users u ON u.id = r.doctor_id
              WHERE r.patient_id = ? ORDER BY r.record_date DESC, r.id DESC', [(int)$user['id']])->fetchAll();

$pageTitle = 'Medical Records';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-user"></i> Patient summary</h2></div>
    <div class="form-grid">
        <div><span class="muted">Name</span><br><strong><?= e($user['full_name']) ?></strong></div>
        <div><span class="muted">Date of birth</span><br><strong><?= e(fmt_date($user['dob'])) ?></strong></div>
        <div><span class="muted">Gender</span><br><strong><?= e(ucfirst($user['gender'] ?? '-')) ?></strong></div>
        <div><span class="muted">Phone</span><br><strong><?= e($user['phone'] ?? '-') ?></strong></div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-notes-medical"></i> Medical history &amp; diagnoses</h2></div>
    <?php if (!$records): ?>
        <div class="empty-state"><i class="fa-solid fa-folder-open fa-3x"></i><p>No medical records yet.</p></div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($records as $r): ?>
                <li>
                    <span class="li-icon"><i class="fa-solid fa-stethoscope"></i></span>
                    <div class="li-body">
                        <div class="li-title"><?= e($r['diagnosis']) ?></div>
                        <div class="li-meta"><i class="fa-regular fa-calendar"></i> <?= e(fmt_date($r['record_date'])) ?>
                            &nbsp; <i class="fa-solid fa-user-doctor"></i> <?= e($r['doctor'] ?? 'Unknown') ?></div>
                        <?php if ($r['notes']): ?><p><?= nl2br(e($r['notes'])) ?></p><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
