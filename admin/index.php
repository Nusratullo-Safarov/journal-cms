<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
$me = require_login();

$stats = [
    ['📚', ta('statIssues'), (int) db_val('SELECT COUNT(*) FROM issues'), url('admin/issues.php')],
    ['📄', ta('statArticles'), (int) db_val('SELECT COUNT(*) FROM articles'), url('admin/articles.php')],
    ['📥', ta('statNewSubmissions'), (int) db_val("SELECT COUNT(*) FROM submissions WHERE status = 'new'"), url('admin/submissions.php', ['status' => 'new'])],
    ['⬇', ta('statDownloads'), (int) db_val('SELECT COALESCE(SUM(downloads), 0) FROM articles'), url('admin/articles.php')],
];

// Омори 30 рӯзи охир
$days = 30;
$rows = array_column(db_all('SELECT day, SUM(views) AS v, SUM(downloads) AS d FROM article_stats WHERE day > CURDATE() - INTERVAL ? DAY GROUP BY day', [$days]), null, 'day');
$series = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $series[] = [$d, (int) ($rows[$d]['v'] ?? 0), (int) ($rows[$d]['d'] ?? 0)];
}
$totalViews = array_sum(array_column($series, 1));
$totalDownloads = array_sum(array_column($series, 2));
$max = max(1, ...array_map(fn($r) => max($r[1], $r[2]), $series));

// Рӯйхати танзимоти аввалия: чӣ ҳанӯз пур нашудааст
$checklist = [
    [ta('chkName'), setting('journal_name_tj') !== 'Паёми илмӣ' || setting('journal_name_ru') !== 'Научный вестник', 'admin/settings.php'],
    [ta('chkLogo'), setting('logo') !== '', 'admin/settings.php'],
    [ta('chkIssn'), setting('issn_print') !== '' || setting('issn_online') !== '', 'admin/settings.php'],
    [ta('chkContacts'), setting('email') !== '' && setting('phone') !== '', 'admin/settings.php'],
    [ta('chkPdf'), (int) db_val("SELECT COUNT(*) FROM articles WHERE pdf = '' AND is_published = 1") === 0, 'admin/articles.php'],
    [ta('chkEnglish'), (int) db_val("SELECT COUNT(*) FROM articles WHERE is_published = 1 AND (title_en = '' OR abstract_en = '')") === 0, 'admin/articles.php'],
];
$checkDone = count(array_filter(array_column($checklist, 1)));

$subs = db_all('SELECT id, name, title, status, created_at FROM submissions ORDER BY id DESC LIMIT 5');
$top = db_all('SELECT id, title_tj, title_ru, title_en, views, downloads FROM articles ORDER BY views DESC LIMIT 5');
$activity = db_all('SELECT * FROM activity_log ORDER BY id DESC LIMIT 8');

admin_header(ta('navOverview'), 'overview');
?>
<div class="section-head">
  <div>
    <h1 class="h1"><?= e(ta('hello', ['name' => $me['name'] !== '' ? $me['name'] : $me['login']])) ?></h1>
    <p class="muted small"><?= e(date('d.m.Y')) ?></p>
  </div>
  <div class="row-gap">
    <a href="<?= e(url('admin/article.php')) ?>" class="btn btn-primary"><?= e(ta('addArticle')) ?></a>
    <a href="<?= e(url('admin/issue.php')) ?>" class="btn btn-ghost"><?= e(ta('addIssue')) ?></a>
    <a href="<?= e(url('admin/news-item.php')) ?>" class="btn btn-ghost"><?= e(ta('addNews')) ?></a>
  </div>
</div>

<div class="stat-grid mb-lg">
  <?php foreach ($stats as [$icon, $label, $value, $href]): ?>
    <a href="<?= e($href) ?>" class="card stat">
      <div class="small muted"><span class="stat-icon" aria-hidden="true"><?= $icon ?></span> <?= e($label) ?></div>
      <div class="stat-value"><?= number_format($value, 0, ',', ' ') ?></div>
    </a>
  <?php endforeach ?>
</div>

