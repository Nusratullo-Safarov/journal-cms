<?php
// Добавление и редактирование новости
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$id = get_id();
$item = $id ? db_one('SELECT * FROM news WHERE id = ?', [$id]) : null;
if ($id && !$item) admin_not_found();
$row = $item ?? ['image' => '', 'published_at' => date('Y-m-d H:i:s'), 'is_published' => 1];
$row['published_at_input'] = date('Y-m-d\TH:i', strtotime($row['published_at']));

$error = null;
if (is_post()) {
    admin_require_csrf();
    if (post_str('action', 20) === 'delete' && $item) {
        db_exec('DELETE FROM news WHERE id = ?', [$id]);
        delete_upload($item['image']);
        flash('ok', ta('deleted'));
        redirect('admin/news.php');
    }
    $values = post_lang_fields(['title'], 300) + post_lang_fields(['body'], 200000, true);
    $date = post_str('published_at_input', 16);
    $ts = $date !== '' ? strtotime($date) : time();
    if ($values['title_tj'] === '' && $values['title_ru'] === '') $error = ta('errTitle');
    elseif ($ts === false) $error = ta('errDate');
    else {
        [$files, $error] = handle_files(['image' => 'image'], $row);
        if (!$error) {
            $values += ['published_at' => date('Y-m-d H:i:s', $ts), 'is_published' => post_bool('is_published')] + $files;
            $cols = array_keys($values);
            if ($item) {
                db_exec('UPDATE news SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($values), $id]);
            } else {
                db_exec('INSERT INTO news (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($values));
                $id = (int) db()->lastInsertId();
            }
            cleanup_replaced_files($row, $files);
            flash('ok', ta('saved'));
            redirect('admin/news-item.php', ['id' => $id]);
        }
    }
}

$title = $item ? tr($item, 'title', admin_lang()) : ta('newNews');
admin_header($title, 'news');
?>
<a href="<?= e(url('admin/news.php')) ?>" class="back-link"><?= e(ta('backNews')) ?></a>
<div class="section-head mb">
  <h1 class="h1"><?= e($title) ?></h1>
  <?php if ($item): ?>
    <div class="row-gap">
      <a href="<?= e(url('news.php', ['id' => $id])) ?>" target="_blank" class="btn btn-ghost"><?= e(ta('viewOnSite')) ?></a>
      <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" name="action" value="delete"><?= e(ta('delete')) ?></button></form>
    </div>
  <?php endif ?>
</div>
<?php if ($error): ?><div class="alert-error mb"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="card pad form-grid">
  <?= csrf_field() ?>
  <div class="stack">
    <?php lang_tabs([['title', ta('fTitle'), 'input', ['max' => 300, 'required' => true]], ['body', ta('fBody'), 'editor', ['rows' => 12]]], $row) ?>
  </div>
  <div class="stack">
    <div><label class="label" for="published_at_input"><?= e(ta('fPublishedAt')) ?></label>
      <input id="published_at_input" name="published_at_input" type="datetime-local" class="input" value="<?= e(form_val($row, 'published_at_input')) ?>"></div>
    <?php file_field('image', ta('fImage'), $row['image'], 'image') ?>
    <label class="check"><input type="checkbox" name="is_published" value="1"<?= (is_post() ? post_bool('is_published') : $row['is_published']) ? ' checked' : '' ?>> <?= e(ta('fPublished')) ?></label>
  </div>
  <div class="form-actions"><button class="btn btn-primary"><?= e(ta('save')) ?></button></div>
</form>
<?php admin_footer();
