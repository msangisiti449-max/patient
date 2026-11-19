<?php
/** Public contact form. Messages are stored for administrators (Admin > Contact Messages). */
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contact Us';
$publicLayout = true;

$errors = [];
$sent = false;
$user = current_user();
$old = ['name' => $user['full_name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '', 'subject' => '', 'message' => ''];
$topics = ['General enquiry', 'Appointments', 'Portal account help', 'Billing', 'Feedback', 'Other'];

if (is_post()) {
    verify_csrf();
    foreach (['name' => 100, 'email' => 150, 'phone' => 20, 'subject' => 50, 'message' => 3000] as $k => $max) {
        $old[$k] = post_str($k, $max);
    }
    $old['email'] = strtolower($old['email']);

    // Spam protection: hidden honeypot field + per-IP limit
    $honeypot = post_str('website', 100);
    $recent = (int)q('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)', [client_ip()])->fetchColumn();

    if (!preg_match("/^[\p{L} .'-]{2,100}$/u", $old['name'])) {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $old['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if (!in_array($old['subject'], $topics, true)) {
        $errors[] = 'Choose a topic.';
    }
    if (mb_strlen($old['message']) < 10) {
        $errors[] = 'Your message should be at least 10 characters.';
    }
    if ($recent >= 5) {
        $errors[] = 'You have sent several messages recently. Please wait a while or call us.';
    }

    if (!$errors) {
        if ($honeypot === '') {
            q('INSERT INTO contact_messages (name, email, phone, subject, message, user_id, ip) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$old['name'], $old['email'], $old['phone'] ?: null, $old['subject'], $old['message'], $user['id'] ?? null, client_ip()]);
            foreach (q("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll() as $a) {
                notify((int)$a['id'], 'message', 'New contact message from ' . $old['name'] . ': ' . $old['subject'], 'admin/contact-messages.php');
            }
        }
        $sent = true;
        $old['subject'] = $old['message'] = '';
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <h1>Contact us</h1>
    <p>Questions about the portal, appointments or billing? We are here to help.</p>
</section>

<section class="section">
    <div class="alert alert-error emergency-note"><i class="fa-solid fa-truck-medical"></i>
        <span><strong>Medical emergency?</strong> Do not use this form. Call <?= e(HOSPITAL_EMERGENCY) ?> or go to the nearest emergency department.</span></div>

    <div class="contact-grid">
        <div class="contact-info">
            <div class="contact-item"><span class="stat-icon blue"><i class="fa-solid fa-location-dot"></i></span>
                <div><h3>Visit us</h3><p><?= e(HOSPITAL_ADDRESS) ?></p></div></div>
            <div class="contact-item"><span class="stat-icon teal"><i class="fa-solid fa-phone"></i></span>
                <div><h3>Call us</h3><p><?= e(HOSPITAL_PHONE) ?></p></div></div>
            <div class="contact-item"><span class="stat-icon amber"><i class="fa-solid fa-envelope"></i></span>
                <div><h3>Email</h3><p><?= e(HOSPITAL_EMAIL) ?></p></div></div>
            <div class="contact-item"><span class="stat-icon red"><i class="fa-regular fa-clock"></i></span>
                <div><h3>Opening hours</h3>
                    <table class="hours">
                        <?php foreach (HOSPITAL_HOURS as $day => $hrs): ?>
                            <tr><td><?= e($day) ?></td><td><?= e($hrs) ?></td></tr>
                        <?php endforeach; ?>
                    </table></div></div>
        </div>

        <div class="card">
            <div class="card-head"><h2><i class="fa-solid fa-paper-plane"></i> Send us a message</h2></div>
            <?php if ($sent): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><span>Thank you. Your message has been received and our team will reply by email or phone.</span></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
                    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('contact.php')) ?>">
                <?= csrf_field() ?>
                <div class="hp-field" aria-hidden="true"><label for="website">Leave empty</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="form-grid">
                    <div class="field"><label for="name">Full name</label><input id="name" name="name" maxlength="100" required value="<?= e($old['name']) ?>"></div>
                    <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="150" required value="<?= e($old['email']) ?>"></div>
                    <div class="field"><label for="phone">Phone (optional)</label><input type="tel" id="phone" name="phone" maxlength="20" value="<?= e($old['phone']) ?>"></div>
                    <div class="field"><label for="subject">Topic</label>
                        <select id="subject" name="subject" required><option value="">Choose a topic</option>
                            <?php foreach ($topics as $t): ?><option <?= $old['subject'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="field full"><label for="message">Message</label><textarea id="message" name="message" maxlength="3000" required><?= e($old['message']) ?></textarea>
                        <span class="hint">Please do not include detailed medical information here. Signed-in patients can message their doctor securely in the portal.</span></div>
                </div>
                <button type="submit" class="btn btn-primary mt"><i class="fa-solid fa-paper-plane"></i> Send message</button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