<section class="card mb-lg">
  <div class="card-head">
    <span><?= e(ta('chartTitle')) ?></span>
    <span class="legend"><span><i style="background:var(--brand)"></i><?= e(ta('views')) ?>: <?= $totalViews ?></span><span><i style="background:color-mix(in srgb, var(--brand) 40%, #f59e0b)"></i><?= e(ta('downloads')) ?>: <?= $totalDownloads ?></span></span>
  </div>
  <div class="pad">
    <?php $w = 720; $h = 180; $bw = $w / $days; ?>
    <svg class="chart" viewBox="0 0 <?= $w ?> <?= $h + 20 ?>" role="img" aria-label="<?= e(ta('chartTitle')) ?>">
      <?php foreach ([0, 0.5, 1] as $g): $y = $h - $g * $h; ?>
        <line class="grid" x1="0" x2="<?= $w ?>" y1="<?= $y ?>" y2="<?= $y ?>"/>
        <?php if ($g > 0): ?><text x="2" y="<?= $y + 11 ?>"><?= (int) round($max * $g) ?></text><?php endif ?>
      <?php endforeach ?>
      <?php foreach ($series as $i => [$d, $v, $dl]): $x = $i * $bw; ?>
        <rect class="bar-v" x="<?= round($x + $bw * 0.12, 1) ?>" y="<?= round($h - $v / $max * $h, 1) ?>" width="<?= round($bw * 0.38, 1) ?>" height="<?= round($v / $max * $h, 1) ?>" rx="1.5"><title><?= e(format_date($d)) ?>: <?= $v ?> / <?= $dl ?></title></rect>
        <rect class="bar-d" x="<?= round($x + $bw * 0.5, 1) ?>" y="<?= round($h - $dl / $max * $h, 1) ?>" width="<?= round($bw * 0.38, 1) ?>" height="<?= round($dl / $max * $h, 1) ?>" rx="1.5"><title><?= e(format_date($d)) ?>: <?= $v ?> / <?= $dl ?></title></rect>
        <?php if ($i % 5 === 0 || $i === $days - 1): ?><text x="<?= round($x, 1) ?>" y="<?= $h + 15 ?>"><?= e(date('d.m', strtotime($d))) ?></text><?php endif ?>
      <?php endforeach ?>
    </svg>
  </div>
</section>

<div class="two-col mb-lg">
  <section class="card">
    <div class="card-head"><span><?= e(ta('recentSubmissions')) ?></span><a href="<?= e(url('admin/submissions.php')) ?>" class="link small"><?= e(ta('all')) ?> →</a></div>
    <?php if (!$subs): ?><div class="pad small muted"><?= e(ta('noSubmissions')) ?></div><?php endif ?>
    <div class="divided">
      <?php foreach ($subs as $s): ?>
        <a href="<?= e(url('admin/submission.php', ['id' => $s['id']])) ?>" class="list-row">
          <span class="strong">№<?= (int) $s['id'] ?></span>
          <span class="grow truncate"><?= e($s['title']) ?><span class="tiny muted block"><?= e($s['name']) ?> · <?= e(format_date($s['created_at'])) ?></span></span>
          <?= submission_badge($s['status']) ?>
        </a>
      <?php endforeach ?>
    </div>
  </section>
  <section class="card">
    <div class="card-head"><span><?= e(ta('popularArticles')) ?></span></div>
    <div class="divided">
      <?php foreach ($top as $a): ?>
        <a href="<?= e(url('admin/article.php', ['id' => $a['id']])) ?>" class="list-row">
          <span class="grow truncate"><?= e(tr($a, 'title', admin_lang())) ?></span>
          <span class="tiny muted nowrap">👁 <?= (int) $a['views'] ?> · ⬇ <?= (int) $a['downloads'] ?></span>
        </a>
      <?php endforeach ?>
    </div>
  </section>
</div>

<div class="two-col">
  <section class="card">
    <div class="card-head"><span><?= e(ta('checklistTitle')) ?></span><span class="tiny muted"><?= $checkDone ?>/<?= count($checklist) ?></span></div>
    <ul class="checklist">
      <?php foreach ($checklist as [$label, $done, $href]): ?>
        <li><span class="<?= $done ? 'ok' : 'todo' ?>" aria-hidden="true"><?= $done ? '✔' : '○' ?></span>
          <?php if ($done): ?><span class="muted"><?= e($label) ?></span><?php else: ?><a href="<?= e(url($href)) ?>" class="link"><?= e($label) ?></a><?php endif ?></li>
      <?php endforeach ?>
    </ul>
  </section>
  <section class="card">
    <div class="card-head"><span><?= e(ta('recentActivity')) ?></span><?php if (is_admin_role()): ?><a href="<?= e(url('admin/activity.php')) ?>" class="link small"><?= e(ta('all')) ?> →</a><?php endif ?></div>
    <?php if (!$activity): ?><div class="pad small muted"><?= e(ta('noActivity')) ?></div><?php endif ?>
    <ul class="activity">
      <?php foreach ($activity as $act) echo activity_row($act); ?>
    </ul>
  </section>
</div>
<?php admin_footer();
