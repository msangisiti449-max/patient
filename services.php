<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/site_content.php';
$pageTitle = 'Services';
$publicLayout = true;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <h1>Our portal services</h1>
    <p>A detailed look at everything you can do in the CarePoint patient portal.</p>
</section>

<section class="section">
    <nav class="chip-nav" aria-label="Jump to service">
        <?php foreach (SERVICES as $slug => $s): ?>
            <a href="#<?= e($slug) ?>"><i class="fa-solid <?= e($s['icon']) ?>"></i> <?= e($s['title']) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="service-detail-list">
        <?php foreach (SERVICES as $slug => $s): ?>
            <article class="service-detail" id="<?= e($slug) ?>">
                <div class="stat-icon <?= e($s['color']) ?> big"><i class="fa-solid <?= e($s['icon']) ?>"></i></div>
                <div>
                    <h2 class="left"><?= e($s['title']) ?></h2>
                    <p class="service-summary"><?= e($s['summary']) ?></p>
                    <p><?= e($s['details']) ?></p>
                    <ul class="check-list two-col">
                        <?php foreach ($s['points'] as $pt): ?>
                            <li><i class="fa-solid fa-check"></i><?= e($pt) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="cta-band">
    <h2>Start using these services</h2>
    <p>All services are free and available 24 hours a day.</p>
    <div class="btn-row">
        <a class="btn btn-accent" href="<?= e(url('register.php')) ?>"><i class="fa-solid fa-user-plus"></i> Create account</a>
        <a class="btn btn-outline" href="<?= e(url('index.php#faq')) ?>"><i class="fa-solid fa-circle-question"></i> Read the FAQ</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
