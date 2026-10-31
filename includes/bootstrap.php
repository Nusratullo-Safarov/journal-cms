<?php
// Подключается первой строкой каждой страницы.
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';

date_default_timezone_set('Asia/Dushanbe');
mb_internal_encoding('UTF-8');

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/html.php';
require __DIR__ . '/queries.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/uploads.php';
require __DIR__ . '/layout.php';
require __DIR__ . '/migrate.php';

if (!is_file(__DIR__ . '/config.php')) {
    // Сайт ещё не установлен — отправляем на мастер установки
    header('Location: ' . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : '') . 'install.php', true, 302);
    exit;
}
$CONFIG = require __DIR__ . '/config.php';
ensure_schema();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

ini_set('session.gc_maxlifetime', (string) ADMIN_SESSION_TTL);
ini_set('session.use_strict_mode', '1');
session_name('jr_sess');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => base_path() . '/',
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
