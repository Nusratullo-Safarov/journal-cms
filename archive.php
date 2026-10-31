<?php
require __DIR__ . '/includes/bootstrap.php';

$years = issues_by_year();

site_header(t('archive'), ['canonical' => abs_url('archive.php'), 'nav' => 'archive']);
?>
<h1 class="h1 mb"><?= e(t('archive')) ?></h1>
<?php if (!$years): ?>
  <div class="card pad muted"><?= e(t('noIssues')) ?></div>
<?php endif ?>
<?php foreach ($years as $year => $issues): ?>
  <section class="year-block">
    <h2 class="h2"><?= (int) $year ?></h2>
    <div class="issue-grid">
      <?php foreach ($issues as $i): ?>
        <a href="<?= e(url('issue.php', ['id' => $i['id']])) ?>" class="issue-tile">
          <?= issue_cover($i) ?>
          <span class="issue-tile-label"><?= e(issue_label($i)) ?></span>
          <?php if (($title = tr($i, 'title')) !== ''): ?><span class="tiny muted"><?= e($title) ?></span><?php endif ?>
        </a>
      <?php endforeach ?>
    </div>
  </section>
<?php endforeach ?>
<?php site_footer();
