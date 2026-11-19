<?php
/** Messages sent through the public Contact Us form. Admin only. */
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');

if (is_post()) {
    verify_csrf();
    $id = post_int('id');
    $action = post_str('action', 20);
    if ($action === 'delete') {
        q('DELETE FROM contact_messages WHERE id = ?', [$id]);
        audit('Deleted contact message #' . $id);
        flash('success', 'Message deleted.');
    } elseif (in_array($action, ['read', 'replied', 'new'], true)) {
        q('UPDATE contact_messages SET status = ? WHERE id = ?', [$action, $id]);
        flash('success', 'Message marked as ' . $action . '.');
    }
    redirect('admin/contact-messages.php' . (isset($_GET['status']) ? '?status=' . rawurlencode((string)$_GET['status']) : ''));
}

$status = in_array($_GET['status'] ?? '', ['new', 'read', 'replied'], true) ? $_GET['status'] : '';
$sql = 'SELECT * FROM contact_messages';
$params = [];
if ($status !== '') { $sql .= ' WHERE status = ?'; $params[] = $status; }
$rows = q($sql . ' ORDER BY created_at DESC, id DESC LIMIT 200', $params)->fetchAll();
$newCount = (int)q("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

$pageTitle = 'Contact Messages';
require __DIR__ . '/../includes/header.php';
?>
<div class="tabs">
    <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= e(url('admin/contact-messages.php')) ?>">All</a>
    <a class="<?= $status === 'new' ? 'active' : '' ?>" href="<?= e(url('admin/contact-messages.php?status=new')) ?>">New (<?= $newCount ?>)</a>
    <a class="<?= $status === 'read' ? 'active' : '' ?>" href="<?= e(url('admin/contact-messages.php?status=read')) ?>">Read</a>
    <a class="<?= $status === 'replied' ? 'active' : '' ?>" href="<?= e(url('admin/contact-messages.php?status=replied')) ?>">Replied</a>
</div>
<div class="card">
    <?php if (!$rows): ?>
        <div class="empty-state"><i class="fa-solid fa-inbox fa-3x"></i><p>No contact messages.</p></div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($rows as $m): ?>
                <li class="<?= $m['status'] === 'new' ? 'unread' : '' ?>">
                    <span class="li-icon"><i class="fa-solid fa-envelope"></i></span>
                    <div class="li-body">
                        <div class="li-title"><?= e($m['subject']) ?> - <?= e($m['name']) ?> <?= badge($m['status'] === 'new' ? 'pending' : ($m['status'] === 'replied' ? 'completed' : 'confirmed')) ?></div>
                        <div class="li-meta"><i class="fa-solid fa-envelope"></i> <?= e($m['email']) ?>
                            <?php if ($m['phone']): ?> &middot; <i class="fa-solid fa-phone"></i> <?= e($m['phone']) ?><?php endif; ?>
                            &middot; <i class="fa-regular fa-clock"></i> <?= e(fmt_datetime($m['created_at'])) ?>
                            <?php if ($m['user_id']): ?> &middot; <i class="fa-solid fa-user-check"></i> Portal user<?php endif; ?></div>
                        <div class="msg-body mt"><?= e($m['message']) ?></div>
                        <div class="btn-row mt">
                            <?php foreach (['read' => ['fa-eye', 'Mark read'], 'replied' => ['fa-reply', 'Mark replied']] as $act => [$ic, $lbl]): if ($m['status'] === $act) continue; ?>
                                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="<?= e($act) ?>">
                                    <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid <?= e($ic) ?>"></i> <?= e($lbl) ?></button></form>
                            <?php endforeach; ?>
                            <form method="post" class="inline-form" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-trash-can"></i> Delete</button></form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
