<?php
// Рӯйхати муаллифон бо тартиби алифбо ва шумораи мақолаҳо
require __DIR__ . '/includes/bootstrap.php';

$q = get_str('q', 100);
$authors = authors_index();
if ($q !== '') $authors = array_values(array_filter($authors, fn($au) => mb_stripos($au['name'], $q) !== false));
$groups = [];
foreach ($authors as $au) $groups[mb_strtoupper(mb_substr($au['name'], 0, 1))][] = $au;

site_header(t('authors'), ['canonical' => abs_url('authors.php'), 'nav' => 'authors']);
?>
<?= breadcrumbs([[t('home'), url()], [t('authors'), null]]) ?>
<div class="section-head">
  <div>
    <h1 class="h1"><?= e(t('authors')) ?></h1>
    <p class="muted small"><?= e(t('authorsCount', ['n' => count($authors)])) ?></p>
  </div>
  <form class="search-form compact">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('findAuthor')) ?>" class="input" aria-label="<?= e(t('findAuthor')) ?>">
  </form>
</div>
<?php if ($groups): ?>
  <nav class="letters" aria-label="A–Я">
    <?php foreach (array_keys($groups) as $letter): ?><a href="#l-<?= e(bin2hex($letter)) ?>"><?= e($letter) ?></a><?php endforeach ?>
  </nav>
<?php else: ?>
  <div class="card pad muted"><?= e(t('nothingFound')) ?></div>
<?php endif ?>
<?php foreach ($groups as $letter => $list): ?>
  <section class="letter-group" id="l-<?= e(bin2hex($letter)) ?>">
    <h2 class="letter"><?= e($letter) ?></h2>
    <div class="author-grid">
      <?php foreach ($list as $au): ?>
        <a href="<?= e(url('author.php', ['name' => $au['name']])) ?>" class="card author-card">
          <span class="avatar" aria-hidden="true"><?= e(mb_substr($au['name'], 0, 1)) ?></span>
          <span class="grow"><span class="strong"><?= e($au['name']) ?></span><span class="tiny muted block"><?= e(t('articlesN', ['n' => $au['n']])) ?></span></span>
        </a>
      <?php endforeach ?>
    </div>
  </section>
<?php endforeach ?>
<?php site_footer();
