<?php
// Свой профиль: имя и смена пароля (с проверкой текущего)
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
$me = require_login();

if (is_post()) {
    admin_require_csrf();
    $current = is_string($_POST['current'] ?? null) ? $_POST['current'] : '';
    $new = is_string($_POST['new'] ?? null) ? $_POST['new'] : '';
    db_exec('UPDATE users SET name = ? WHERE id = ?', [post_str('name', 100), $me['id']]);
    if ($new !== '') {
        $hash = (string) db_val('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
        if (!password_verify($current, $hash)) {
            flash('error', ta('errCurrentPassword'));
            redirect('admin/profile.php');
        }
        if (mb_strlen($new) < 8) {
            flash('error', ta('errPasswordShort'));
            redirect('admin/profile.php');
        }
        db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        session_regenerate_id(true);
        flash('ok', ta('passwordChanged'));
    } else {
        flash('ok', ta('saved'));
    }
    redirect('admin/profile.php');
}

admin_header(ta('profile'), 'profile');
?>
<div class="narrow">
  <h1 class="h1 mb"><?= e(ta('profile')) ?></h1>
  <form method="post" class="card pad stack" autocomplete="off">
    <?= csrf_field() ?>
    <div><label class="label"><?= e(ta('login')) ?></label><div class="strong"><?= e($me['login']) ?> <span class="tiny muted">(<?= e(ta('role_' . $me['role'])) ?>)</span></div></div>
    <div><label class="label" for="name"><?= e(ta('fName')) ?></label><input id="name" name="name" maxlength="100" class="input" value="<?= e($me['name']) ?>"></div>
    <fieldset class="fieldset stack-sm">
      <legend><?= e(ta('changePassword')) ?></legend>
      <div><label class="label" for="current"><?= e(ta('currentPassword')) ?></label><input id="current" name="current" type="password" autocomplete="current-password" class="input"></div>
      <div><label class="label" for="new"><?= e(ta('newPassword')) ?></label><input id="new" name="new" type="password" minlength="8" autocomplete="new-password" class="input"></div>
    </fieldset>
    <div><button class="btn btn-primary"><?= e(ta('save')) ?></button></div>
  </form>
</div>
<?php admin_footer();
