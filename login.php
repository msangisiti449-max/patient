<?php
/**
 * Secure sign-in.
 *  - CSRF token required
 *  - Prepared statements only (SQL injection safe)
 *  - password_verify() against bcrypt hashes
 *  - Lockout per account (by email, so it also works for unknown emails) and throttling per IP
 *  - Generic error messages (no hint whether the email exists)
 *  - Session id regenerated on success (session fixation safe)
 */
require_once __DIR__ . '/includes/bootstrap.php';

if ($u = current_user()) {
    redirect(dashboard_path($u['role']));
}

$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email    = strtolower(post_str('email', 150));
    $password = $_POST['password'] ?? '';
    $password = is_string($password) ? $password : '';
    $ip       = client_ip();

    $ipAttempts = (int)q('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
        [$ip, IP_WINDOW_MINUTES])->fetchColumn();
    $emailAttempts = (int)q('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
        [$email, LOCKOUT_MINUTES])->fetchColumn();

    $user = null;
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user = q('SELECT id, role, password_hash, is_active, locked_until FROM users WHERE email = ? LIMIT 1', [$email])->fetch();
    }
    $locked = $emailAttempts >= MAX_LOGIN_ATTEMPTS
        || ($user && $user['locked_until'] && strtotime($user['locked_until']) > time());

    if ($ipAttempts >= IP_MAX_ATTEMPTS || $locked) {
        $error = 'Too many failed sign-in attempts. Please wait ' . LOCKOUT_MINUTES . ' minutes and try again.';
        audit('Blocked login attempt (lockout) for ' . $email);
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        // Always run password_verify, even for unknown emails, so response time does not reveal which accounts exist.
        $hash  = $user['password_hash'] ?? '$2y$10$/yKotVsRphqRgwIIlDQIneGvFb0DhOMZiKsC/7QKC/bKTIZzKIdeq';
        $valid = password_verify($password, $hash);

        if ($user && $valid && (int)$user['is_active'] === 1) {
            q('DELETE FROM login_attempts WHERE email = ?', [$email]);
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            q('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?', [$user['id']]);
            login_user($user);
            audit('Signed in', (int)$user['id']);
            redirect(dashboard_path($user['role']));
        }

        q('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [$ip, $email]);
        if ($user) {
            // MySQL evaluates SET left to right: both expressions see the old failed_attempts value.
            q('UPDATE users SET
                    locked_until    = IF(failed_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until),
                    failed_attempts = IF(failed_attempts + 1 >= ?, 0, failed_attempts + 1)
               WHERE id = ?', [MAX_LOGIN_ATTEMPTS, LOCKOUT_MINUTES, MAX_LOGIN_ATTEMPTS, $user['id']]);
        }
        audit('Failed sign-in for ' . $email, $user ? (int)$user['id'] : null);
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Sign in';
$publicLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="card">
            <div class="auth-head">
                <div class="stat-icon blue"><i class="fa-solid fa-user-lock"></i></div>
                <h1>Welcome back</h1>
                <p>Sign in to access your health portal</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('login.php')) ?>" autocomplete="on" novalidate>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" maxlength="150" required autofocus autocomplete="username" value="<?= e($email) ?>">
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" id="password" name="password" maxlength="72" required autocomplete="current-password">
                        <button type="button" class="toggle-pw" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="field text-right"><a href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a></div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Sign in</button>
            </form>
            <p class="auth-foot">New patient? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
            <p class="secure-note"><i class="fa-solid fa-shield-halved"></i> Protected connection. Never share your password.</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
