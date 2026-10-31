<?php
// Добавление (без ?id) и редактирование выпуска
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$id = get_id();
$issue = $id ? get_issue($id, false) : null;
if ($id && !$issue) admin_not_found();
$row = $issue ?? ['year' => date('Y'), 'volume' => '', 'number' => '', 'published_at' => date('Y-m-d'), 'cover' => '', 'pdf' => '', 'is_published' => 0];

$error = null;
if (is_post()) {
    admin_require_csrf();
    if (post_str('action', 20) === 'delete' && $issue) {
        if ((int) db_val('SELECT COUNT(*) FROM articles WHERE issue_id = ?', [$id]) > 0) {
            flash('error', ta('errIssueHasArticles'));
            redirect('admin/issue.php', ['id' => $id]);
        }
        db_exec('DELETE FROM issues WHERE id = ?', [$id]);
        delete_upload($issue['cover']);
        delete_upload($issue['pdf']);
        flash('ok', ta('deleted'));
        redirect('admin/issues.php');
    }

    $year = post_int('year');
    $number = post_str('number', 20);
    $date = post_str('published_at', 10);
    if (!is_int($year) || $year < 1900 || $year > 2100) $error = ta('errYear');
    elseif ($number === '') $error = ta('errNumber');
    elseif ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $error = ta('errDate');
    else {
        [$files, $error] = handle_files(['cover' => 'image', 'pdf' => 'pdf'], $row);
        if (!$error) {
            $values = ['year' => $year, 'volume' => post_str('volume', 20), 'number' => $number, 'published_at' => $date !== '' ? $date : null,
                'is_published' => post_bool('is_published')] + post_lang_fields(['title'], 300) + $files;
            $cols = array_keys($values);
            if ($issue) {
                db_exec('UPDATE issues SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($values), $id]);
            } else {
                db_exec('INSERT INTO issues (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($values));
                $id = (int) db()->lastInsertId();
            }
            cleanup_replaced_files($row, $files);
            flash('ok', ta('saved'));
            redirect('admin/issue.php', ['id' => $id]);
        }
    }
}

$title = $issue ? issue_label($issue) : ta('newIssue');
admin_header($title, 'issues');
?>
<a href="<?= e(url('admin/issues.php')) ?>" class="back-link"><?= e(ta('backIssues')) ?></a>
<div class="section-head mb">
  <h1 class="h1"><?= e($title) ?></h1>
  <?php if ($issue): ?>
    <div class="row-gap">
      <a href="<?= e(url('admin/articles.php', ['issue' => $id])) ?>" class="btn btn-ghost"><?= e(ta('navArticles')) ?></a>
      <?php if ($issue['is_published']): ?><a href="<?= e(url('issue.php', ['id' => $id])) ?>" target="_blank" class="btn btn-ghost"><?= e(ta('viewOnSite')) ?></a><?php endif ?>
      <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" name="action" value="delete"><?= e(ta('delete')) ?></button></form>
    </div>
  <?php endif ?>
</div>
<?php if ($error): ?><div class="alert-error mb"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="card pad form-grid">
  <?= csrf_field() ?>
  <div class="stack">
    <div class="grid-3">
      <div><label class="label" for="year"><?= e(ta('fYear')) ?> *</label><input id="year" name="year" required inputmode="numeric" class="input" value="<?= e(form_val($row, 'year')) ?>"></div>
      <div><label class="label" for="volume"><?= e(ta('fVolume')) ?></label><input id="volume" name="volume" maxlength="20" class="input" value="<?= e(form_val($row, 'volume')) ?>"></div>
      <div><label class="label" for="number"><?= e(ta('fNumber')) ?> *</label><input id="number" name="number" required maxlength="20" class="input" value="<?= e(form_val($row, 'number')) ?>"></div>
    </div>
    <div><label class="label" for="published_at"><?= e(ta('fPublishedAt')) ?></label><input id="published_at" name="published_at" type="date" class="input w-auto" value="<?= e(form_val($row, 'published_at')) ?>"></div>
    <?php lang_tabs([['title', ta('fIssueTitle'), 'input', ['max' => 300, 'hint' => ta('hintIssueTitle')]]], $row) ?>
    <label class="check"><input type="checkbox" name="is_published" value="1"<?= (is_post() ? post_bool('is_published') : $row['is_published']) ? ' checked' : '' ?>> <?= e(ta('fPublished')) ?></label>
  </div>
  <div class="stack">
    <?php file_field('cover', ta('fCover'), $row['cover'], 'image') ?>
    <?php file_field('pdf', ta('fIssuePdf'), $row['pdf'], 'pdf') ?>
  </div>
  <div class="form-actions"><button class="btn btn-primary"><?= e(ta('save')) ?></button></div>
</form>
<?php admin_footer();
