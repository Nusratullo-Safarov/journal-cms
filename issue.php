<?php
// Содержание выпуска. Без ?id показывает последний выпуск.
require __DIR__ . '/includes/bootstrap.php';

$id = get_id();
$issue = $id ? get_issue($id) : latest_issue();
if (!$issue) not_found();

$contents = issue_contents($issue['id']);
$label = issue_label($issue);
$isLatest = !$id || ($issue['id'] === (latest_issue()['id'] ?? 0));

site_header($label, [
    'canonical' => abs_url('issue.php', ['id' => $issue['id']]),
    'image' => $issue['cover'],
    'nav' => $isLatest ? 'current' : 'archive',
]);
?>
<div class="issue-head card">
  <?= issue_cover($issue, 'issue-head-cover') ?>
  <div class="stack-sm">
    <h1 class="h1"><?= e($label) ?></h1>
    <?php if (($title = tr($issue, 'title')) !== ''): ?><p class="lead"><?= e($title) ?></p><?php endif ?>
    <?php if ($issue['published_at']): ?><p class="muted small"><?= e(t('publishedOn', ['date' => format_date($issue['published_at'])])) ?></p><?php endif ?>
    <?php if ($issue['pdf'] !== ''): ?>
      <a href="<?= e(file_url($issue['pdf'])) ?>" class="btn btn-primary" target="_blank"><?= e(t('downloadIssue')) ?></a>
    <?php endif ?>
  </div>
</div>

<h2 class="h2"><?= e(t('contents')) ?></h2>
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
