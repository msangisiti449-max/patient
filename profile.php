<?php
/** Profile and password change for any signed-in user. */
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_role(['patient', 'doctor', 'admin']);
$uid = (int)$user['id'];
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = post_str('action', 20);

    if ($action === 'profile') {
        $phone   = post_str('phone', 20);
        $address = post_str('address', 255);
        if ($phone !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $phone)) {
            $errors[] = 'Enter a valid phone number.';
        }
        if (!$errors) {
            q('UPDATE users SET phone = ?, address = ? WHERE id = ?', [$phone ?: null, $address ?: null, $uid]);
            audit('Updated profile');
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new     = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        $hash = q('SELECT password_hash FROM users WHERE id = ?', [$uid])->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (!strong_password($new)) {
            $errors[] = 'New password must be 8-72 characters and include uppercase, lowercase, a number and a symbol.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            audit('Changed password');
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'My Profile';
require __DIR__ . '/includes/header.php';
?>
<?php if ($errors): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-id-card"></i> Personal details</h2></div>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <div class="form-grid">
                <div class="field full"><label>Full name</label><input type="text" value="<?= e($user['full_name']) ?>" disabled></div>
                <div class="field full"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled></div>
                <div class="field"><label>Date of birth</label><input type="text" value="<?= e(fmt_date($user['dob'])) ?>" disabled></div>
                <div class="field"><label>Gender</label><input type="text" value="<?= e(ucfirst($user['gender'] ?? '-')) ?>" disabled></div>
                <div class="field full"><label for="phone">Phone</label><input type="tel" id="phone" name="phone" maxlength="20" value="<?= e($user['phone']) ?>"></div>
                <div class="field full"><label for="address">Address</label><input type="text" id="address" name="address" maxlength="255" value="<?= e($user['address']) ?>"></div>
            </div>
            <p class="hint mt">To change your name, email or date of birth, contact the hospital administrator.</p>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
        </form>
    </div>
    <div class="card">
        <div class="card-head"><h2><i class="fa-solid fa-key"></i> Change password</h2></div>
        <form method="post" data-match="new_password,confirm_password">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <div class="field"><label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" maxlength="72" required autocomplete="current-password"></div>
            <div class="field"><label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" maxlength="72" required autocomplete="new-password" data-strength="pwStrength">
                <span id="pwStrength" class="hint">Use 8+ characters with upper, lower, number and symbol.</span></div>
            <div class="field"><label for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" maxlength="72" required autocomplete="new-password"></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-lock"></i> Update password</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
