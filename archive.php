<?php
require __DIR__ . '/includes/bootstrap.php';

$years = issues_by_year();

site_header(t('archive'), ['canonical' => abs_url('archive.php'), 'nav' => 'archive']);
?>
<?= breadcrumbs([[t('home'), url()], [t('archive'), null]]) ?>
<h1 class="h1 mb"><?= e(t('archive')) ?></h1>
<?php if ($years): ?>
  <nav class="letters" aria-label="<?= e(t('year')) ?>"><?php foreach (array_keys($years) as $y): ?><a href="#y<?= (int) $y ?>"><?= (int) $y ?></a><?php endforeach ?></nav>
<?php endif ?>
<?php if (!$years): ?>
  <div class="card pad muted"><?= e(t('noIssues')) ?></div>
<?php endif ?>
<?php foreach ($years as $year => $issues): ?>
  <section class="year-block" id="y<?= (int) $year ?>">
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
