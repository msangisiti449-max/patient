<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/site_content.php';
$pageTitle = 'Home';
$publicLayout = true;

$doctorCount = (int)q("SELECT COUNT(*) FROM users WHERE role = 'doctor' AND is_active = 1")->fetchColumn();

require __DIR__ . '/includes/header.php';

$steps = [
    ['fa-user-plus',        'Create your account', 'Register online in two minutes with your name, email and a strong password.'],
    ['fa-calendar-check',   'Book a visit',        'Choose a doctor, a date and a time that suits you. You are notified once it is confirmed.'],
    ['fa-flask',            'See your results',    'Lab results, diagnoses and prescriptions appear in your portal as soon as your doctor enters them.'],
    ['fa-comments',         'Stay connected',      'Message your doctor, request refills, pay attention to reminders and track your bills.'],
];
$advantages = [
    ['fa-clock',              'Saves time',                 'No queues to book or ask questions. Do it from home, any time of day.'],
    ['fa-comments',           'Better communication',       'Reach your doctor directly with secure messages and get written answers.'],
    ['fa-folder-open',        'Easy access to information', 'Your history, results and prescriptions in one place, always up to date.'],
    ['fa-house-medical',      'Fewer unnecessary visits',   'Get results and renew prescriptions online instead of travelling to the hospital.'],
    ['fa-hand-holding-heart', 'More involved in your care', 'Understand your health, follow your treatment and never miss a dose or visit.'],
];
?>
<section class="hero">
    <span class="hero-tag"><i class="fa-solid fa-shield-halved"></i> Secure online patient portal</span>
    <h1>Your health, in your hands</h1>
    <p>CarePoint connects you with <?= e(HOSPITAL_NAME) ?>. View records, book visits, check lab results and talk to your doctor from anywhere.</p>
    <div class="btn-row">
        <a class="btn btn-accent" href="<?= e(url('register.php')) ?>"><i class="fa-solid fa-user-plus"></i> Get started</a>
        <a class="btn btn-outline" href="<?= e(url('services.php')) ?>"><i class="fa-solid fa-circle-info"></i> Explore services</a>
    </div>
    <div class="hero-stats">
        <div><strong>24/7</strong><span>Online access</span></div>
        <div><strong><?= count(SERVICES) ?></strong><span>Patient services</span></div>
        <div><strong><?= $doctorCount ?></strong><span>Doctors online</span></div>
        <div><strong>100%</strong><span>Private &amp; encrypted</span></div>
    </div>
</section>

<section class="section" id="services">
    <span class="eyebrow">What we offer</span>
    <h2>Services available in your portal</h2>
    <p class="lead">Everything you need to manage your care with <?= e(HOSPITAL_NAME) ?>, explained simply.</p>
    <div class="service-grid">
        <?php foreach (SERVICES as $slug => $s): ?>
            <article class="service-card">
                <div class="stat-icon <?= e($s['color']) ?>"><i class="fa-solid <?= e($s['icon']) ?>"></i></div>
                <h3><?= e($s['title']) ?></h3>
                <p><?= e($s['summary']) ?></p>
                <ul class="check-list">
                    <?php foreach (array_slice($s['points'], 0, 3) as $pt): ?>
                        <li><i class="fa-solid fa-check"></i><?= e($pt) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="card-link" href="<?= e(url('services.php#' . $slug)) ?>">Learn more <i class="fa-solid fa-arrow-right"></i></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section-alt" id="how-it-works">
    <span class="eyebrow">Getting started</span>
    <h2>How it works</h2>
    <p class="lead">Four simple steps from sign-up to managing your health online.</p>
    <ol class="steps">
        <?php foreach ($steps as $i => [$icon, $title, $text]): ?>
            <li class="step">
                <span class="step-num"><?= $i + 1 ?></span>
                <div class="stat-icon blue"><i class="fa-solid <?= e($icon) ?>"></i></div>
                <h3><?= e($title) ?></h3>
                <p><?= e($text) ?></p>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="section" id="advantages">
    <span class="eyebrow">Why use the portal</span>
    <h2>Advantages of a patient portal</h2>
    <p class="lead">Why patients and the hospital both benefit.</p>
    <div class="adv-grid">
        <?php foreach ($advantages as [$icon, $title, $text]): ?>
            <div class="adv-item">
                <i class="fa-solid <?= e($icon) ?>"></i>
                <div><strong><?= e($title) ?></strong><p><?= e($text) ?></p></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section-dark">
    <div class="split">
        <div>
            <span class="eyebrow light">Security first</span>
            <h2 class="left">Your medical data stays private</h2>
            <p>Your information is protected with encrypted passwords, secure sessions that expire automatically and strict access rules. Only you and your care team can see your records.</p>
            <a class="btn btn-accent" href="<?= e(url('privacy.php')) ?>"><i class="fa-solid fa-lock"></i> How we protect you</a>
        </div>
        <ul class="security-list">
            <li><i class="fa-solid fa-key"></i> Encrypted passwords and account lockout</li>
            <li><i class="fa-solid fa-user-shield"></i> Only you and your doctors see your records</li>
            <li><i class="fa-solid fa-hourglass-end"></i> Automatic sign-out after inactivity</li>
            <li><i class="fa-solid fa-clipboard-check"></i> Every access is logged and audited</li>
        </ul>
    </div>
</section>

<section class="section" id="faq">
    <span class="eyebrow">Questions</span>
    <h2>Frequently asked questions</h2>
    <p class="lead">Can't find your answer? <a href="<?= e(url('contact.php')) ?>">Contact us</a>.</p>
    <div class="faq">
        <?php foreach (FAQS as $i => [$q, $a]): ?>
            <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
                <summary><span><?= e($q) ?></span><i class="fa-solid fa-chevron-down"></i></summary>
                <p><?= e($a) ?></p>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<section class="cta-band">
    <h2>Ready to take charge of your health?</h2>
    <p>Create your free patient account today and access your care online.</p>
    <div class="btn-row">
        <a class="btn btn-accent" href="<?= e(url('register.php')) ?>"><i class="fa-solid fa-user-plus"></i> Create account</a>
        <a class="btn btn-outline" href="<?= e(url('doctors.php')) ?>"><i class="fa-solid fa-user-doctor"></i> Meet our doctors</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
