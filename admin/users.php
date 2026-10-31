<?php
// Пользователи админ-панели. Только для роли admin.
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_admin_role();
$me = current_user();

if (is_post()) {
    admin_require_csrf();
    $action = post_str('action', 20);
    $id = post_int('id');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if ($action === 'delete' && is_int($id)) {
        if ($id === $me['id']) flash('error', ta('errDeleteSelf'));
        else {
            db_exec('DELETE FROM users WHERE id = ?', [$id]);
            flash('ok', ta('deleted'));
        }
    } elseif ($action === 'password' && is_int($id)) {
        if (mb_strlen($password) < 8) flash('error', ta('errPasswordShort'));
        else {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
            flash('ok', ta('passwordChanged'));
        }
    } elseif ($action === 'role' && is_int($id) && $id !== $me['id'] && in_array(post_str('role', 20), ROLES, true)) {
        db_exec('UPDATE users SET role = ? WHERE id = ?', [post_str('role', 20), $id]);
        flash('ok', ta('saved'));
    } elseif ($action === 'add') {
        $login = post_str('login', 50);
        $role = post_str('role', 20);
        if (!preg_match('/^[a-zA-Z0-9_.\-]{3,50}$/', $login)) flash('error', ta('errLoginFormat'));
        elseif (mb_strlen($password) < 8) flash('error', ta('errPasswordShort'));
        elseif (!in_array($role, ROLES, true)) flash('error', ta('errData'));
        elseif (db_val('SELECT COUNT(*) FROM users WHERE login = ?', [$login])) flash('error', ta('errLoginTaken'));
        else {
            db_exec('INSERT INTO users (login, name, password_hash, role) VALUES (?, ?, ?, ?)',
                [$login, post_str('name', 100), password_hash($password, PASSWORD_DEFAULT), $role]);
            flash('ok', ta('saved'));
        }
    }
    redirect('admin/users.php');
}

$users = db_all('SELECT id, login, name, role, created_at FROM users ORDER BY id');

admin_header(ta('navUsers'), 'users');
?>
<div class="wide stack-lg">
  <div>
    <h1 class="h1"><?= e(ta('navUsers')) ?></h1>
    <p class="muted small"><?= e(ta('rolesHint')) ?></p>
  </div>
  <form method="post" class="card pad inline-form" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <input name="login" required placeholder="<?= e(ta('login')) ?>" aria-label="<?= e(ta('login')) ?>" class="input grow min-40">
    <input name="name" placeholder="<?= e(ta('fName')) ?>" aria-label="<?= e(ta('fName')) ?>" class="input grow min-40">
    <input name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="<?= e(ta('password')) ?>" aria-label="<?= e(ta('password')) ?>" class="input grow min-40">
    <select name="role" class="input w-auto" aria-label="<?= e(ta('colRole')) ?>">
      <?php foreach (ROLES as $r): ?><option value="<?= $r ?>"<?= $r === 'editor' ? ' selected' : '' ?>><?= e(ta("role_$r")) ?></option><?php endforeach ?>
    </select>
    <button class="btn btn-primary"><?= e(ta('add')) ?></button>
  </form>
  <div class="card scroll-x">
    <table class="table">
      <thead><tr><th><?= e(ta('login')) ?></th><th><?= e(ta('colRole')) ?></th><th><?= e(ta('newPassword')) ?></th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><b><?= e($u['login']) ?></b><?php if ($u['name'] !== ''): ?><div class="tiny muted"><?= e($u['name']) ?></div><?php endif ?></td>
            <td>
              <?php if ($u['id'] === $me['id']): ?>
                <?= e(ta('role_' . $u['role'])) ?> <span class="tiny muted">(<?= e(ta('you')) ?>)</span>
              <?php else: ?>
                <form method="post" class="row-gap">
                  <?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <select name="role" class="input w-auto" data-autosubmit aria-label="<?= e(ta('colRole')) ?>">
                    <?php foreach (ROLES as $r): ?><option value="<?= $r ?>"<?= $u['role'] === $r ? ' selected' : '' ?>><?= e(ta("role_$r")) ?></option><?php endforeach ?>
                  </select>
                  <noscript><button class="btn btn-ghost btn-sm"><?= e(ta('save')) ?></button></noscript>
                </form>
              <?php endif ?>
            </td>
            <td>
              <form method="post" class="row-gap" autocomplete="off">
                <?= csrf_field() ?><input type="hidden" name="action" value="password"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <input name="password" type="password" minlength="8" required autocomplete="new-password" class="input w-40" aria-label="<?= e(ta('newPassword')) ?>">
                <button class="btn btn-ghost btn-sm"><?= e(ta('change')) ?></button>
              </form>
            </td>
            <td class="right">
              <?php if ($u['id'] !== $me['id']): ?>
                <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="link-danger small"><?= e(ta('delete')) ?></button>
                </form>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer();
