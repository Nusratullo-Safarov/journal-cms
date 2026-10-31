<?php
// Добавление (без ?id, можно ?issue=N) и редактирование статьи
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

const LANG_FIELDS = ['title', 'authors', 'abstract', 'keywords'];

$id = get_id();
$article = $id ? db_one('SELECT * FROM articles WHERE id = ?', [$id]) : null;
if ($id && !$article) admin_not_found();
$issues = db_all('SELECT id, year, volume, number FROM issues ORDER BY year DESC, id DESC');
$row = $article ?? ['issue_id' => get_id('issue') ?: ($issues[0]['id'] ?? ''), 'section_id' => '', 'sort' => '', 'pages' => '', 'udc' => '',
    'doi' => '', 'refs' => '', 'pdf' => '', 'is_published' => 1];

$error = null;
if (is_post()) {
    admin_require_csrf();
    if (post_str('action', 20) === 'delete' && $article) {
        db_exec('DELETE FROM articles WHERE id = ?', [$id]);
        delete_upload($article['pdf']);
        flash('ok', ta('deleted'));
        redirect('admin/articles.php', ['issue' => $article['issue_id']]);
    }

    $issueId = post_int('issue_id');
    $sectionId = post_int('section_id');
    $sort = post_int('sort');
    $lang = post_lang_fields(LANG_FIELDS, 10000);
    $lang = array_map(fn($v) => str_replace("\r\n", "\n", $v), $lang);
    foreach (LANGS as $l) $lang["title_$l"] = mb_substr(preg_replace('/\s+/u', ' ', $lang["title_$l"]), 0, 500);
    if (!is_int($issueId) || !in_array($issueId, array_column($issues, 'id'), true)) $error = ta('errIssue');
    elseif ($sectionId === false || ($sectionId !== null && !in_array($sectionId, array_column(get_sections(), 'id'), true))) $error = ta('errSection');
    elseif ($sort === false) $error = ta('errSort');
    elseif ($lang['title_tj'] === '' && $lang['title_ru'] === '' && $lang['title_en'] === '') $error = ta('errTitle');
    else {
        [$files, $error] = handle_files(['pdf' => 'pdf'], $row);
        if (!$error) {
            if ($sort === null) $sort = (int) db_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM articles WHERE issue_id = ?', [$issueId]);
            $values = ['issue_id' => $issueId, 'section_id' => $sectionId, 'sort' => $sort, 'pages' => post_str('pages', 20),
                'udc' => post_str('udc', 100), 'doi' => preg_replace('~^https?://(dx\.)?doi\.org/~i', '', post_str('doi', 150)),
                'refs' => str_replace("\r\n", "\n", post_str('refs', 50000)), 'is_published' => post_bool('is_published')] + $lang + $files;
            $cols = array_keys($values);
            if ($article) {
                db_exec('UPDATE articles SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($values), $id]);
            } else {
                db_exec('INSERT INTO articles (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($values));
                $id = (int) db()->lastInsertId();
            }
            cleanup_replaced_files($row, $files);
            flash('ok', ta('saved'));
            redirect('admin/article.php', post_str('next', 10) === 'new' ? ['issue' => $issueId] : ['id' => $id]);
        }
    }
}

$title = $article ? tr($article, 'title') : ta('newArticle');
admin_header($title, 'articles');
?>
<a href="<?= e(url('admin/articles.php', ['issue' => $row['issue_id'] ?: null])) ?>" class="back-link"><?= e(ta('backArticles')) ?></a>
<div class="section-head mb">
  <h1 class="h1"><?= e($title) ?></h1>
  <?php if ($article): ?>
    <div class="row-gap">
      <?php if ($article['is_published']): ?><a href="<?= e(url('article.php', ['id' => $id])) ?>" target="_blank" class="btn btn-ghost"><?= e(ta('viewOnSite')) ?></a><?php endif ?>
      <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" name="action" value="delete"><?= e(ta('delete')) ?></button></form>
    </div>
  <?php endif ?>
</div>
<?php if (!$issues): ?>
  <div class="alert-error mb"><?= e(ta('needIssueFirst')) ?> <a href="<?= e(url('admin/issue.php')) ?>" class="link"><?= e(ta('addIssue')) ?></a></div>
<?php endif ?>
<?php if ($error): ?><div class="alert-error mb"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="card pad form-grid">
  <?= csrf_field() ?>
  <div class="stack">
    <?php lang_tabs([
        ['title', ta('fTitle'), 'textarea', ['rows' => 2, 'required' => true]],
        ['authors', ta('fAuthors'), 'textarea', ['rows' => 3, 'hint' => ta('hintAuthors')]],
        ['abstract', ta('fAbstract'), 'textarea', ['rows' => 6]],
        ['keywords', ta('fKeywords'), 'input', ['max' => 1000, 'hint' => ta('hintKeywords')]],
    ], $row) ?>
    <div>
      <label class="label" for="refs"><?= e(ta('fRefs')) ?></label>
      <textarea id="refs" name="refs" rows="6" class="input"><?= e(form_val($row, 'refs')) ?></textarea>
      <p class="tiny muted"><?= e(ta('hintRefs')) ?></p>
    </div>
  </div>
  <div class="stack">
    <div>
      <label class="label" for="issue_id"><?= e(ta('fIssue')) ?> *</label>
      <select id="issue_id" name="issue_id" class="input" required>
        <?php foreach ($issues as $i): ?><option value="<?= (int) $i['id'] ?>"<?= (string) form_val($row, 'issue_id') === (string) $i['id'] ? ' selected' : '' ?>><?= e(issue_label($i)) ?></option><?php endforeach ?>
      </select>
    </div>
    <div>
      <label class="label" for="section_id"><?= e(ta('fSection')) ?></label>
      <select id="section_id" name="section_id" class="input">
        <option value="">—</option>
        <?php foreach (get_sections() as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (string) form_val($row, 'section_id') === (string) $s['id'] ? ' selected' : '' ?>><?= e(tr($s, 'title', admin_lang())) ?></option><?php endforeach ?>
      </select>
    </div>
    <div class="grid-2">
      <div><label class="label" for="pages"><?= e(ta('fPages')) ?></label><input id="pages" name="pages" maxlength="20" placeholder="5-14" class="input" value="<?= e(form_val($row, 'pages')) ?>"></div>
      <div><label class="label" for="sort"><?= e(ta('fSort')) ?></label><input id="sort" name="sort" inputmode="numeric" class="input" value="<?= e(form_val($row, 'sort')) ?>"></div>
      <div><label class="label" for="udc"><?= e(ta('fUdc')) ?></label><input id="udc" name="udc" maxlength="100" class="input" value="<?= e(form_val($row, 'udc')) ?>"></div>
      <div><label class="label" for="doi">DOI</label><input id="doi" name="doi" maxlength="150" placeholder="10.xxxx/xxxx" class="input" value="<?= e(form_val($row, 'doi')) ?>"></div>
    </div>
    <?php file_field('pdf', ta('fArticlePdf'), $row['pdf'], 'pdf') ?>
    <label class="check"><input type="checkbox" name="is_published" value="1"<?= (is_post() ? post_bool('is_published') : $row['is_published']) ? ' checked' : '' ?>> <?= e(ta('fPublished')) ?></label>
    <?php if ($article): ?><p class="tiny muted">👁 <?= (int) $article['views'] ?> · ⬇ <?= (int) $article['downloads'] ?></p><?php endif ?>
  </div>
  <div class="form-actions">
    <button class="btn btn-primary"><?= e(ta('save')) ?></button>
    <button class="btn btn-ghost" name="next" value="new"><?= e(ta('saveAndNew')) ?></button>
  </div>
</form>
<?php admin_footer();
