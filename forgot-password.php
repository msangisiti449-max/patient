<?php
/**
 * Password reset request. The system runs offline (no email), so the request is sent
 * to administrators, who verify the patient's identity and set a temporary password.
 * The response is the same whether or not the email exists (no account enumeration).
 */
require_once __DIR__ . '/includes/bootstrap.php';

$sent = false;
if (is_post()) {
    verify_csrf();
    $email = strtolower(post_str('email', 150));
    $_SESSION['reset_requests'] = ($_SESSION['reset_requests'] ?? 0) + 1;

    if ($_SESSION['reset_requests'] <= 3 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user = q("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1", [$email])->fetch();
        if ($user) {
            $admins = q("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll();
            foreach ($admins as $a) {
                notify((int)$a['id'], 'reset', 'Password reset requested by ' . $user['full_name'] . ' (' . $email . ').',
                    'admin/users.php?q=' . rawurlencode($email));
            }
            audit('Password reset requested', (int)$user['id']);
        }
    }
    $sent = true;
}

$pageTitle = 'Forgot password';
$publicLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="card">
            <div class="auth-head">
                <div class="stat-icon amber"><i class="fa-solid fa-unlock-keyhole"></i></div>
                <h1>Reset your password</h1>
                <p>We will notify the hospital administrator</p>
            </div>
            <?php if ($sent): ?>
                <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i>
                    <span>If an account exists for that email, the administrator has been notified. Please visit or call the hospital reception with your ID to receive a temporary password.</span>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('forgot-password.php')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" maxlength="150" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-paper-plane"></i> Send request</button>
            </form>
            <p class="auth-foot"><a href="<?= e(url('login.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back to sign in</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
