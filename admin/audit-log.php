<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');

$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$sql = 'SELECT a.action, a.ip, a.created_at, u.full_name, u.role FROM audit_log a LEFT JOIN users u ON u.id = a.user_id';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE a.action LIKE ? OR u.full_name LIKE ? OR a.ip LIKE ?';
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $params = [$like, $like, $like];
}
$rows = q($sql . ' ORDER BY a.id DESC LIMIT 500', $params)->fetchAll();

$pageTitle = 'Audit Log';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <form method="get" class="btn-row">
        <input type="search" name="q" maxlength="100" placeholder="Search action, user or IP" value="<?= e($search) ?>" aria-label="Search log">
        <button type="submit" class="btn btn-outline"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>
</div>
<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-clipboard-list"></i> Security &amp; activity log (latest 500)</h2></div>
    <div class="table-wrap"><table class="table-stack">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>IP address</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="Time"><?= e(fmt_datetime($r['created_at'])) ?></td>
                <td data-label="User"><?= e($r['full_name'] ?? 'Guest') ?><?php if ($r['role']): ?> <small class="muted">(<?= e($r['role']) ?>)</small><?php endif; ?></td>
                <td data-label="Action"><?= e($r['action']) ?></td>
                <td data-label="IP"><?= e($r['ip']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
