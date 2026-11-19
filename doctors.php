<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Our Doctors';
$publicLayout = true;

// Public page: only show name and specialization, never contact details.
$spec = mb_substr(trim((string)($_GET['specialization'] ?? '')), 0, 100);
$specs = q("SELECT DISTINCT dp.specialization FROM doctors_profile dp JOIN users u ON u.id = dp.user_id
            WHERE u.is_active = 1 ORDER BY dp.specialization")->fetchAll(PDO::FETCH_COLUMN);
$sql = "SELECT u.full_name, dp.specialization FROM users u LEFT JOIN doctors_profile dp ON dp.user_id = u.id
        WHERE u.role = 'doctor' AND u.is_active = 1";
$params = [];
if ($spec !== '' && in_array($spec, $specs, true)) {
    $sql .= ' AND dp.specialization = ?';
    $params[] = $spec;
} else {
    $spec = '';
}
$doctors = q($sql . ' ORDER BY u.full_name', $params)->fetchAll();
$user = current_user();
$bookUrl = ($user && $user['role'] === 'patient') ? 'patient/book-appointment.php' : 'login.php';

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <h1>Meet our doctors</h1>
    <p>Qualified specialists you can book and message through the portal.</p>
</section>

<section class="section">
    <?php if (count($specs) > 1): ?>
        <nav class="chip-nav" aria-label="Filter by specialization">
            <a class="<?= $spec === '' ? 'active' : '' ?>" href="<?= e(url('doctors.php')) ?>">All</a>
            <?php foreach ($specs as $s): ?>
                <a class="<?= $spec === $s ? 'active' : '' ?>" href="<?= e(url('doctors.php?specialization=' . rawurlencode($s))) ?>"><?= e($s) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!$doctors): ?>
        <div class="card empty-state"><i class="fa-solid fa-user-doctor fa-3x"></i><p>No doctors are listed yet.</p></div>
    <?php else: ?>
        <div class="doctor-grid">
            <?php foreach ($doctors as $d): ?>
                <article class="doctor-card">
                    <div class="doctor-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                    <h3><?= e($d['full_name']) ?></h3>
                    <p class="doctor-spec"><i class="fa-solid fa-stethoscope"></i> <?= e($d['specialization'] ?? 'General practice') ?></p>
                    <ul class="doctor-meta">
                        <li><i class="fa-regular fa-calendar"></i> Mon - Sat</li>
                        <li><i class="fa-regular fa-clock"></i> 08:00 - 17:00</li>
                    </ul>
                    <a class="btn btn-primary btn-block" href="<?= e(url($bookUrl)) ?>"><i class="fa-solid fa-calendar-plus"></i> Book appointment</a>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$user): ?>
            <p class="lead mt">You need a free patient account to book. <a href="<?= e(url('register.php')) ?>">Register here</a>.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
