<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Home';
$publicLayout = true;
require __DIR__ . '/includes/header.php';

$features = [
    ['fa-notes-medical',               'blue',  'Medical records',      'See your medical history and diagnoses whenever you need them.'],
    ['fa-calendar-check',              'teal',  'Book appointments',    'Schedule hospital visits online without waiting in line.'],
    ['fa-flask',                       'amber', 'Laboratory results',   'Check blood tests and other examination results as soon as they are ready.'],
    ['fa-prescription-bottle-medical', 'red',   'Prescription renewals','Request renewals of your regular medication with one click.'],
    ['fa-comments',                    'blue',  'Message your doctor',  'Send secure messages to your healthcare providers.'],
    ['fa-file-invoice-dollar',         'teal',  'Bills & payments',     'View your medical bills and full payment history.'],
    ['fa-bell',                        'amber', 'Reminders',            'Get appointment, medication and bill reminders automatically.'],
    ['fa-shield-halved',               'red',   'Secure by design',     'Your data is protected with encrypted passwords and secure sessions.'],
];
$advantages = [
    ['fa-clock',              'Saves time.'],
    ['fa-comments',           'Improves communication between patients and doctors.'],
    ['fa-folder-open',        'Provides easy access to medical information.'],
    ['fa-house-medical',      'Reduces unnecessary hospital visits.'],
    ['fa-hand-holding-heart', 'Improves patient participation in healthcare.'],
];
?>
<section class="hero">
    <h1>Your health, in your hands</h1>
    <p>CarePoint connects you with your hospital. View records, book visits, check lab results and talk to your doctor from anywhere.</p>
    <div class="btn-row">
        <a class="btn btn-accent" href="<?= e(url('register.php')) ?>"><i class="fa-solid fa-user-plus"></i> Get started</a>
        <a class="btn btn-outline" href="<?= e(url('login.php')) ?>"><i class="fa-solid fa-right-to-bracket"></i> Sign in</a>
    </div>
</section>

<section class="section">
    <h2>Everything you need in one place</h2>
    <p class="lead">A secure portal that puts your medical information and hospital services at your fingertips.</p>
    <div class="feature-grid">
        <?php foreach ($features as [$icon, $color, $title, $text]): ?>
            <div class="feature">
                <div class="stat-icon <?= e($color) ?>"><i class="fa-solid <?= e($icon) ?>"></i></div>
                <h3><?= e($title) ?></h3>
                <p><?= e($text) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section-alt">
    <h2>Advantages of a patient portal</h2>
    <p class="lead">Why patients and hospitals benefit.</p>
    <ul class="adv-list">
        <?php foreach ($advantages as [$icon, $text]): ?>
            <li><i class="fa-solid <?= e($icon) ?>"></i><?= e($text) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
