<?php
/** Billing: create bills and record payments. Admin only. */
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
$methods = ['cash' => 'Cash', 'card' => 'Card', 'mobile_money' => 'Mobile money', 'insurance' => 'Insurance', 'bank' => 'Bank transfer'];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);

    if ($action === 'create') {
        $pid = post_int('patient_id');
        $desc = post_str('description', 255);
        $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
        $due = post_str('due_date', 10);
        $isPatient = q("SELECT 1 FROM users WHERE id = ? AND role = 'patient'", [$pid])->fetchColumn();
        if (!$isPatient || mb_strlen($desc) < 2 || $amount === false || $amount <= 0 || $amount > 100000000 || ($due !== '' && !valid_date($due))) {
            flash('error', 'Select a patient and enter a description, a positive amount and a valid due date.');
        } else {
            q('INSERT INTO bills (patient_id, description, amount, due_date) VALUES (?, ?, ?, ?)', [$pid, $desc, round($amount, 2), $due ?: null]);
            notify($pid, 'bill', 'New bill: ' . $desc . ' (' . money($amount) . ').', 'patient/bills.php');
            audit('Created bill for patient #' . $pid . ' amount ' . $amount);
            flash('success', 'Bill created and the patient has been notified.');
        }
    } elseif ($action === 'pay') {
        $bid = post_int('bill_id');
        $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
        $method = post_str('method', 20);
        $bill = q('SELECT b.id, b.patient_id, b.amount, b.description, COALESCE((SELECT SUM(amount) FROM payments WHERE bill_id = b.id), 0) AS paid
                   FROM bills b WHERE b.id = ?', [$bid])->fetch();
        $balance = $bill ? round($bill['amount'] - $bill['paid'], 2) : 0;
        if (!$bill || $amount === false || $amount <= 0 || round($amount, 2) > $balance || !isset($methods[$method])) {
            flash('error', 'Enter a valid payment amount (up to the remaining balance) and method.');
        } else {
            db()->beginTransaction();
            q('INSERT INTO payments (bill_id, amount, method) VALUES (?, ?, ?)', [$bid, round($amount, 2), $method]);
            $newStatus = round($amount, 2) >= $balance ? 'paid' : 'partial';
            q('UPDATE bills SET status = ? WHERE id = ?', [$newStatus, $bid]);
            db()->commit();
            notify((int)$bill['patient_id'], 'bill', 'Payment of ' . money($amount) . ' received for ' . $bill['description'] . '.', 'patient/bills.php');
            audit('Recorded payment of ' . $amount . ' on bill #' . $bid);
            flash('success', 'Payment recorded.');
        }
    }
    redirect('admin/bills.php');
}

$status = in_array($_GET['status'] ?? '', ['unpaid', 'partial', 'paid'], true) ? $_GET['status'] : '';
$sql = 'SELECT b.*, u.full_name AS patient, COALESCE((SELECT SUM(amount) FROM payments WHERE bill_id = b.id), 0) AS paid
        FROM bills b JOIN users u ON u.id = b.patient_id';
$params = [];
if ($status !== '') { $sql .= ' WHERE b.status = ?'; $params[] = $status; }
$bills = q($sql . ' ORDER BY b.created_at DESC LIMIT 300', $params)->fetchAll();
$patients = q("SELECT id, full_name, email FROM users WHERE role = 'patient' AND is_active = 1 ORDER BY full_name")->fetchAll();

$pageTitle = 'Billing';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-file-circle-plus"></i> New bill</h2></div>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <div class="form-grid">
            <div class="field"><label for="patient_id">Patient</label>
                <select id="patient_id" name="patient_id" required><option value="">Select patient</option>
                    <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['full_name'] . ' (' . $p['email'] . ')') ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="description">Description</label><input id="description" name="description" maxlength="255" required placeholder="e.g. Consultation fee"></div>
            <div class="field"><label for="amount">Amount (<?= e(CURRENCY) ?>)</label><input type="number" id="amount" name="amount" min="1" step="0.01" required></div>
            <div class="field"><label for="due_date">Due date</label><input type="date" id="due_date" name="due_date"></div>
        </div>
        <button type="submit" class="btn btn-primary mt"><i class="fa-solid fa-floppy-disk"></i> Create bill</button>
    </form>
</div>

<div class="tabs">
    <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= e(url('admin/bills.php')) ?>">All</a>
    <?php foreach (['unpaid', 'partial', 'paid'] as $s): ?>
        <a class="<?= $status === $s ? 'active' : '' ?>" href="<?= e(url('admin/bills.php?status=' . $s)) ?>"><?= e(ucfirst($s)) ?></a>
    <?php endforeach; ?>
</div>
<div class="card">
    <?php if (!$bills): ?><div class="empty-state"><i class="fa-solid fa-receipt fa-3x"></i><p>No bills.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table-stack">
        <thead><tr><th>Invoice</th><th>Patient</th><th>Description</th><th>Amount</th><th>Balance</th><th>Status</th><th>Record payment</th></tr></thead>
        <tbody>
        <?php foreach ($bills as $b): $bal = $b['amount'] - $b['paid']; ?>
            <tr>
                <td data-label="Invoice">#<?= str_pad((string)(int)$b['id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td data-label="Patient"><?= e($b['patient']) ?></td>
                <td data-label="Description"><?= e($b['description']) ?><br><small class="muted">Due <?= e(fmt_date($b['due_date'])) ?></small></td>
                <td data-label="Amount"><?= e(money($b['amount'])) ?></td>
                <td data-label="Balance"><?= e(money($bal)) ?></td>
                <td data-label="Status"><?= badge($b['status']) ?></td>
                <td><?php if ($bal > 0): ?>
                    <form method="post" class="btn-row">
                        <?= csrf_field() ?><input type="hidden" name="action" value="pay"><input type="hidden" name="bill_id" value="<?= (int)$b['id'] ?>">
                        <input type="number" name="amount" min="0.01" step="0.01" max="<?= e(number_format($bal, 2, '.', '')) ?>" value="<?= e(number_format($bal, 2, '.', '')) ?>" required aria-label="Amount">
                        <select name="method" aria-label="Method"><?php foreach ($methods as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
                        <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-money-bill-wave"></i> Pay</button>
                    </form>
                <?php else: ?><span class="muted"><i class="fa-solid fa-circle-check"></i> Settled</span><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
