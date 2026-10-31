<?php
// Редактирование текстовой страницы
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$id = get_id();
$page = $id ? db_one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
if ($id && !$page) admin_not_found();
$row = $page ?? ['slug' => '', 'sort' => (int) db_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM pages'), 'in_menu' => 1];

$error = null;
if (is_post()) {
    admin_require_csrf();
    if (post_str('action', 20) === 'delete' && $page) {
        db_exec('DELETE FROM pages WHERE id = ?', [$id]);
        flash('ok', ta('deleted'));
        redirect('admin/pages.php');
    }
    $slug = strtolower(post_str('slug', 60));
    $sort = post_int('sort') ?? 0;
    $values = post_lang_fields(['title'], 200) + post_lang_fields(['body'], 200000, true);
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) $error = ta('errSlug');
    elseif ($sort === false) $error = ta('errSort');
    elseif ($values['title_tj'] === '' && $values['title_ru'] === '') $error = ta('errTitle');
    elseif (db_val('SELECT COUNT(*) FROM pages WHERE slug = ? AND id <> ?', [$slug, $id])) $error = ta('errSlugTaken');
    else {
        $values += ['slug' => $slug, 'sort' => $sort, 'in_menu' => post_bool('in_menu')];
        $cols = array_keys($values);
        if ($page) {
            db_exec('UPDATE pages SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($values), $id]);
        } else {
            db_exec('INSERT INTO pages (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($values));
            $id = (int) db()->lastInsertId();
        }
        flash('ok', ta('saved'));
        redirect('admin/page.php', ['id' => $id]);
    }
}

$title = $page ? tr($page, 'title', admin_lang()) : ta('newPage');
admin_header($title, 'pages');
?>
<a href="<?= e(url('admin/pages.php')) ?>" class="back-link"><?= e(ta('backPages')) ?></a>
<div class="section-head mb">
  <h1 class="h1"><?= e($title) ?></h1>
  <?php if ($page): ?>
    <div class="row-gap">
      <a href="<?= e(url('page.php', ['slug' => $page['slug']])) ?>" target="_blank" class="btn btn-ghost"><?= e(ta('viewOnSite')) ?></a>
      <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" name="action" value="delete"><?= e(ta('delete')) ?></button></form>
    </div>
  <?php endif ?>
</div>
<?php if ($error): ?><div class="alert-error mb"><?= e($error) ?></div><?php endif ?>
<form method="post" class="card pad stack">
  <?= csrf_field() ?>
  <div class="grid-3">
    <div><label class="label" for="slug"><?= e(ta('fSlug')) ?> *</label><input id="slug" name="slug" required pattern="[a-z0-9\-]+" maxlength="60" class="input" value="<?= e(form_val($row, 'slug')) ?>"><p class="tiny muted"><?= e(ta('hintSlug')) ?></p></div>
    <div><label class="label" for="sort"><?= e(ta('fSort')) ?></label><input id="sort" name="sort" inputmode="numeric" class="input" value="<?= e(form_val($row, 'sort')) ?>"></div>
    <div><label class="check mt"><input type="checkbox" name="in_menu" value="1"<?= (is_post() ? post_bool('in_menu') : $row['in_menu']) ? ' checked' : '' ?>> <?= e(ta('fInMenu')) ?></label></div>
  </div>
  <?php lang_tabs([['title', ta('fTitle'), 'input', ['max' => 200, 'required' => true]], ['body', ta('fBody'), 'editor', ['rows' => 16]]], $row) ?>
  <div><button class="btn btn-primary"><?= e(ta('save')) ?></button></div>
</form>
<?php admin_footer();
