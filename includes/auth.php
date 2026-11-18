<?php
/**
 * Authentication and role-based access control.
 * Every protected page calls require_role() before doing anything else.
 */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        $row = q('SELECT id, role, full_name, email, phone, dob, gender, address, is_active FROM users WHERE id = ?',
            [(int)$_SESSION['user_id']])->fetch();
        // Re-check on every request: a deactivated account or changed role loses access immediately.
        if ($row && (int)$row['is_active'] === 1 && $row['role'] === ($_SESSION['role'] ?? '')) {
            $user = $row;
        } else {
            destroy_session();
            start_secure_session();
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
    return $user;
}

/**
 * Lock a page to one or more roles. Anyone else gets a 403 and the attempt is logged.
 * @param string|string[] $roles
 */
function require_role($roles): array
{
    $roles = (array)$roles;
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        audit('Access denied to ' . ($_SERVER['SCRIPT_NAME'] ?? '') . ' for role ' . $user['role']);
        http_response_code(403);
        $pageTitle = 'Access denied';
        require __DIR__ . '/header.php';
        echo '<div class="card empty-state"><i class="fa-solid fa-lock fa-3x"></i>'
            . '<h2>Access denied</h2><p>You do not have permission to view this page.</p>'
            . '<a class="btn btn-primary" href="' . e(url(dashboard_path($user['role']))) . '">Go to my dashboard</a></div>';
        require __DIR__ . '/footer.php';
        exit;
    }
    return $user;
}

function dashboard_path(string $role): string
{
    return match ($role) {
        'admin'  => 'admin/dashboard.php',
        'doctor' => 'doctor/dashboard.php',
        default  => 'patient/dashboard.php',
    };
}

/** Called after a successful password check. */
function login_user(array $user): void
{
    session_regenerate_id(true);   // prevent session fixation
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['created_at'] = time();
    unset($_SESSION['csrf_token'], $_SESSION['reminders_at']);
}
