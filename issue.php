<?php
// Содержание выпуска. Без ?id показывает последний выпуск.
require __DIR__ . '/includes/bootstrap.php';

$id = get_id();
$preview = is_preview();
$issue = $id ? get_issue($id, !$preview) : latest_issue();
if (!$issue) not_found();

$contents = issue_contents($issue['id'], !$preview);
$articleCount = array_sum(array_map(fn($g) => count($g['articles']), $contents));
$label = issue_label($issue);
$isLatest = !$id || ($issue['id'] === (latest_issue()['id'] ?? 0));

site_header($label, [
    'canonical' => abs_url('issue.php', ['id' => $issue['id']]),
    'image' => $issue['cover'],
    'nav' => $isLatest ? 'current' : 'archive',
    'noindex' => !$issue['is_published'],
]);
?>
<?= breadcrumbs([[t('home'), url()], [t('archive'), url('archive.php')], [$label, null]]) ?>
<?php if (!$issue['is_published']): ?><div class="alert-warn mb"><?= e(t('previewNotice')) ?></div><?php endif ?>
<div class="issue-head card">
  <?= issue_cover($issue, 'issue-head-cover') ?>
  <div class="stack-sm">
    <h1 class="h1"><?= e($label) ?></h1>
    <?php if (($title = tr($issue, 'title')) !== ''): ?><p class="lead"><?= e($title) ?></p><?php endif ?>
    <div class="chips">
      <?php if ($issue['published_at']): ?><span class="chip"><?= e(t('publishedOn', ['date' => format_date($issue['published_at'])])) ?></span><?php endif ?>
      <span class="chip"><?= e(t('articlesN', ['n' => $articleCount])) ?></span>
    </div>
    <?php if ($issue['pdf'] !== ''): ?>
      <a href="<?= e(file_url($issue['pdf'])) ?>" class="btn btn-primary" target="_blank"><?= e(t('downloadIssue')) ?></a>
    <?php endif ?>
  </div>
</div>

<h2 class="h2 mb"><?= e(t('contents')) ?></h2>
<?php if (!$contents): ?>
  <div class="card pad muted"><?= e(t('noArticles')) ?></div>
<?php endif ?>
<?php foreach ($contents as $group): ?>
  <section class="card toc-section">
    <h3 class="toc-section-title"><?= e($group['section'] ? tr($group['section'], 'title') : t('otherSection')) ?></h3>
    <div class="divided">
      <?php foreach ($group['articles'] as $a) article_item($a); ?>
    </div>
  </section>
<?php endforeach ?>
<?php site_footer();
