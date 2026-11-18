<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
$pageTitle = 'Page not found';
$publicLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="card empty-state auth-card">
        <i class="fa-solid fa-compass fa-3x"></i>
        <h2>Page not found</h2>
        <p>The page you are looking for does not exist.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
