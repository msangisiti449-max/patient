<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(403);
$pageTitle = 'Forbidden';
$publicLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="card empty-state auth-card">
        <i class="fa-solid fa-lock fa-3x"></i>
        <h2>Access forbidden</h2>
        <p>You do not have permission to access this resource.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
