<?php
// Мастер установки: создаёт таблицы, первого администратора и файл includes/config.php.
// После установки становится недоступен (пока в базе есть хотя бы один пользователь).
declare(strict_types=1);

const APP_ROOT = __DIR__;
date_default_timezone_set('Asia/Dushanbe');
mb_internal_encoding('UTF-8');
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/db.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
session_name('jr_install');
session_start();

$configFile = __DIR__ . '/includes/config.php';
$hasConfig = is_file($configFile);
$CONFIG = $hasConfig ? require $configFile : [];

function try_connect(array $c): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], $c['db_port'], $c['db_name']);
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+05:00'");
    return $pdo;
}

function table_exists(PDO $pdo, string $table): bool
{
    return (bool) $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetchColumn();
}

function import_schema(PDO $pdo): void
{
    $sql = (string) file_get_contents(__DIR__ . '/database.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
        if (trim($stmt) !== '') $pdo->exec($stmt);
    }
}

// Уже установлено? Тогда мастер закрыт.
if ($hasConfig) {
    try {
        $pdo = try_connect($CONFIG);
        if (table_exists($pdo, 'users') && (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            http_response_code(403);
            exit('<!DOCTYPE html><meta charset="utf-8"><p style="font-family:sans-serif;padding:2rem">Сомона аллакай насб шудааст. '
                . '<a href="admin/">Ба панели идоракунӣ</a>. Барои амният файли install.php-ро нест кардан мумкин аст.</p>');
        }
    } catch (PDOException $ex) {
        // Конфиг уже есть — через браузер его не перезаписываем (иначе чужой человек мог бы подменить базу)
        http_response_code(503);
        exit('<!DOCTYPE html><meta charset="utf-8"><p style="font-family:sans-serif;padding:2rem">Ба пойгоҳи додаҳо пайваст шуда натавонистем. '
            . 'Танзимоти файли includes/config.php-ро санҷед ё онро нест карда, install.php-ро аз нав кушоед.</p>');
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$guessUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

$v = fn(string $k, string $d = '') => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : $d;
$errors = [];
$configText = null;
$done = false;

if (is_post()) {
    if (!csrf_ok()) $errors[] = 'Саҳифа кӯҳна шудааст. Онро нав кунед.';
    $c = $hasConfig ? $CONFIG : [
        'db_host' => $v('db_host', 'localhost'),
        'db_port' => (int) $v('db_port', '3306') ?: 3306,
        'db_name' => $v('db_name'),
        'db_user' => $v('db_user'),
        'db_pass' => is_string($_POST['db_pass'] ?? null) ? $_POST['db_pass'] : '',
        'site_url' => rtrim($v('site_url', $guessUrl), '/'),
        'trust_proxy' => false,
    ];
    $login = $v('admin_login');
    $password = is_string($_POST['admin_password'] ?? null) ? $_POST['admin_password'] : '';
    if (!$hasConfig && ($c['db_name'] === '' || $c['db_user'] === '')) $errors[] = 'Номи пойгоҳ ва корбари онро нависед.';
    if (!$hasConfig && !preg_match('~^https?://~', $c['site_url'])) $errors[] = 'Суроғаи сомона бояд бо http:// ё https:// оғоз шавад.';
    if (!preg_match('/^[a-zA-Z0-9_.\-]{3,50}$/', $login)) $errors[] = 'Логин: 3–50 ҳарфи лотинӣ, рақам, нуқта ё дефис.';
    if (mb_strlen($password) < 8) $errors[] = 'Парол бояд на камтар аз 8 аломат бошад.';

    if (!$errors) {
        try {
            $pdo = try_connect($c);
            if (!table_exists($pdo, 'users')) import_schema($pdo);
            $st = $pdo->prepare("INSERT INTO users (login, name, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $st->execute([$login, mb_substr($v('admin_name'), 0, 100), password_hash($password, PASSWORD_DEFAULT)]);
            if (!$hasConfig) {
                $configText = "<?php\n// Сохтаи install.php. Барои тағйир додани пайваст ба пойгоҳ ин файлро таҳрир кунед.\nreturn "
                    . var_export($c, true) . ";\n";
                if (@file_put_contents($configFile, $configText) !== false) {
                    @chmod($configFile, 0640);
                    $configText = null;
                }
            }
            $done = true;
        } catch (PDOException $ex) {
            $errors[] = 'Хатои пойгоҳи додаҳо: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tg">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Насби сомона</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="card pad install-box stack">
    <h1 class="h2">Насби сомонаи маҷалла</h1>
    <?php if ($done): ?>
      <div class="alert-ok">Сомона насб шуд!</div>
      <?php if ($configText !== null): ?>
        <p class="small">Файли <code>includes/config.php</code> навишта нашуд (иҷозати навиштан нест). Онро дастӣ созед ва ин матнро дар он гузоред:</p>
        <textarea class="input mono" rows="12" readonly><?= e($configText) ?></textarea>
      <?php endif ?>
      <p class="small">Барои амният файли <code>install.php</code>-ро аз сервер нест кунед.</p>
      <div class="row-gap">
        <a href="admin/" class="btn btn-primary">Ба панели идоракунӣ</a>
        <a href="./" class="btn btn-ghost">Кушодани сомона</a>
      </div>
    <?php else: ?>
      <?php foreach ($errors as $err): ?><div class="alert-error"><?= e($err) ?></div><?php endforeach ?>
      <form method="post" class="stack">
        <?= csrf_field() ?>
        <?php if (!$hasConfig): ?>
          <fieldset class="fieldset stack-sm">
            <legend>Пойгоҳи додаҳо (MySQL)</legend>
            <p class="tiny muted">Пойгоҳи холиро дар панели хостинг ё phpMyAdmin пешакӣ созед.</p>
            <div class="grid-2">
              <div><label class="label">Сервер</label><input name="db_host" class="input" value="<?= e($v('db_host', 'localhost')) ?>"></div>
              <div><label class="label">Порт</label><input name="db_port" class="input" value="<?= e($v('db_port', '3306')) ?>"></div>
              <div><label class="label">Номи пойгоҳ</label><input name="db_name" required class="input" value="<?= e($v('db_name')) ?>"></div>
              <div><label class="label">Корбар</label><input name="db_user" required class="input" value="<?= e($v('db_user')) ?>"></div>
            </div>
            <div><label class="label">Пароли пойгоҳ</label><input name="db_pass" type="password" class="input"></div>
            <div><label class="label">Суроғаи сомона</label><input name="site_url" required class="input" value="<?= e($v('site_url', $guessUrl)) ?>"></div>
          </fieldset>
        <?php endif ?>
        <fieldset class="fieldset stack-sm">
          <legend>Администратор</legend>
          <div><label class="label">Ном</label><input name="admin_name" class="input" maxlength="100" value="<?= e($v('admin_name')) ?>"></div>
          <div class="grid-2">
            <div><label class="label">Логин</label><input name="admin_login" required class="input" autocomplete="username" value="<?= e($v('admin_login', 'admin')) ?>"></div>
            <div><label class="label">Парол (на камтар аз 8)</label><input name="admin_password" type="password" required minlength="8" autocomplete="new-password" class="input"></div>
          </div>
        </fieldset>
        <button class="btn btn-primary">Насб кардан</button>
      </form>
    <?php endif ?>
  </div>
</div>
</body>
</html>
