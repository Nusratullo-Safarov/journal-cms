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
        log_action('delete', 'issue', $id, issue_label($issue));
        delete_upload($issue['cover']);
        delete_upload($issue['pdf']);
        flash('ok', ta('deleted'));
        redirect('admin/issues.php');
    }

    if (post_str('action', 20) === 'reorder' && $issue) {
        foreach ((array) ($_POST['sort'] ?? []) as $aid => $pos) {
            if (is_numeric($aid) && is_string($pos) && preg_match('/^\d{1,6}$/', $pos)) {
                db_exec('UPDATE articles SET sort = ? WHERE id = ? AND issue_id = ?', [(int) $pos, (int) $aid, $id]);
            }
        }
        log_action('update', 'issue', $id, issue_label($issue) . ' — ' . ta('articleOrder'));
        flash('ok', ta('saved'));
        redirect('admin/issue.php', ['id' => $id]);
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
            log_action($issue ? 'update' : 'create', 'issue', $id, issue_label($values));
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
      <a href="<?= e(url('issue.php', ['id' => $id, 'preview' => $issue['is_published'] ? null : 1])) ?>" target="_blank" class="btn btn-ghost"><?= e($issue['is_published'] ? ta('viewOnSite') : ta('preview')) ?></a>
      <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" name="action" value="delete"><?= e(ta('delete')) ?></button></form>
    </div>
  <?php endif ?>
</div>
<?php if ($error): ?><div class="alert-error mb"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="card pad form-grid" data-dirty-check>
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

<?php if ($issue): $articles = db_all('SELECT id, sort, pages, is_published, title_tj, title_ru, title_en, authors_tj, authors_ru, authors_en FROM articles WHERE issue_id = ? ORDER BY sort, id', [$id]); ?>
<form method="post" class="card mt">
  <?= csrf_field() ?><input type="hidden" name="action" value="reorder">
  <div class="card-head">
    <span><?= e(ta('articleOrder')) ?> <span class="muted normal">(<?= count($articles) ?>)</span></span>
    <a href="<?= e(url('admin/article.php', ['issue' => $id])) ?>" class="btn btn-ghost btn-sm"><?= e(ta('addArticleHere')) ?></a>
  </div>
  <?php if (!$articles): ?><div class="pad muted small"><?= e(ta('noArticles')) ?></div><?php else: ?>
    <table class="table">
      <?php foreach ($articles as $a): ?>
        <tr>
          <td class="nowrap"><input name="sort[<?= (int) $a['id'] ?>]" value="<?= (int) $a['sort'] ?>" inputmode="numeric" class="input sort-input" aria-label="<?= e(ta('fSort')) ?>"></td>
          <td><a href="<?= e(url('admin/article.php', ['id' => $a['id']])) ?>" class="link strong"><?= e(tr($a, 'title', admin_lang())) ?></a><div class="tiny muted"><?= e(authors_names($a, admin_lang())) ?></div></td>
          <td class="muted nowrap"><?= e($a['pages']) ?></td>
          <td><?= yes_no_badge($a['is_published']) ?></td>
        </tr>
      <?php endforeach ?>
    </table>
    <div class="pad"><button class="btn btn-ghost"><?= e(ta('saveOrder')) ?></button> <span class="tiny muted"><?= e(ta('orderHint')) ?></span></div>
  <?php endif ?>
</form>
<?php endif ?>
<?php admin_footer();
