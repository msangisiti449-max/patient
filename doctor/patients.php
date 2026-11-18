<?php
/** Patient list and patient chart (records, labs, prescriptions) for doctors. */
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('doctor');
$uid = (int)$user['id'];
$pid = get_int('id');

function load_patient(int $id): ?array
{
    $p = q("SELECT id, full_name, email, phone, dob, gender, address FROM users WHERE id = ? AND role = 'patient'", [$id])->fetch();
    return $p ?: null;
}

if (is_post()) {
    verify_csrf();
    $pid = post_int('patient_id');
    $patient = load_patient($pid);
    $action = post_str('action', 20);
    $back = 'doctor/patients.php?id=' . $pid;
    if (!$patient) {
        flash('error', 'Patient not found.');
        redirect('doctor/patients.php');
    }

    if ($action === 'record') {
        $diagnosis = post_str('diagnosis', 255);
        $notes = post_str('notes', 5000);
        $date = post_str('record_date', 10);
        if (mb_strlen($diagnosis) < 2 || !valid_date($date) || $date > date('Y-m-d')) {
            flash('error', 'Enter a diagnosis and a valid date (not in the future).');
        } else {
            q('INSERT INTO medical_records (patient_id, doctor_id, diagnosis, notes, record_date) VALUES (?, ?, ?, ?, ?)',
                [$pid, $uid, $diagnosis, $notes ?: null, $date]);
            notify($pid, 'record', 'A new entry was added to your medical records by ' . $user['full_name'] . '.', 'patient/records.php');
            audit('Added medical record for patient #' . $pid);
            flash('success', 'Medical record added.');
        }
    } elseif ($action === 'lab') {
        $test = post_str('test_name', 150);
        $result = post_str('result', 255);
        $range = post_str('normal_range', 100);
        $status = post_str('status', 10);
        $remarks = post_str('remarks', 2000);
        $date = post_str('test_date', 10);
        if (mb_strlen($test) < 2 || $result === '' || !in_array($status, ['normal', 'abnormal', 'critical', 'pending'], true)
            || !valid_date($date) || $date > date('Y-m-d')) {
            flash('error', 'Fill in the test name, result, status and a valid date.');
        } else {
            q('INSERT INTO lab_results (patient_id, doctor_id, test_name, result, normal_range, status, remarks, test_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$pid, $uid, $test, $result, $range ?: null, $status, $remarks ?: null, $date]);
            notify($pid, 'lab', 'New lab result available: ' . $test . '.', 'patient/lab-results.php');
            audit('Added lab result for patient #' . $pid);
            flash('success', 'Lab result saved.');
        }
    } elseif ($action === 'rx') {
        $med = post_str('medication', 150);
        $dosage = post_str('dosage', 100);
        $freq = post_str('frequency', 100);
        $start = post_str('start_date', 10);
        $end = post_str('end_date', 10);
        $renewable = isset($_POST['renewable']) ? 1 : 0;
        if (mb_strlen($med) < 2 || $dosage === '' || $freq === '' || !valid_date($start) || ($end !== '' && (!valid_date($end) || $end < $start))) {
            flash('error', 'Fill in medication, dosage, frequency and valid dates.');
        } else {
            q('INSERT INTO prescriptions (patient_id, doctor_id, medication, dosage, frequency, start_date, end_date, renewable) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$pid, $uid, $med, $dosage, $freq, $start, $end ?: null, $renewable]);
            notify($pid, 'prescription', 'New prescription: ' . $med . ' ' . $dosage . '.', 'patient/prescriptions.php');
            audit('Prescribed ' . $med . ' for patient #' . $pid);
            flash('success', 'Prescription added.');
        }
    }
    redirect($back);
}

$patient = $pid ? load_patient($pid) : null;

if ($patient) {
    audit('Viewed chart of patient #' . $pid);
    $records = q('SELECT r.*, u.full_name AS doctor FROM medical_records r LEFT JOIN users u ON u.id = r.doctor_id WHERE r.patient_id = ? ORDER BY r.record_date DESC', [$pid])->fetchAll();
    $labs = q('SELECT * FROM lab_results WHERE patient_id = ? ORDER BY test_date DESC, id DESC', [$pid])->fetchAll();
    $rxs = q('SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY start_date DESC', [$pid])->fetchAll();
    $pageTitle = 'Patient: ' . $patient['full_name'];
} else {
    $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
    $mine = isset($_GET['mine']);
    $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.dob, u.gender,
              (SELECT MAX(a.appointment_date) FROM appointments a WHERE a.patient_id = u.id AND a.doctor_id = ?) AS last_visit
            FROM users u WHERE u.role = 'patient' AND u.is_active = 1";
    $params = [$uid];
    if ($search !== '') {
        $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $like = '%' . addcslashes($search, '%_\\') . '%';
        array_push($params, $like, $like, $like);
    }
    if ($mine) {
        $sql .= ' AND EXISTS (SELECT 1 FROM appointments a WHERE a.patient_id = u.id AND a.doctor_id = ?)';
        $params[] = $uid;
    }
    $patients = q($sql . ' ORDER BY u.full_name LIMIT 200', $params)->fetchAll();
    $pageTitle = 'Patients';
}
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$patient): ?>
<div class="card">
    <form method="get" class="btn-row">
        <input type="search" name="q" maxlength="100" placeholder="Search by name, email or phone" value="<?= e($search) ?>" aria-label="Search patients">
        <label class="btn-row"><input type="checkbox" name="mine" value="1" <?= $mine ? 'checked' : '' ?>> Only my patients</label>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>
</div>
<div class="card">
    <?php if (!$patients): ?>
        <div class="empty-state"><i class="fa-solid fa-hospital-user fa-3x"></i><p>No patients found.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Date of birth</th><th>Last visit with me</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td data-label="Name"><strong><?= e($p['full_name']) ?></strong></td>
                    <td data-label="Email"><?= e($p['email']) ?></td>
                    <td data-label="Phone"><?= e($p['phone'] ?? '-') ?></td>
                    <td data-label="DOB"><?= e(fmt_date($p['dob'])) ?></td>
                    <td data-label="Last visit"><?= e(fmt_date($p['last_visit'])) ?></td>
                    <td><a class="btn btn-outline btn-sm" href="<?= e(url('doctor/patients.php?id=' . (int)$p['id'])) ?>"><i class="fa-solid fa-folder-open"></i> Open chart</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="card-head">
        <h2><i class="fa-solid fa-user"></i> <?= e($patient['full_name']) ?></h2>
        <div class="btn-row">
            <a class="btn btn-outline btn-sm" href="<?= e(url('doctor/messages.php?to=' . (int)$patient['id'])) ?>"><i class="fa-solid fa-envelope"></i> Message</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('doctor/patients.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="form-grid">
        <div><span class="muted">Email</span><br><?= e($patient['email']) ?></div>
        <div><span class="muted">Phone</span><br><?= e($patient['phone'] ?? '-') ?></div>
        <div><span class="muted">Date of birth</span><br><?= e(fmt_date($patient['dob'])) ?></div>
        <div><span class="muted">Gender</span><br><?= e(ucfirst($patient['gender'] ?? '-')) ?></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-notes-medical"></i> Add diagnosis / record</h2></div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="record"><input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
            <div class="field"><label for="diagnosis">Diagnosis</label><input id="diagnosis" name="diagnosis" maxlength="255" required></div>
            <div class="field"><label for="notes">Clinical notes</label><textarea id="notes" name="notes" maxlength="5000"></textarea></div>
            <div class="field"><label for="record_date">Date</label><input type="date" id="record_date" name="record_date" required value="<?= e(date('Y-m-d')) ?>"></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save record</button>
        </form>
    </div>
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-flask"></i> Enter lab result</h2></div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="lab"><input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
            <div class="form-grid">
                <div class="field full"><label for="test_name">Test name</label><input id="test_name" name="test_name" maxlength="150" required></div>
                <div class="field"><label for="result">Result</label><input id="result" name="result" maxlength="255" required></div>
                <div class="field"><label for="normal_range">Normal range</label><input id="normal_range" name="normal_range" maxlength="100"></div>
                <div class="field"><label for="status">Status</label>
                    <select id="status" name="status"><option value="normal">Normal</option><option value="abnormal">Abnormal</option><option value="critical">Critical</option><option value="pending">Pending</option></select></div>
                <div class="field"><label for="test_date">Test date</label><input type="date" id="test_date" name="test_date" required value="<?= e(date('Y-m-d')) ?>"></div>
                <div class="field full"><label for="remarks">Remarks</label><input id="remarks" name="remarks" maxlength="2000"></div>
            </div>
            <button type="submit" class="btn btn-primary mt"><i class="fa-solid fa-floppy-disk"></i> Save result</button>
        </form>
    </div>
</div>

<div class="card mt">
    <div class="card-head"><h2><i class="fa-solid fa-prescription"></i> New prescription</h2></div>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="rx"><input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
        <div class="form-grid">
            <div class="field"><label for="medication">Medication</label><input id="medication" name="medication" maxlength="150" required></div>
            <div class="field"><label for="dosage">Dosage</label><input id="dosage" name="dosage" maxlength="100" required placeholder="e.g. 500 mg"></div>
            <div class="field"><label for="frequency">Frequency</label><input id="frequency" name="frequency" maxlength="100" required placeholder="e.g. Twice daily"></div>
            <div class="field"><label class="btn-row"><input type="checkbox" name="renewable" value="1" checked> Patient may request renewal online</label></div>
            <div class="field"><label for="start_date">Start date</label><input type="date" id="start_date" name="start_date" required value="<?= e(date('Y-m-d')) ?>"></div>
            <div class="field"><label for="end_date">End date</label><input type="date" id="end_date" name="end_date"></div>
        </div>
        <button type="submit" class="btn btn-primary mt"><i class="fa-solid fa-floppy-disk"></i> Save prescription</button>
    </form>
</div>

<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> History</h2></div>
    <h3>Diagnoses</h3>
    <?php if (!$records): ?><p class="muted">None.</p><?php else: ?>
        <ul class="list"><?php foreach ($records as $r): ?>
            <li><span class="li-icon"><i class="fa-solid fa-stethoscope"></i></span><div class="li-body">
                <div class="li-title"><?= e($r['diagnosis']) ?></div>
                <div class="li-meta"><?= e(fmt_date($r['record_date'])) ?> &middot; <?= e($r['doctor'] ?? '-') ?></div>
                <?php if ($r['notes']): ?><p><?= nl2br(e($r['notes'])) ?></p><?php endif; ?></div></li>
        <?php endforeach; ?></ul>
    <?php endif; ?>
    <h3 class="mt">Lab results</h3>
    <?php if (!$labs): ?><p class="muted">None.</p><?php else: ?>
        <div class="table-wrap"><table class="table-stack"><thead><tr><th>Date</th><th>Test</th><th>Result</th><th>Range</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($labs as $l): ?>
            <tr><td data-label="Date"><?= e(fmt_date($l['test_date'])) ?></td><td data-label="Test"><?= e($l['test_name']) ?></td>
                <td data-label="Result"><?= e($l['result']) ?></td><td data-label="Range"><?= e($l['normal_range'] ?? '-') ?></td><td data-label="Status"><?= badge($l['status']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <h3 class="mt">Prescriptions</h3>
    <?php if (!$rxs): ?><p class="muted">None.</p><?php else: ?>
        <div class="table-wrap"><table class="table-stack"><thead><tr><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Period</th></tr></thead><tbody>
        <?php foreach ($rxs as $p): ?>
            <tr><td data-label="Medication"><?= e($p['medication']) ?></td><td data-label="Dosage"><?= e($p['dosage']) ?></td>
                <td data-label="Frequency"><?= e($p['frequency']) ?></td><td data-label="Period"><?= e(fmt_date($p['start_date'])) ?> - <?= e(fmt_date($p['end_date'])) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
