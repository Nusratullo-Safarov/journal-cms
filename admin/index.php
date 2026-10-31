<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$stats = [
    [ta('statIssues'), (int) db_val('SELECT COUNT(*) FROM issues'), url('admin/issues.php')],
    [ta('statArticles'), (int) db_val('SELECT COUNT(*) FROM articles'), url('admin/articles.php')],
    [ta('statNewSubmissions'), (int) db_val("SELECT COUNT(*) FROM submissions WHERE status = 'new'"), url('admin/submissions.php', ['status' => 'new'])],
    [ta('statDownloads'), (int) db_val('SELECT COALESCE(SUM(downloads), 0) FROM articles'), url('admin/articles.php')],
];
$subs = db_all('SELECT id, name, title, status, created_at FROM submissions ORDER BY id DESC LIMIT 6');
$top = db_all('SELECT id, title_tj, title_ru, title_en, views, downloads FROM articles ORDER BY views DESC LIMIT 6');

admin_header(ta('navOverview'), 'overview');
?>
<h1 class="h1 mb"><?= e(ta('navOverview')) ?></h1>
<div class="stat-grid mb-lg">
  <?php foreach ($stats as [$label, $value, $href]): ?>
    <a href="<?= e($href) ?>" class="card stat"><div class="small muted"><?= e($label) ?></div><div class="stat-value"><?= $value ?></div></a>
  <?php endforeach ?>
</div>

<div class="row-gap mb-lg">
  <a href="<?= e(url('admin/issue.php')) ?>" class="btn btn-primary"><?= e(ta('addIssue')) ?></a>
  <a href="<?= e(url('admin/article.php')) ?>" class="btn btn-primary"><?= e(ta('addArticle')) ?></a>
  <a href="<?= e(url('admin/news-item.php')) ?>" class="btn btn-ghost"><?= e(ta('addNews')) ?></a>
</div>

<div class="two-col">
  <section class="card">
    <div class="card-head"><?= e(ta('recentSubmissions')) ?></div>
    <?php if (!$subs): ?><div class="pad small muted"><?= e(ta('noSubmissions')) ?></div><?php endif ?>
    <div class="divided">
      <?php foreach ($subs as $s): ?>
        <a href="<?= e(url('admin/submission.php', ['id' => $s['id']])) ?>" class="list-row">
          <span class="strong">№<?= (int) $s['id'] ?></span>
          <span class="grow truncate"><?= e($s['title']) ?></span>
          <?= submission_badge($s['status']) ?>
        </a>
      <?php endforeach ?>
    </div>
  </section>
  <section class="card">
    <div class="card-head"><?= e(ta('popularArticles')) ?></div>
    <div class="divided">
      <?php foreach ($top as $a): ?>
        <a href="<?= e(url('admin/article.php', ['id' => $a['id']])) ?>" class="list-row">
          <span class="grow truncate"><?= e(tr($a, 'title')) ?></span>
          <span class="tiny muted nowrap">👁 <?= (int) $a['views'] ?> · ⬇ <?= (int) $a['downloads'] ?></span>
        </a>
      <?php endforeach ?>
    </div>
  </section>
</div>
<?php admin_footer();
