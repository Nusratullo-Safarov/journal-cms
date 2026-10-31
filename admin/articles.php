<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$issueId = get_id('issue');
$q = get_str('q', 100);
$where = [];
$args = [];
if ($issueId) {
    $where[] = 'a.issue_id = ?';
    $args[] = $issueId;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '\\%_') . '%';
    $where[] = '(a.title_tj LIKE ? OR a.title_ru LIKE ? OR a.title_en LIKE ? OR a.authors_tj LIKE ? OR a.authors_ru LIKE ? OR a.authors_en LIKE ?)';
    array_push($args, $like, $like, $like, $like, $like, $like);
}
$articles = db_all('SELECT a.*, i.year AS issue_year, i.volume AS issue_volume, i.number AS issue_number
    FROM articles a JOIN issues i ON i.id = a.issue_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
    ORDER BY i.year DESC, i.id DESC, a.sort, a.id LIMIT 500', $args);
$issues = db_all('SELECT id, year, volume, number FROM issues ORDER BY year DESC, id DESC');

admin_header(ta('navArticles'), 'articles');
?>
<div class="section-head mb">
  <h1 class="h1"><?= e(ta('navArticles')) ?></h1>
  <a href="<?= e(url('admin/article.php', ['issue' => $issueId ?: null])) ?>" class="btn btn-primary"><?= e(ta('addArticle')) ?></a>
</div>
<form class="filters">
  <input name="q" value="<?= e($q) ?>" placeholder="<?= e(ta('searchArticles')) ?>" class="input w-64">
  <select name="issue" class="input w-auto">
    <option value=""><?= e(ta('allIssues')) ?></option>
    <?php foreach ($issues as $i): ?><option value="<?= (int) $i['id'] ?>"<?= $issueId === $i['id'] ? ' selected' : '' ?>><?= e(issue_label($i)) ?></option><?php endforeach ?>
  </select>
  <button class="btn btn-ghost"><?= e(ta('find')) ?></button>
</form>
<div class="card scroll-x">
  <table class="table">
    <thead><tr>
      <th><?= e(ta('colTitle')) ?></th><th><?= e(ta('colIssue')) ?></th><th><?= e(ta('colPages')) ?></th><th>PDF</th><th class="right">👁 / ⬇</th><th><?= e(ta('colStatus')) ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($articles as $a): ?>
        <tr>
          <td><a href="<?= e(url('admin/article.php', ['id' => $a['id']])) ?>" class="link strong"><?= e(tr($a, 'title')) ?></a>
            <div class="tiny muted"><?= e(authors_names($a)) ?></div></td>
          <td class="nowrap"><?= e(issue_label($a, 'issue_')) ?></td>
          <td class="nowrap muted"><?= e($a['pages']) ?></td>
          <td><?= $a['pdf'] !== '' ? '✓' : '<span class="text-red">—</span>' ?></td>
          <td class="right nowrap muted"><?= (int) $a['views'] ?> / <?= (int) $a['downloads'] ?></td>
          <td><?= yes_no_badge($a['is_published']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$articles): ?><div class="empty-box muted"><?= e(ta('noArticles')) ?></div><?php endif ?>
</div>
<?php admin_footer();
