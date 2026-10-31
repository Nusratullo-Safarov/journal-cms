<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

if (current_user()) redirect('admin/');

$error = '';
if (is_post()) {
    if (!csrf_ok()) {
        $error = ta('errCsrf');
    } elseif (!rate_limit('login', 10, 15 * 60)) {
        $error = ta('errLoginRate');
    } elseif (!attempt_login(post_str('login', 50), is_string($_POST['password'] ?? null) ? $_POST['password'] : '')) {
        $error = ta('errLogin');
    } else {
        log_action('login', 'user', current_user()['id'] ?? null, post_str('login', 50));
        redirect('admin/');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= admin_lang() === 'ru' ? 'ru' : 'tg' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e(ta('loginTitle')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/style.css')) ?>">
<style>:root { --brand: <?= brand_color() ?>; }</style>
</head>
<body>
<div class="login-wrap">
  <form method="post" class="card pad login-box stack">
    <?= csrf_field() ?>
    <div class="login-title"><?= logo_html() ?><h1 class="h3"><?= e(ta('loginTitle')) ?></h1></div>
    <div>
      <label class="label" for="login"><?= e(ta('login')) ?></label>
      <input id="login" name="login" required autofocus autocomplete="username" class="input" value="<?= e(post_str('login', 50)) ?>">
    </div>
    <div>
      <label class="label" for="password"><?= e(ta('password')) ?></label>
      <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
    </div>
    <?php if ($error !== ''): ?><div class="alert-error"><?= e($error) ?></div><?php endif ?>
    <button class="btn btn-primary btn-block"><?= e(ta('signIn')) ?></button>
  </form>
  <?= admin_lang_switch() ?>
</div>
</body>
</html>
