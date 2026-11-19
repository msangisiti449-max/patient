<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About Us';
$publicLayout = true;
require __DIR__ . '/includes/header.php';

$values = [
    ['fa-heart',              'Patient-centred care', 'Every decision starts with what is best for the patient.'],
    ['fa-user-lock',          'Privacy',              'Medical information is personal. We protect it as if it were our own.'],
    ['fa-scale-balanced',     'Integrity',            'Honest, transparent communication about health, treatment and costs.'],
    ['fa-lightbulb',          'Innovation',           'Using technology to make healthcare faster, simpler and more accessible.'],
];
?>
<section class="page-hero">
    <h1>About <?= e(HOSPITAL_NAME) ?></h1>
    <p>Bringing quality healthcare closer to every patient through technology.</p>
</section>

<section class="section">
    <div class="split">
        <div>
            <span class="eyebrow">Who we are</span>
            <h2 class="left">Care that continues beyond the hospital</h2>
            <p><?= e(HOSPITAL_NAME) ?> provides outpatient, laboratory and specialist services to our community. We built the CarePoint patient portal so that patients can stay involved in their care between visits, without long queues or repeated phone calls.</p>
            <p>Through the portal, patients see their records and results, book appointments, renew prescriptions, message their doctors and track bills, all from a phone or computer.</p>
        </div>
        <div class="info-panel">
            <div class="mission">
                <i class="fa-solid fa-bullseye"></i>
                <div><h3>Our mission</h3><p>To give every patient easy, secure access to their health information and care team.</p></div>
            </div>
            <div class="mission">
                <i class="fa-solid fa-eye"></i>
                <div><h3>Our vision</h3><p>A community where patients actively take part in their own healthcare.</p></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <span class="eyebrow">What guides us</span>
    <h2>Our values</h2>
    <p class="lead">The principles behind our hospital and this portal.</p>
    <div class="feature-grid">
        <?php foreach ($values as [$icon, $title, $text]): ?>
            <div class="feature">
                <div class="stat-icon teal"><i class="fa-solid <?= e($icon) ?>"></i></div>
                <h3><?= e($title) ?></h3>
                <p><?= e($text) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <span class="eyebrow">Why a patient portal</span>
    <h2>What the portal changes for you</h2>
    <p class="lead">Benefits for patients and for the hospital.</p>
    <ul class="adv-list">
        <li><i class="fa-solid fa-clock"></i>Saves time: no queues to book visits or collect results.</li>
        <li><i class="fa-solid fa-comments"></i>Improves communication between patients and doctors.</li>
        <li><i class="fa-solid fa-folder-open"></i>Provides easy access to medical information.</li>
        <li><i class="fa-solid fa-house-medical"></i>Reduces unnecessary hospital visits.</li>
        <li><i class="fa-solid fa-hand-holding-heart"></i>Improves patient participation in healthcare.</li>
    </ul>
</section>

<section class="cta-band">
    <h2>Join the portal today</h2>
    <p>Free for all patients of <?= e(HOSPITAL_NAME) ?>.</p>
    <div class="btn-row">
        <a class="btn btn-accent" href="<?= e(url('register.php')) ?>"><i class="fa-solid fa-user-plus"></i> Create account</a>
        <a class="btn btn-outline" href="<?= e(url('contact.php')) ?>"><i class="fa-solid fa-phone"></i> Contact us</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
