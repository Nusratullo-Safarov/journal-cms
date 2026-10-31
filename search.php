<?php
require __DIR__ . '/includes/bootstrap.php';

$q = get_str('q', 100);
$year = (int) get_str('year', 4);
$sectionId = get_id('section');
$searching = mb_strlen($q) >= 2 || $year || $sectionId;
$total = 0;
$rows = [];
$page = 1;
$pages = 1;
if ($searching) {
    [, $total] = search_articles_filtered($q, $year, $sectionId, 1, 0);
    [$page, $offset, $pages] = paginate($total, 20);
    [$rows] = search_articles_filtered($q, $year, $sectionId, 20, $offset);
}

site_header($q !== '' ? t('search') . ': ' . $q : t('search'), ['noindex' => true]);
?>
<?= breadcrumbs([[t('home'), url()], [t('search'), null]]) ?>
<h1 class="h1 mb"><?= e(t('search')) ?></h1>
<form class="card pad search-panel">
  <input type="search" name="q" value="<?= e($q) ?>" class="input input-lg" placeholder="<?= e(t('searchPlaceholder')) ?>" aria-label="<?= e(t('search')) ?>" autofocus>
  <select name="year" class="input" aria-label="<?= e(t('year')) ?>">
    <option value=""><?= e(t('allYears')) ?></option>
    <?php foreach (published_years() as $y): ?><option value="<?= $y ?>"<?= $year === $y ? ' selected' : '' ?>><?= $y ?></option><?php endforeach ?>
  </select>
  <select name="section" class="input" aria-label="<?= e(t('section')) ?>">
    <option value=""><?= e(t('allSections')) ?></option>
    <?php foreach (get_sections() as $s): ?><option value="<?= (int) $s['id'] ?>"<?= $sectionId === $s['id'] ? ' selected' : '' ?>><?= e(tr($s, 'title')) ?></option><?php endforeach ?>
  </select>
  <button class="btn btn-primary"><?= e(t('search')) ?></button>
</form>
<?php if (!$searching): ?>
  <p class="muted small"><?= e(t('searchHint')) ?></p>
<?php else: ?>
  <p class="muted small mb"><?= e(t('found', ['n' => $total])) ?></p>
  <?php if ($rows): ?>
    <div class="card divided"><?php foreach ($rows as $a) article_item($a, true, mb_strlen($q) >= 2 ? $q : ''); ?></div>
    <?= pager($page, $pages) ?>
  <?php else: ?>
    <div class="card pad muted"><?= e(t('nothingFound')) ?></div>
  <?php endif ?>
<?php endif ?>
<?php site_footer();
