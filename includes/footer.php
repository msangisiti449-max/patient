<?php if (!empty($showApp)): ?>
        </main>
        <footer class="app-footer">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></footer>
    </div>
</div>
<?php else: ?>
</main>
<footer class="public-footer">
    <p><i class="fa-solid fa-heart-pulse"></i> &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Your health information, secure and in one place.</p>
</footer>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
