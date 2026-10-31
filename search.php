<?php
require __DIR__ . '/includes/bootstrap.php';

$q = get_str('q', 100);
$results = mb_strlen($q) >= 2 ? search_articles($q) : [];

site_header($q !== '' ? t('search') . ': ' . $q : t('search'), ['noindex' => true]);
?>
<h1 class="h1 mb"><?= e(t('search')) ?></h1>
<form class="search-form mb">
  <input type="search" name="q" value="<?= e($q) ?>" class="input" placeholder="<?= e(t('searchPlaceholder')) ?>" autofocus>
  <button class="btn btn-primary"><?= e(t('search')) ?></button>
</form>
<?php if ($q === ''): ?>
  <p class="muted small"><?= e(t('searchHint')) ?></p>
<?php else: ?>
  <p class="muted small mb"><?= e(t('found', ['n' => count($results)])) ?></p>
  <?php if ($results): ?>
    <div class="card divided">
      <?php foreach ($results as $a) article_item($a, true); ?>
    </div>
  <?php else: ?>
    <div class="card pad muted"><?= e(t('nothingFound')) ?></div>
  <?php endif ?>
<?php endif ?>
<?php site_footer();
