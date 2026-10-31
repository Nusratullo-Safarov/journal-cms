<?php
// Саҳифаи муаллиф: author.php?name=Раҳимов%20А.С.
require __DIR__ . '/includes/bootstrap.php';

$name = get_str('name', 150);
$articles = $name !== '' ? author_articles($name) : [];
if (!$articles) not_found();

$affiliations = array_values(array_unique(array_filter(array_column($articles, '_affiliation'))));
$coauthors = [];
foreach ($articles as $a) {
    foreach (parse_authors(tr($a, 'authors')) as $au) {
        if (mb_strtolower($au['name']) !== mb_strtolower($name)) $coauthors[$au['name']] = ($coauthors[$au['name']] ?? 0) + 1;
    }
}
arsort($coauthors);
$views = array_sum(array_column($articles, 'views'));
$downloads = array_sum(array_column($articles, 'downloads'));

site_header($name, ['canonical' => abs_url('author.php', ['name' => $name]), 'nav' => 'authors', 'type' => 'profile']);
?>
<?= breadcrumbs([[t('home'), url()], [t('authors'), url('authors.php')], [$name, null]]) ?>
<div class="layout-sidebar">
  <div class="stack">
    <div class="card pad-lg author-head">
      <span class="avatar avatar-lg" aria-hidden="true"><?= e(mb_substr($name, 0, 1)) ?></span>
      <div>
        <h1 class="h1"><?= e($name) ?></h1>
        <?php foreach ($affiliations as $af): ?><div class="muted"><?= e($af) ?></div><?php endforeach ?>
      </div>
    </div>
    <section>
      <h2 class="h2 mb"><?= e(t('publications')) ?> <span class="muted">(<?= count($articles) ?>)</span></h2>
      <div class="card divided"><?php foreach ($articles as $a) article_item($a, true); ?></div>
    </section>
  </div>
  <aside class="stack">
    <div class="card pad">
      <div class="mini-stats">
        <div><b><?= count($articles) ?></b><span><?= e(t('statArticles')) ?></span></div>
        <div><b><?= (int) $views ?></b><span><?= e(t('viewsLabel')) ?></span></div>
        <div><b><?= (int) $downloads ?></b><span><?= e(t('downloadsLabel')) ?></span></div>
      </div>
    </div>
    <?php if ($coauthors): ?>
      <div class="card pad">
        <h2 class="h3 mb-sm"><?= e(t('coauthors')) ?></h2>
        <div class="stack-sm">
          <?php foreach (array_slice($coauthors, 0, 10, true) as $co => $n): ?>
            <a href="<?= e(url('author.php', ['name' => $co])) ?>" class="author-mini"><span class="avatar" aria-hidden="true"><?= e(mb_substr((string) $co, 0, 1)) ?></span><?= e((string) $co) ?></a>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>
  </aside>
</div>
<?php site_footer();
