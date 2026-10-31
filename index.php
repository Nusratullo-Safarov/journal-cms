<?php
require __DIR__ . '/includes/bootstrap.php';

$issue = latest_issue();
$contents = $issue ? issue_contents($issue['id']) : [];
$latest = [];
foreach ($contents as $g) foreach ($g['articles'] as $a) $latest[] = $a;
$news = latest_news(3);
$stats = site_stats();
$popular = popular_articles(5);
$sections = sections_with_counts();
$about = db_one("SELECT * FROM pages WHERE slug = 'about'");
$indexing = indexing_links();
$journal = setting_tr('journal_name');

$jsonLd = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Periodical',
    'name' => $journal,
    'description' => setting_tr('tagline') ?: null,
    'url' => abs_url(),
    'issn' => array_values(array_filter([setting('issn_print'), setting('issn_online')])) ?: null,
    'publisher' => setting_tr('publisher') !== '' ? ['@type' => 'Organization', 'name' => setting_tr('publisher')] : null,
    'inLanguage' => ['tg', 'ru', 'en'],
]);

site_header('', [
    'canonical' => abs_url(),
    'nav' => 'home',
    'image' => $issue['cover'] ?? '',
    'head' => '<script type="application/ld+json">' . json_encode($jsonLd, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>',
]);
?>
<section class="hero">
  <div class="hero-body">
    <?php if (($tag = setting_tr('tagline')) !== ''): ?><div class="kicker"><?= e($tag) ?></div><?php endif ?>
    <h1 class="hero-title"><?= e($journal) ?></h1>
    <?php if ($about && ($text = html_to_text(tr($about, 'body'), 320)) !== ''): ?><p class="hero-text"><?= e($text) ?></p><?php endif ?>
    <div class="chips">
      <?php if (($v = setting('issn_print')) !== ''): ?><span class="chip">ISSN <?= e($v) ?></span><?php endif ?>
      <?php if (($v = setting('issn_online')) !== ''): ?><span class="chip">eISSN <?= e($v) ?></span><?php endif ?>
      <?php if (($v = setting_tr('frequency')) !== ''): ?><span class="chip"><?= e($v) ?></span><?php endif ?>
      <span class="chip"><?= e(t('languagesChip')) ?></span>
    </div>
    <div class="row-gap">
      <?php if ($issue): ?><a href="<?= e(url('issue.php', ['id' => $issue['id']])) ?>" class="btn btn-primary btn-lg"><?= e(t('currentIssue')) ?> →</a><?php endif ?>
      <a href="<?= e(url('submit.php')) ?>" class="btn btn-ghost btn-lg"><?= e(t('submit')) ?></a>
    </div>
  </div>
  <?php if ($issue): ?>
    <a href="<?= e(url('issue.php', ['id' => $issue['id']])) ?>" class="hero-cover" aria-label="<?= e(issue_label($issue)) ?>">
      <?= issue_cover($issue) ?>
      <span class="hero-cover-label"><?= e(issue_label($issue)) ?></span>
    </a>
  <?php endif ?>
</section>

<section class="stats-row" aria-label="<?= e(t('statsTitle')) ?>">
  <?php foreach ([['issues', 'statIssues'], ['articles', 'statArticles'], ['authors', 'statAuthors'], ['downloads', 'statDownloads']] as [$k, $label]): ?>
    <div class="stat-box"><b><?= number_format($stats[$k], 0, ',', ' ') ?></b><span><?= e(t($label)) ?></span></div>
  <?php endforeach ?>
</section>

<div class="layout-sidebar">
  <div class="stack-lg">
    <section>
      <div class="section-head">
        <h2 class="h2"><?= e(t('latestArticles')) ?><?= $issue ? ' <span class="muted">· ' . e(issue_label($issue)) . '</span>' : '' ?></h2>
        <a href="<?= e(url('archive.php')) ?>" class="link small"><?= e(t('allIssues')) ?></a>
      </div>
      <?php if (!$issue): ?>
        <div class="card pad muted"><?= e(t('noIssues')) ?></div>
      <?php elseif (!$latest): ?>
        <div class="card pad muted"><?= e(t('noArticles')) ?></div>
      <?php else: ?>
        <div class="card divided"><?php foreach ($latest as $a) article_item($a); ?></div>
      <?php endif ?>
    </section>

    <?php if ($sections): ?>
    <section>
      <h2 class="h2 mb"><?= e(t('sections')) ?></h2>
      <div class="section-grid">
        <?php foreach ($sections as $s): ?>
          <a href="<?= e(url('section.php', ['id' => $s['id']])) ?>" class="card section-tile">
            <span class="strong"><?= e(tr($s, 'title')) ?></span>
            <span class="tiny muted"><?= e(t('articlesN', ['n' => $s['n']])) ?></span>
          </a>
        <?php endforeach ?>
      </div>
    </section>
    <?php endif ?>
  </div>

  <aside class="stack">
    <?php if ($popular): ?>
    <div class="card pad">
      <h2 class="h3 mb-sm"><?= e(t('mostRead')) ?></h2>
      <ol class="popular">
        <?php foreach ($popular as $a): ?>
          <li>
            <a href="<?= e(url('article.php', ['id' => $a['id']])) ?>"><?= e(tr($a, 'title')) ?></a>
            <span class="tiny muted"><?= e(authors_names($a)) ?> · 👁 <?= (int) $a['views'] ?></span>
          </li>
        <?php endforeach ?>
      </ol>
    </div>
    <?php endif ?>

    <div class="card pad">
      <h2 class="h3 mb-sm"><?= e(t('news')) ?></h2>
      <?php if (!$news): ?>
        <p class="muted small"><?= e(t('noNews')) ?></p>
      <?php else: ?>
        <div class="news-mini">
          <?php foreach ($news as $n): ?>
            <a href="<?= e(url('news.php', ['id' => $n['id']])) ?>">
              <span class="tiny muted"><?= e(format_date($n['published_at'])) ?></span>
              <span><?= e(tr($n, 'title')) ?></span>
            </a>
          <?php endforeach ?>
        </div>
        <a href="<?= e(url('news.php')) ?>" class="link small"><?= e(t('allNews')) ?></a>
      <?php endif ?>
    </div>

    <div class="card pad cta-card">
      <h2 class="h3"><?= e(t('ctaTitle')) ?></h2>
      <p class="small"><?= e(t('ctaText')) ?></p>
      <a href="<?= e(url('submit.php')) ?>" class="btn btn-primary btn-block"><?= e(t('submit')) ?></a>
    </div>

    <?php if ($indexing): ?>
    <div class="card pad">
      <h2 class="h3 mb-sm"><?= e(t('indexing')) ?></h2>
      <div class="chips">
        <?php foreach ($indexing as $ix): ?>
          <?php if ($ix['url'] !== ''): ?><a href="<?= e($ix['url']) ?>" class="chip" target="_blank" rel="noopener"><?= e($ix['name']) ?></a><?php else: ?><span class="chip"><?= e($ix['name']) ?></span><?php endif ?>
        <?php endforeach ?>
      </div>
    </div>
    <?php endif ?>
  </aside>
</div>
<?php site_footer();
