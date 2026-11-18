<?php
/** User management: create accounts, activate/deactivate, unlock, reset passwords. Admin only. */
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
$adminId = (int)$admin['id'];
$errors = [];
$tempPassword = null;
$old = ['role' => 'doctor', 'full_name' => '', 'email' => '', 'phone' => '', 'specialization' => ''];

function temp_password(): string
{
    // Random password that always satisfies the strength rules
    $sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '!@#$%&*?'];
    $chars = [];
    foreach ($sets as $s) {
        $chars[] = $s[random_int(0, strlen($s) - 1)];
    }
    $all = implode('', $sets);
    for ($i = 0; $i < 8; $i++) {
        $chars[] = $all[random_int(0, strlen($all) - 1)];
    }
    shuffle($chars);
    return implode('', $chars);
}

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);
    $id = post_int('id');

    if ($action === 'create') {
        $old['role']           = post_str('role', 10);
        $old['full_name']      = post_str('full_name', 100);
        $old['email']          = strtolower(post_str('email', 150));
        $old['phone']          = post_str('phone', 20);
        $old['specialization'] = post_str('specialization', 100);
        if (!in_array($old['role'], ['patient', 'doctor', 'admin'], true)) {
            $errors[] = 'Select a valid role.';
        }
        if (!preg_match("/^[\p{L} .'-]{3,100}$/u", $old['full_name'])) {
            $errors[] = 'Enter a valid full name.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email.';
        } elseif (q('SELECT 1 FROM users WHERE email = ?', [$old['email']])->fetchColumn()) {
            $errors[] = 'That email is already registered.';
        }
        if ($old['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $old['phone'])) {
            $errors[] = 'Enter a valid phone number.';
        }
        if ($old['role'] === 'doctor' && mb_strlen($old['specialization']) < 2) {
            $errors[] = 'Enter the doctor\'s specialization.';
        }
        if (!$errors) {
            $tempPassword = temp_password();
            db()->beginTransaction();
            q('INSERT INTO users (role, full_name, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)',
                [$old['role'], $old['full_name'], $old['email'], password_hash($tempPassword, PASSWORD_DEFAULT), $old['phone'] ?: null]);
            $newId = (int)db()->lastInsertId();
            if ($old['role'] === 'doctor') {
                q('INSERT INTO doctors_profile (user_id, specialization) VALUES (?, ?)', [$newId, $old['specialization']]);
            }
            db()->commit();
            audit('Created ' . $old['role'] . ' account #' . $newId . ' (' . $old['email'] . ')');
            $_SESSION['temp_pw'] = ['email' => $old['email'], 'pw' => $tempPassword];
            redirect('admin/users.php');
        }
    } elseif ($id === $adminId && in_array($action, ['deactivate', 'reset'], true)) {
        flash('error', 'You cannot do that to your own account here. Use My Profile instead.');
        redirect('admin/users.php');
    } elseif ($target = q('SELECT id, email, full_name FROM users WHERE id = ?', [$id])->fetch()) {
        if ($action === 'deactivate') {
            q('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
            audit('Deactivated user #' . $id);
            flash('success', $target['full_name'] . ' has been deactivated and can no longer sign in.');
        } elseif ($action === 'activate') {
            q('UPDATE users SET is_active = 1 WHERE id = ?', [$id]);
            audit('Activated user #' . $id);
            flash('success', $target['full_name'] . ' has been activated.');
        } elseif ($action === 'unlock') {
            q('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?', [$id]);
            q('DELETE FROM login_attempts WHERE email = ?', [$target['email']]);
            audit('Unlocked user #' . $id);
            flash('success', $target['full_name'] . ' has been unlocked.');
        } elseif ($action === 'reset') {
            $pw = temp_password();
            q('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
            q('DELETE FROM login_attempts WHERE email = ?', [$target['email']]);
            audit('Reset password for user #' . $id);
            $_SESSION['temp_pw'] = ['email' => $target['email'], 'pw' => $pw];
        }
        redirect('admin/users.php');
    }
}

$shownPw = $_SESSION['temp_pw'] ?? null;   // shown once, then forgotten
unset($_SESSION['temp_pw']);

$role = in_array($_GET['role'] ?? '', ['patient', 'doctor', 'admin'], true) ? $_GET['role'] : '';
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$sql = 'SELECT u.id, u.role, u.full_name, u.email, u.phone, u.is_active, u.locked_until, u.last_login, dp.specialization
        FROM users u LEFT JOIN doctors_profile dp ON dp.user_id = u.id WHERE 1 = 1';
$params = [];
if ($role !== '') {
    $sql .= ' AND u.role = ?';
    $params[] = $role;
}
if ($search !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . addcslashes($search, '%_\\') . '%';
    array_push($params, $like, $like);
}
$users = q($sql . ' ORDER BY u.role, u.full_name LIMIT 300', $params)->fetchAll();

$pageTitle = 'User Management';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($shownPw): ?>
    <div class="alert alert-success"><i class="fa-solid fa-key"></i>
        <span>Temporary password for <strong><?= e($shownPw['email']) ?></strong>: <code><?= e($shownPw['pw']) ?></code>
        <br>Give it to the user in person. It will not be shown again. Ask them to change it under My Profile after signing in.</span></div>
<?php endif; ?>

<div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-user-plus"></i> Create account</h2></div>
    <?php if ($errors): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <div class="form-grid">
            <div class="field"><label for="role">Role</label>
                <select id="role" name="role">
                    <?php foreach (['doctor' => 'Doctor', 'patient' => 'Patient', 'admin' => 'Administrator'] as $v => $l): ?>
                        <option value="<?= e($v) ?>" <?= $old['role'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="100" required value="<?= e($old['full_name']) ?>"></div>
            <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="150" required value="<?= e($old['email']) ?>"></div>
            <div class="field"><label for="phone">Phone</label><input type="tel" id="phone" name="phone" maxlength="20" value="<?= e($old['phone']) ?>"></div>
            <div class="field full"><label for="specialization">Specialization (doctors only)</label><input id="specialization" name="specialization" maxlength="100" value="<?= e($old['specialization']) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary mt"><i class="fa-solid fa-user-plus"></i> Create with temporary password</button>
    </form>
</div>

<div class="card">
    <form method="get" class="btn-row">
        <input type="search" name="q" maxlength="100" placeholder="Search name or email" value="<?= e($search) ?>" aria-label="Search users">
        <select name="role" aria-label="Role">
            <option value="">All roles</option>
            <?php foreach (['patient', 'doctor', 'admin'] as $r): ?><option value="<?= e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
    <div class="table-wrap mt"><table class="table-stack">
        <thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Last sign-in</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $isLocked = $u['locked_until'] && strtotime($u['locked_until']) > time(); ?>
            <tr>
                <td data-label="Name"><strong><?= e($u['full_name']) ?></strong><?php if ($u['specialization']): ?><br><small class="muted"><?= e($u['specialization']) ?></small><?php endif; ?></td>
                <td data-label="Role"><?= e(ucfirst($u['role'])) ?></td>
                <td data-label="Email"><?= e($u['email']) ?></td>
                <td data-label="Last sign-in"><?= e(fmt_datetime($u['last_login'])) ?></td>
                <td data-label="Status"><?= badge($u['is_active'] ? ($isLocked ? 'locked' : 'active') : 'inactive') ?></td>
                <td><div class="btn-row">
                    <?php if ((int)$u['id'] !== $adminId): ?>
                        <form method="post" class="inline-form" data-confirm="<?= $u['is_active'] ? 'Deactivate this account?' : 'Activate this account?' ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="action" value="<?= $u['is_active'] ? 'deactivate' : 'activate' ?>">
                            <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid <?= $u['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i> <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <form method="post" class="inline-form" data-confirm="Reset this user's password? A new temporary password will be shown once.">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="reset">
                            <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-key"></i> Reset password</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($isLocked): ?>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="unlock">
                            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-lock-open"></i> Unlock</button>
                        </form>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
