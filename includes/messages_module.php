<?php
/**
 * Shared secure messaging between patients and doctors.
 * Included by patient/messages.php and doctor/messages.php after require_role().
 * Expects: $user (current user), $selfPath (e.g. 'patient/messages.php'), $peerRole ('doctor' or 'patient').
 */
$uid = (int)$user['id'];
$errors = [];
$draft = ['to' => get_int('to'), 'subject' => '', 'body' => ''];

if (isset($_GET['reply'])) {
    $orig = q('SELECT sender_id, subject FROM messages WHERE id = ? AND receiver_id = ?', [get_int('reply'), $uid])->fetch();
    if ($orig) {
        $draft['to'] = (int)$orig['sender_id'];
        $draft['subject'] = str_starts_with($orig['subject'], 'Re: ') ? $orig['subject'] : 'Re: ' . $orig['subject'];
    }
}

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);
    if ($action === 'send') {
        $draft['to']      = post_int('to');
        $draft['subject'] = post_str('subject', 150);
        $draft['body']    = post_str('body', 5000);
        $peer = q('SELECT id, full_name FROM users WHERE id = ? AND role = ? AND is_active = 1', [$draft['to'], $peerRole])->fetch();
        if (!$peer) {
            $errors[] = 'Select a valid recipient.';
        }
        if (mb_strlen($draft['subject']) < 2) {
            $errors[] = 'Enter a subject.';
        }
        if (mb_strlen($draft['body']) < 2) {
            $errors[] = 'Enter a message.';
        }
        if (!$errors) {
            q('INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)',
                [$uid, $peer['id'], $draft['subject'], $draft['body']]);
            $peerPath = $peerRole === 'doctor' ? 'doctor/messages.php' : 'patient/messages.php';
            notify((int)$peer['id'], 'message', 'New message from ' . $user['full_name'] . ': ' . $draft['subject'], $peerPath);
            flash('success', 'Message sent to ' . $peer['full_name'] . '.');
            redirect($selfPath . '?box=sent');
        }
    } elseif ($action === 'delete') {
        // Only the receiver can delete from their inbox
        q('DELETE FROM messages WHERE id = ? AND receiver_id = ?', [post_int('id'), $uid]);
        flash('success', 'Message deleted.');
        redirect($selfPath);
    }
}

$box = in_array($_GET['box'] ?? 'inbox', ['inbox', 'sent', 'compose'], true) ? ($_GET['box'] ?? 'inbox') : 'inbox';
if ($errors || isset($_GET['reply']) || $draft['to']) {
    $box = 'compose';
}
$viewId = get_int('view');
$view = null;
if ($viewId) {
    // Can only open a message you sent or received
    $view = q('SELECT m.*, s.full_name AS sender, r.full_name AS receiver FROM messages m
               JOIN users s ON s.id = m.sender_id JOIN users r ON r.id = m.receiver_id
               WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)', [$viewId, $uid, $uid])->fetch();
    if ($view && (int)$view['receiver_id'] === $uid && !$view['is_read']) {
        q('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?', [$viewId, $uid]);
    }
}

if ($box === 'sent') {
    $list = q('SELECT m.id, m.subject, m.sent_at, m.is_read, u.full_name AS peer FROM messages m JOIN users u ON u.id = m.receiver_id
               WHERE m.sender_id = ? ORDER BY m.sent_at DESC LIMIT 100', [$uid])->fetchAll();
} else {
    $list = q('SELECT m.id, m.subject, m.sent_at, m.is_read, u.full_name AS peer FROM messages m JOIN users u ON u.id = m.sender_id
               WHERE m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 100', [$uid])->fetchAll();
}
$peers = q("SELECT u.id, u.full_name, dp.specialization FROM users u LEFT JOIN doctors_profile dp ON dp.user_id = u.id
            WHERE u.role = ? AND u.is_active = 1 ORDER BY u.full_name", [$peerRole])->fetchAll();

$pageTitle = 'Messages';
require __DIR__ . '/header.php';
?>
<div class="tabs">
    <a class="<?= $box === 'inbox' && !$view ? 'active' : '' ?>" href="<?= e(url($selfPath)) ?>"><i class="fa-solid fa-inbox"></i> Inbox</a>
    <a class="<?= $box === 'sent' && !$view ? 'active' : '' ?>" href="<?= e(url($selfPath . '?box=sent')) ?>"><i class="fa-solid fa-paper-plane"></i> Sent</a>
    <a class="<?= $box === 'compose' ? 'active' : '' ?>" href="<?= e(url($selfPath . '?box=compose')) ?>"><i class="fa-solid fa-pen-to-square"></i> New message</a>
</div>

<?php if ($view): ?>
    <div class="card">
        <div class="card-head">
            <h2><i class="fa-solid fa-envelope-open-text"></i> <?= e($view['subject']) ?></h2>
            <div class="btn-row">
                <?php if ((int)$view['receiver_id'] === $uid): ?>
                    <a class="btn btn-primary btn-sm" href="<?= e(url($selfPath . '?reply=' . (int)$view['id'])) ?>"><i class="fa-solid fa-reply"></i> Reply</a>
                    <form method="post" class="inline-form" data-confirm="Delete this message?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$view['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-trash-can"></i> Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <p class="muted"><strong>From:</strong> <?= e($view['sender']) ?> &nbsp; <strong>To:</strong> <?= e($view['receiver']) ?> &nbsp; <i class="fa-regular fa-clock"></i> <?= e(fmt_datetime($view['sent_at'])) ?></p>
        <div class="msg-body"><?= e($view['body']) ?></div>
    </div>
<?php elseif ($box === 'compose'): ?>
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-pen-to-square"></i> New message</h2></div>
        <?php if ($errors): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
                <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url($selfPath)) ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="send">
            <div class="field">
                <label for="to">To</label>
                <select id="to" name="to" required>
                    <option value="">Select <?= e($peerRole) ?></option>
                    <?php foreach ($peers as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $draft['to'] === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= e($p['full_name'] . ($p['specialization'] ? ' - ' . $p['specialization'] : '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" maxlength="150" required value="<?= e($draft['subject']) ?>"></div>
            <div class="field"><label for="body">Message</label>
                <textarea id="body" name="body" maxlength="5000" required><?= e($draft['body']) ?></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send message</button>
        </form>
        <?php if ($peerRole === 'doctor'): ?>
            <p class="hint mt"><i class="fa-solid fa-triangle-exclamation"></i> For emergencies, do not use messages. Call the hospital or go to the emergency department.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <?php if (!$list): ?>
            <div class="empty-state"><i class="fa-regular fa-envelope fa-3x"></i><p>No messages.</p></div>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($list as $m): ?>
                    <li class="<?= $box === 'inbox' && !$m['is_read'] ? 'unread' : '' ?>">
                        <span class="li-icon"><i class="fa-solid <?= $box === 'sent' ? 'fa-paper-plane' : 'fa-envelope' ?>"></i></span>
                        <div class="li-body">
                            <div class="li-title"><a href="<?= e(url($selfPath . '?view=' . (int)$m['id'])) ?>"><?= e($m['subject']) ?></a></div>
                            <div class="li-meta"><?= $box === 'sent' ? 'To' : 'From' ?> <?= e($m['peer']) ?> &middot; <?= e(fmt_datetime($m['sent_at'])) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
