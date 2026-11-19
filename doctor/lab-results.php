<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('doctor');

$labs = q('SELECT l.*, u.full_name AS patient FROM lab_results l JOIN users u ON u.id = l.patient_id
           WHERE l.doctor_id = ? ORDER BY l.test_date DESC, l.id DESC LIMIT 200', [(int)$user['id']])->fetchAll();

$pageTitle = 'Lab Results';
require __DIR__ . '/../includes/header.php';
?>
<div class="alert alert-info"><i class="fa-solid fa-circle-info"></i>
    <span>To enter a new result, open the patient's chart from <a href="<?= e(url('doctor/patients.php')) ?>">Patients</a>. The patient is notified automatically.</span></div>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-flask"></i> Results I have entered</h2></div>
    <?php if (!$labs): ?>
        <div class="empty-state"><i class="fa-solid fa-vial fa-3x"></i><p>No lab results entered yet.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Date</th><th>Patient</th><th>Test</th><th>Result</th><th>Range</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($labs as $l): ?>
                <tr>
                    <td data-label="Date"><?= e(fmt_date($l['test_date'])) ?></td>
                    <td data-label="Patient"><a href="<?= e(url('doctor/patients.php?id=' . (int)$l['patient_id'])) ?>"><?= e($l['patient']) ?></a></td>
                    <td data-label="Test"><?= e($l['test_name']) ?></td>
                    <td data-label="Result"><?= e($l['result']) ?></td>
                    <td data-label="Range"><?= e($l['normal_range'] ?? '-') ?></td>
                    <td data-label="Status"><?= badge($l['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
