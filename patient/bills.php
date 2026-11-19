<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');
$uid = (int)$user['id'];

$bills = q('SELECT b.*, COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.bill_id = b.id), 0) AS paid
            FROM bills b WHERE b.patient_id = ? ORDER BY b.created_at DESC', [$uid])->fetchAll();
$payments = q('SELECT p.amount, p.method, p.paid_at, b.description FROM payments p JOIN bills b ON b.id = p.bill_id
               WHERE b.patient_id = ? ORDER BY p.paid_at DESC', [$uid])->fetchAll();
$total = array_sum(array_column($bills, 'amount'));
$paid = array_sum(array_column($bills, 'paid'));

$pageTitle = 'Bills & Payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <div class="stat"><span class="stat-icon blue"><i class="fa-solid fa-receipt"></i></span>
        <span><span class="stat-value"><?= e(money($total)) ?></span><br><span class="stat-label">Total billed</span></span></div>
    <div class="stat"><span class="stat-icon teal"><i class="fa-solid fa-circle-check"></i></span>
        <span><span class="stat-value"><?= e(money($paid)) ?></span><br><span class="stat-label">Total paid</span></span></div>
    <div class="stat"><span class="stat-icon red"><i class="fa-solid fa-scale-unbalanced"></i></span>
        <span><span class="stat-value"><?= e(money($total - $paid)) ?></span><br><span class="stat-label">Balance due</span></span></div>
</div>

<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-file-invoice-dollar"></i> Medical bills</h2></div>
    <?php if (!$bills): ?>
        <div class="empty-state"><i class="fa-solid fa-receipt fa-3x"></i><p>No bills.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Invoice</th><th>Description</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($bills as $b): ?>
                <tr>
                    <td data-label="Invoice">#<?= str_pad((string)(int)$b['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td data-label="Description"><?= e($b['description']) ?></td>
                    <td data-label="Amount"><?= e(money($b['amount'])) ?></td>
                    <td data-label="Paid"><?= e(money($b['paid'])) ?></td>
                    <td data-label="Balance"><?= e(money($b['amount'] - $b['paid'])) ?></td>
                    <td data-label="Due date"><?= e(fmt_date($b['due_date'])) ?></td>
                    <td data-label="Status"><?= badge($b['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-money-bill-wave"></i> Payment history</h2></div>
    <?php if (!$payments): ?>
        <p class="muted">No payments recorded.</p>
    <?php else: ?>
        <div class="table-wrap"><table class="table-stack">
            <thead><tr><th>Date</th><th>For</th><th>Method</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td data-label="Date"><?= e(fmt_datetime($p['paid_at'])) ?></td>
                    <td data-label="For"><?= e($p['description']) ?></td>
                    <td data-label="Method"><?= e(ucwords(str_replace('_', ' ', $p['method']))) ?></td>
                    <td data-label="Amount"><?= e(money($p['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<div class="alert alert-info"><i class="fa-solid fa-circle-info"></i><span>Payments are made at the hospital cashier, by mobile money or through insurance. Your bill status updates once the payment is recorded.</span></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
