<?php

declare(strict_types=1);

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_admin(): void
{
    require_role(['super_admin', 'teacher']);
}

function require_role(string|array $roles): void
{
    $roles = (array) $roles;
    if (!user() || !in_array((string) (user()['role'] ?? ''), $roles, true)) {
        redirect('login.php');
    }
    if (!empty(user()['must_change_password']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'settings.php') {
        redirect('admin/settings.php');
    }
}

function is_super_admin(): bool
{
    return (user()['role'] ?? null) === 'super_admin';
}

function current_user_id(): int
{
    return (int) (user()['id'] ?? 0);
}

function login_user(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $admin['id'],
        'name' => $admin['name'],
        'role' => $admin['role'],
        'must_change_password' => (bool) $admin['must_change_password'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}