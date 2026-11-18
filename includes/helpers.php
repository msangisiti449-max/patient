<?php
/**
 * General helpers: output escaping, redirects, flash messages,
 * notifications, audit log and reminder generation.
 */

/** Escape for HTML output. Use on EVERY value printed into a page. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function client_ip(): string
{
    // REMOTE_ADDR only: X-Forwarded-For can be forged by the client.
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Read a trimmed string from POST, limited in length. */
function post_str(string $key, int $max = 255): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    return mb_substr(trim($v), 0, $max);
}

function post_int(string $key): int
{
    $v = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $v === false ? 0 : (int)$v;
}

function get_int(string $key): int
{
    $v = filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $v === false ? 0 : (int)$v;
}

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function valid_time(string $t): bool
{
    return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
}

function strong_password(string $p): bool
{
    return strlen($p) >= 8 && strlen($p) <= 72
        && preg_match('/[A-Z]/', $p) && preg_match('/[a-z]/', $p)
        && preg_match('/\d/', $p) && preg_match('/[^A-Za-z0-9]/', $p);
}

function money($amount): string
{
    return CURRENCY . ' ' . number_format((float)$amount, 2);
}

function fmt_date(?string $d): string
{
    return $d ? date('d M Y', strtotime($d)) : '-';
}

function fmt_time(?string $t): string
{
    return $t ? date('h:i A', strtotime($t)) : '-';
}

function fmt_datetime(?string $d): string
{
    return $d ? date('d M Y, h:i A', strtotime($d)) : '-';
}

/** Coloured status pill. Only fixed class names reach the HTML. */
function badge(string $status): string
{
    $map = [
        'pending' => 'warning', 'confirmed' => 'info', 'completed' => 'success', 'cancelled' => 'muted',
        'normal' => 'success', 'abnormal' => 'warning', 'critical' => 'danger',
        'approved' => 'success', 'denied' => 'danger',
        'unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success',
        'active' => 'success', 'inactive' => 'muted', 'locked' => 'danger',
    ];
    $cls = $map[$status] ?? 'muted';
    return '<span class="badge badge-' . $cls . '">' . e(ucfirst($status)) . '</span>';
}

/** Create a notification. A ref_key makes it unique per user (used to avoid duplicate reminders). */
function notify(int $userId, string $type, string $message, ?string $link = null, ?string $refKey = null): void
{
    q('INSERT IGNORE INTO notifications (user_id, type, message, link, ref_key) VALUES (?, ?, ?, ?, ?)',
        [$userId, $type, mb_substr($message, 0, 255), $link, $refKey]);
}

function audit(string $action, ?int $userId = null): void
{
    try {
        q('INSERT INTO audit_log (user_id, action, ip) VALUES (?, ?, ?)',
            [$userId ?? ($_SESSION['user_id'] ?? null), mb_substr($action, 0, 255), client_ip()]);
    } catch (Throwable $ex) {
        error_log('Audit failed: ' . $ex->getMessage());
    }
}

function unread_notifications(int $userId): int
{
    return (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId])->fetchColumn();
}

function unread_messages(int $userId): int
{
    return (int)q('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0', [$userId])->fetchColumn();
}

/**
 * Create reminder notifications for a patient:
 *  - appointments in the next 24 hours
 *  - medication doses whose reminder time has passed today
 *  - bills due within 3 days
 * Runs at most every 5 minutes per session. ref_key prevents duplicates.
 */
function generate_reminders(int $patientId): void
{
    if (isset($_SESSION['reminders_at']) && time() - $_SESSION['reminders_at'] < 300) {
        return;
    }
    $_SESSION['reminders_at'] = time();

    $appts = q("SELECT a.id, a.appointment_date, a.appointment_time, u.full_name AS doctor
                FROM appointments a JOIN users u ON u.id = a.doctor_id
                WHERE a.patient_id = ? AND a.status IN ('pending','confirmed')
                  AND TIMESTAMP(a.appointment_date, a.appointment_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)",
        [$patientId])->fetchAll();
    foreach ($appts as $a) {
        notify($patientId, 'appointment',
            'Reminder: appointment with ' . $a['doctor'] . ' on ' . fmt_date($a['appointment_date']) . ' at ' . fmt_time($a['appointment_time']) . '.',
            'patient/appointments.php', 'appt-' . $a['id']);
    }

    $meds = q("SELECT r.id, r.remind_time, p.medication, p.dosage
               FROM medication_reminders r JOIN prescriptions p ON p.id = r.prescription_id
               WHERE r.patient_id = ? AND p.start_date <= CURDATE()
                 AND (p.end_date IS NULL OR p.end_date >= CURDATE())
                 AND r.remind_time <= CURTIME()",
        [$patientId])->fetchAll();
    foreach ($meds as $m) {
        notify($patientId, 'medication',
            'Medication reminder: take ' . $m['medication'] . ' (' . $m['dosage'] . ') - scheduled ' . fmt_time($m['remind_time']) . '.',
            'patient/prescriptions.php', 'med-' . $m['id'] . '-' . date('Y-m-d'));
    }

    $bills = q("SELECT id, description, amount, due_date FROM bills
                WHERE patient_id = ? AND status <> 'paid' AND due_date IS NOT NULL
                  AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)",
        [$patientId])->fetchAll();
    foreach ($bills as $b) {
        notify($patientId, 'bill',
            'Bill due soon: ' . $b['description'] . ' (' . money($b['amount']) . ') due ' . fmt_date($b['due_date']) . '.',
            'patient/bills.php', 'bill-' . $b['id']);
    }
}
