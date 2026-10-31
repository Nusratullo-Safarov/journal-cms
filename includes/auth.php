<?php
// Вход в админ-панель. Пользователи хранятся в таблице users, пароли — только в виде хеша.

const ADMIN_SESSION_TTL = 12 * 3600; // 12 часов
const ROLES = ['admin', 'editor']; // admin — всё, editor — без настроек и пользователей

function attempt_login(string $login, string $password): bool
{
    $user = db_one('SELECT * FROM users WHERE login = ?', [$login]);
    // Проверяем хеш даже для несуществующего логина, чтобы время ответа не выдавало, есть ли такой пользователь
    $hash = $user['password_hash'] ?? password_hash(random_bytes(16), PASSWORD_DEFAULT);
    if (!password_verify($password, $hash) || !$user) return false;
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    $_SESSION['admin_until'] = time() + ADMIN_SESSION_TTL;
    return true;
}

function logout(): void
{
    unset($_SESSION['uid'], $_SESSION['admin_until']);
    session_regenerate_id(true);
}

function current_user(): ?array
{
    // Кэш по id из сессии: после входа или выхода в том же запросе данные обновляются
    static $cache = [];
    if (($_SESSION['admin_until'] ?? 0) <= time() || empty($_SESSION['uid'])) return null;
    $uid = (int) $_SESSION['uid'];
    return $cache[$uid] ??= db_one('SELECT id, login, name, role FROM users WHERE id = ?', [$uid]);
}

function is_admin_role(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if (!$user) redirect('admin/login.php');
    return $user;
}

/** Страницы «Настройки» и «Пользователи» — только для роли admin. */
function require_admin_role(): void
{
    require_login();
    if (!is_admin_role()) {
        flash('error', ta('errNoAccess'));
        redirect('admin/');
    }
}
