<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Privacy & Security';
$publicLayout = true;
require __DIR__ . '/includes/header.php';

$protections = [
    ['fa-key',             'Encrypted passwords',    'Your password is never stored as plain text. It is encrypted with a one-way algorithm, so not even staff can read it.'],
    ['fa-user-lock',       'Account lockout',        'After 5 wrong password attempts the account is locked for 15 minutes, stopping anyone trying to guess it.'],
    ['fa-hourglass-end',   'Automatic sign-out',     'If you are inactive for 30 minutes you are signed out automatically, protecting you on shared computers.'],
    ['fa-user-shield',     'Strict access rules',    'Patients can only see their own records. Doctors and administrators only see what their role requires.'],
    ['fa-cookie-bite',     'Secure session cookies', 'Your sign-in cookie cannot be read by scripts or sent by other websites, preventing session theft.'],
    ['fa-clipboard-check', 'Audit log',              'Sign-ins, failed attempts and access to patient charts are recorded so any misuse can be traced.'],
];
?>
<section class="page-hero">
    <h1>Privacy &amp; Security</h1>
    <p>How we protect your personal and medical information.</p>
</section>

<section class="section">
    <div class="feature-grid">
        <?php foreach ($protections as [$icon, $title, $text]): ?>
            <div class="feature">
                <div class="stat-icon blue"><i class="fa-solid <?= e($icon) ?>"></i></div>
                <h3><?= e($title) ?></h3>
                <p><?= e($text) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section-alt">
    <div class="prose">
        <h2 class="left">What information we keep</h2>
        <p>We store the details you give when you register (name, email, phone, date of birth and gender) and the information your care team adds: diagnoses, lab results, prescriptions, appointments, messages and bills.</p>
        <h2 class="left">How we use it</h2>
        <p>Your information is used only to provide your care and run the portal services. It is not sold or shared with advertisers.</p>
        <h2 class="left">Your responsibilities</h2>
        <ul class="check-list">
            <li><i class="fa-solid fa-check"></i>Use a strong password and never share it.</li>
            <li><i class="fa-solid fa-check"></i>Always click Sign out when using a shared or public computer.</li>
            <li><i class="fa-solid fa-check"></i>Contact us immediately if you notice activity you do not recognise.</li>
        </ul>
        <p>Questions about your data? <a href="<?= e(url('contact.php')) ?>">Contact us</a>.</p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
