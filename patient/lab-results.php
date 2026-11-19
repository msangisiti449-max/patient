<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');

$labs = q('SELECT l.*, u.full_name AS doctor FROM lab_results l LEFT JOIN users u ON u.id = l.doctor_id
           WHERE l.patient_id = ? ORDER BY l.test_date DESC, l.id DESC', [(int)$user['id']])->fetchAll();

$pageTitle = 'Lab Results';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-flask"></i> Laboratory &amp; examination results</h2></div>
    <?php if (!$labs): ?>
        <div class="empty-state"><i class="fa-solid fa-vial fa-3x"></i><p>No laboratory results yet.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Date</th><th>Test</th><th>Result</th><th>Normal range</th><th>Status</th><th>Doctor's remarks</th></tr></thead>
            <tbody>
            <?php foreach ($labs as $l): ?>
                <tr>
                    <td data-label="Date"><?= e(fmt_date($l['test_date'])) ?></td>
                    <td data-label="Test"><strong><?= e($l['test_name']) ?></strong></td>
                    <td data-label="Result"><?= e($l['result']) ?></td>
                    <td data-label="Normal range"><?= e($l['normal_range'] ?? '-') ?></td>
                    <td data-label="Status"><?= badge($l['status']) ?></td>
                    <td data-label="Remarks"><?= e($l['remarks'] ?? '-') ?><br><small class="muted"><?= e($l['doctor'] ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<div class="alert alert-info"><i class="fa-solid fa-circle-info"></i>
    <span>Results marked Abnormal or Critical will be discussed by your doctor. Use Messages if you have questions.</span></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
