<?php
require __DIR__ . '/includes/bootstrap.php';

$issue = latest_issue();
$contents = $issue ? issue_contents($issue['id']) : [];
$news = latest_news(4);
$about = db_one("SELECT * FROM pages WHERE slug = 'about'");

site_header('', ['canonical' => abs_url(), 'nav' => 'home']);
?>
<div class="layout-sidebar">
  <div class="stack-lg">
    <section class="hero card">
      <h1 class="hero-title"><?= e(setting_tr('journal_name')) ?></h1>
      <?php if (($tag = setting_tr('tagline')) !== ''): ?><p class="hero-tagline"><?= e($tag) ?></p><?php endif ?>
      <?php if ($about && ($text = html_to_text(tr($about, 'body'), 400)) !== ''): ?><p class="muted"><?= e($text) ?></p><?php endif ?>
      <div class="row-gap">
        <?php if ($issue): ?><a href="<?= e(url('issue.php', ['id' => $issue['id']])) ?>" class="btn btn-primary"><?= e(t('currentIssue')) ?></a><?php endif ?>
        <a href="<?= e(url('submit.php')) ?>" class="btn btn-ghost"><?= e(t('submit')) ?></a>
      </div>
    </section>

    <section>
      <div class="section-head">
        <h2 class="h2"><?= e(t('latestArticles')) ?><?= $issue ? ' — ' . e(issue_label($issue)) : '' ?></h2>
        <a href="<?= e(url('archive.php')) ?>" class="link small"><?= e(t('allIssues')) ?></a>
      </div>
      <?php if (!$issue): ?>
        <div class="card pad muted"><?= e(t('noIssues')) ?></div>
      <?php elseif (!$contents): ?>
        <div class="card pad muted"><?= e(t('noArticles')) ?></div>
      <?php else: ?>
        <div class="card issue-preview">
          <a href="<?= e(url('issue.php', ['id' => $issue['id']])) ?>" class="issue-preview-cover"><?= issue_cover($issue) ?></a>
          <div class="divided grow">
            <?php foreach ($contents as $group) foreach ($group['articles'] as $a) article_item($a); ?>
          </div>
        </div>
      <?php endif ?>
    </section>
  </div>

  <aside class="stack">
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
    <?php
    $info = array_filter([
        t('publisher') => setting_tr('publisher'),
        t('issnPrint') => setting('issn_print'),
        t('issnOnline') => setting('issn_online'),
        t('frequency') => setting_tr('frequency'),
    ], fn($v) => $v !== '');
    if ($info): ?>
    <div class="card pad">
      <h2 class="h3 mb-sm"><?= e(t('aboutJournal')) ?></h2>
      <dl class="info-list">
        <?php foreach ($info as $k => $v): ?><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd><?php endforeach ?>
      </dl>
    </div>
    <?php endif ?>
  </aside>
</div>
<?php site_footer();
