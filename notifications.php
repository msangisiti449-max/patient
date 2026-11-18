<?php
/** Notifications and reminders for any signed-in user. */
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_role(['patient', 'doctor', 'admin']);
$uid = (int)$user['id'];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);
    if ($action === 'read_all') {
        q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$uid]);
        flash('success', 'All notifications marked as read.');
    } elseif ($action === 'open') {
        $id = post_int('id');
        // user_id in WHERE: a user can only touch their own notifications
        $n = q('SELECT link FROM notifications WHERE id = ? AND user_id = ?', [$id, $uid])->fetch();
        if ($n) {
            q('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $uid]);
            // Only follow internal relative links
            if ($n['link'] && preg_match('#^[a-z0-9/_\-.]+\.php(\?[a-zA-Z0-9=&%_.@\-]*)?$#', $n['link'])) {
                redirect($n['link']);
            }
        }
    } elseif ($action === 'clear') {
        q('DELETE FROM notifications WHERE user_id = ? AND is_read = 1', [$uid]);
        flash('success', 'Read notifications cleared.');
    }
    redirect('notifications.php');
}

$items = q('SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100', [$uid])->fetchAll();
$icons = [
    'appointment' => 'fa-calendar-check', 'medication' => 'fa-pills', 'bill' => 'fa-file-invoice-dollar',
    'lab' => 'fa-flask', 'message' => 'fa-envelope', 'prescription' => 'fa-prescription-bottle-medical',
    'record' => 'fa-notes-medical', 'reset' => 'fa-unlock-keyhole', 'welcome' => 'fa-hand-holding-heart',
];

$pageTitle = $user['role'] === 'patient' ? 'Reminders & Notifications' : 'Notifications';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <h2><i class="fa-solid fa-bell"></i> Notifications</h2>
        <div class="btn-row">
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="read_all">
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-check-double"></i> Mark all read</button></form>
            <form method="post" class="inline-form" data-confirm="Delete all read notifications?"><?= csrf_field() ?><input type="hidden" name="action" value="clear">
                <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-trash-can"></i> Clear read</button></form>
        </div>
    </div>
    <?php if (!$items): ?>
        <div class="empty-state"><i class="fa-regular fa-bell-slash fa-3x"></i><p>You have no notifications.</p></div>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($items as $n): ?>
                <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
                    <span class="li-icon"><i class="fa-solid <?= e($icons[$n['type']] ?? 'fa-circle-info') ?>"></i></span>
                    <div class="li-body">
                        <div class="li-title"><?= e($n['message']) ?></div>
                        <div class="li-meta"><i class="fa-regular fa-clock"></i> <?= e(fmt_datetime($n['created_at'])) ?></div>
                    </div>
                    <?php if ($n['link']): ?>
                        <form method="post" class="inline-form"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="open"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                            <button class="btn btn-outline btn-sm" type="submit">View <i class="fa-solid fa-arrow-right"></i></button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
