<?php

function cfg(string $key, mixed $default = ''): mixed
{
    global $CONFIG;
    return $CONFIG[$key] ?? $default;
}

/** Экранирование для HTML. Используйте для любого текста из базы или от пользователя. */
function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fmt(string $s, array $vars): string
{
    return preg_replace_callback('/\{(\w+)\}/', fn($m) => (string) ($vars[$m[1]] ?? ''), $s);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (cfg('trust_proxy') && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function site_url(): string
{
    return rtrim((string) cfg('site_url', 'http://localhost'), '/');
}

/** Путь сайта от корня домена ('' или, например, '/journal', если сайт в подпапке). */
function base_path(): string
{
    static $base = null;
    return $base ??= rtrim((string) (parse_url(site_url(), PHP_URL_PATH) ?? ''), '/');
}

/** Ссылка внутри сайта: url('issue.php', ['id' => 5]) → /issue.php?id=5 (с учётом подпапки). */
function url(string $path = '', array $query = []): string
{
    $query = array_filter($query, fn($v) => $v !== null && $v !== '');
    return base_path() . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
}

/** Полный адрес для sitemap, canonical и мета-тегов: abs_url('article.php', ['id' => 5]) */
function abs_url(string $path = '', array $query = []): string
{
    if (preg_match('~^https?://~i', $path)) return $path;
    $p = parse_url(site_url());
    $origin = ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? 'localhost') . (isset($p['port']) ? ':' . $p['port'] : '');
    return $origin . url($path, $query);
}

/** Ссылка на файл: внешние ссылки как есть, загруженные файлы — относительно сайта. */
function file_url(string $src): string
{
    return preg_match('~^https?://~i', $src) ? $src : url($src);
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query), true, 303);
    exit;
}

/** Адрес для возврата: принимаем только путь внутри сайта, чтобы нельзя было увести на чужой домен. */
function safe_back(?string $back, string $fallback): string
{
    $back = (string) $back;
    if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//') || str_contains($back, '\\')
        || preg_match('/[\x00-\x1f]/', $back)) {
        return url($fallback);
    }
    return $back;
}

function format_date(?string $dt, bool $withTime = false): string
{
    if (!$dt) return '';
    return date($withTime ? 'd.m.Y, H:i' : 'd.m.Y', strtotime($dt));
}

function get_str(string $key, int $max = 200): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

function get_id(string $key = 'id'): int
{
    return (int) filter_var($_GET[$key] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 0]]);
}

// ---------- Формы ----------

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_str(string $key, int $max = 200): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

/** null — поле пустое, false — не целое число. */
function post_int(string $key): int|null|false
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return false;
    $v = preg_replace('/\s/u', '', $v);
    if ($v === '') return null;
    return preg_match('/^-?\d{1,10}$/', $v) ? (int) $v : false;
}

function post_bool(string $key): int
{
    return empty($_POST[$key]) ? 0 : 1;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Защита от подделки запросов: каждая POST-форма должна содержать csrf_field(). */
function csrf_ok(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

/** POST больше post_max_size: PHP молча отбрасывает все поля. */
function post_too_large(): bool
{
    return is_post() && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function flash(string $key, mixed $value = null): mixed
{
    if (func_num_args() === 2) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

// ---------- Защита от перебора ----------

function client_ip(): string
{
    if (cfg('trust_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'local';
}

/** Не больше $max событий за $window секунд с одного IP. Хранится в базе, поэтому работает на любом хостинге. */
function rate_limit(string $key, int $max, int $window): bool
{
    $bucket = substr($key . ':' . client_ip(), 0, 100);
    $now = time();
    if (random_int(1, 50) === 1) db_exec('DELETE FROM rate_limits WHERE created_at < ?', [$now - 86400]);
    $n = (int) db_val('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND created_at > ?', [$bucket, $now - $window]);
    if ($n >= $max) return false;
    db_exec('INSERT INTO rate_limits (bucket, created_at) VALUES (?, ?)', [$bucket, $now]);
    return true;
}

function notify_telegram(string $text): void
{
    $token = setting('telegram_bot_token');
    $chat = setting('telegram_chat_id');
    if ($token === '' || $chat === '') return;
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['chat_id' => $chat, 'text' => $text]),
        'timeout' => 5,
        'ignore_errors' => true,
    ]]);
    if (@file_get_contents('https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage', false, $ctx) === false) {
        error_log('Telegram notify failed');
    }
}

// ---------- Настройки журнала (таблица settings) ----------

function settings_all(bool $reload = false): array
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = array_column(db_all('SELECT k, v FROM settings'), 'v', 'k');
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    return (string) (settings_all()[$key] ?? $default);
}

function save_settings(array $values): void
{
    $st = db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
    foreach ($values as $k => $v) $st->execute([$k, (string) $v]);
    settings_all(true);
}
