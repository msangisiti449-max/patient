<?php
/** Patient self-registration. Doctor and admin accounts can only be created by an admin. */
require_once __DIR__ . '/includes/bootstrap.php';

if ($u = current_user()) {
    redirect(dashboard_path($u['role']));
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'dob' => '', 'gender' => ''];

if (is_post()) {
    verify_csrf();
    $old['full_name'] = post_str('full_name', 100);
    $old['email']     = strtolower(post_str('email', 150));
    $old['phone']     = post_str('phone', 20);
    $old['dob']       = post_str('dob', 10);
    $old['gender']    = post_str('gender', 10);
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm  = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    if (!preg_match("/^[\p{L} .'-]{3,100}$/u", $old['full_name'])) {
        $errors[] = 'Full name must be 3-100 letters (spaces, apostrophes and hyphens allowed).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $old['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if ($old['dob'] !== '' && (!valid_date($old['dob']) || $old['dob'] > date('Y-m-d'))) {
        $errors[] = 'Enter a valid date of birth.';
    }
    if ($old['gender'] !== '' && !in_array($old['gender'], ['male', 'female', 'other'], true)) {
        $errors[] = 'Select a valid gender.';
    }
    if (!strong_password($password)) {
        $errors[] = 'Password must be 8-72 characters and include uppercase, lowercase, a number and a symbol.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$errors) {
        $exists = q('SELECT 1 FROM users WHERE email = ?', [$old['email']])->fetchColumn();
        if ($exists) {
            $errors[] = 'An account with this email already exists. Try signing in instead.';
        }
    }
    if (!$errors) {
        q('INSERT INTO users (role, full_name, email, password_hash, phone, dob, gender) VALUES (?, ?, ?, ?, ?, ?, ?)', [
            'patient', $old['full_name'], $old['email'], password_hash($password, PASSWORD_DEFAULT),
            $old['phone'] ?: null, $old['dob'] ?: null, $old['gender'] ?: null,
        ]);
        $newId = (int)db()->lastInsertId();
        audit('Patient registered', $newId);
        notify($newId, 'welcome', 'Welcome to ' . APP_NAME . '. You can now book appointments and message your doctor.', 'patient/dashboard.php');
        flash('success', 'Account created. Please sign in.');
        redirect('login.php');
    }
}

$pageTitle = 'Create account';
$publicLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card wide">
        <div class="card">
            <div class="auth-head">
                <div class="stat-icon teal"><i class="fa-solid fa-user-plus"></i></div>
                <h1>Create your patient account</h1>
                <p>Access your records, appointments and results online</p>
            </div>
            <?php if ($errors): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
                    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('register.php')) ?>" data-match="password,confirm_password">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field full">
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?= e($old['full_name']) ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="150" required autocomplete="email" value="<?= e($old['email']) ?>">
                    </div>
                    <div class="field">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" maxlength="20" value="<?= e($old['phone']) ?>">
                    </div>
                    <div class="field">
                        <label for="dob">Date of birth</label>
                        <input type="date" id="dob" name="dob" value="<?= e($old['dob']) ?>">
                    </div>
                    <div class="field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select</option>
                            <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l): ?>
                                <option value="<?= e($v) ?>" <?= $old['gender'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" id="password" name="password" maxlength="72" required autocomplete="new-password" data-strength="pwStrength">
                            <button type="button" class="toggle-pw" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                        </div>
                        <span id="pwStrength" class="hint">Use 8+ characters with upper, lower, number and symbol.</span>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm password</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" id="confirm_password" name="confirm_password" maxlength="72" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="full">
                        <button type="submit" class="btn btn-accent btn-block"><i class="fa-solid fa-user-plus"></i> Create account</button>
                    </div>
                </div>
            </form>
            <p class="auth-foot">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
