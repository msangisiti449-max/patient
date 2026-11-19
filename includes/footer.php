<?php if (!empty($showApp)): ?>
        </main>
        <footer class="app-footer">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></footer>
    </div>
</div>
<?php else: ?>
</main>
<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <a class="brand" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-heart-pulse"></i><span>CarePoint</span></a>
            <p>Your health information, secure and in one place. Book visits, see results and talk to your doctor online.</p>
        </div>
        <div>
            <h4>Portal</h4>
            <a href="<?= e(url('about.php')) ?>">About Us</a>
            <a href="<?= e(url('services.php')) ?>">Services</a>
            <a href="<?= e(url('doctors.php')) ?>">Our Doctors</a>
            <a href="<?= e(url('index.php#faq')) ?>">FAQ</a>
        </div>
        <div>
            <h4>Account</h4>
            <a href="<?= e(url('register.php')) ?>">Create account</a>
            <a href="<?= e(url('login.php')) ?>">Sign in</a>
            <a href="<?= e(url('forgot-password.php')) ?>">Forgot password</a>
            <a href="<?= e(url('privacy.php')) ?>">Privacy &amp; Security</a>
        </div>
        <div>
            <h4>Contact</h4>
            <p><i class="fa-solid fa-location-dot"></i> <?= e(HOSPITAL_ADDRESS) ?></p>
            <p><i class="fa-solid fa-phone"></i> <?= e(HOSPITAL_PHONE) ?></p>
            <p><i class="fa-solid fa-envelope"></i> <?= e(HOSPITAL_EMAIL) ?></p>
            <p class="footer-emergency"><i class="fa-solid fa-truck-medical"></i> Emergency: <?= e(HOSPITAL_EMERGENCY) ?></p>
        </div>
    </div>
    <p class="footer-bottom">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
</footer>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/app.js')) ?>"></script>
</body>
</html>
