<?php
// Ҳамаи мақолаҳои як рубрика: section.php?id=3
require __DIR__ . '/includes/bootstrap.php';

$section = array_column(get_sections(), null, 'id')[get_id()] ?? null;
if (!$section) not_found();

[$rows, $total] = search_articles_filtered('', 0, (int) $section['id'], 1, 0);
[$page, $offset, $pages] = paginate($total, 20);
[$rows] = search_articles_filtered('', 0, (int) $section['id'], 20, $offset);
$title = tr($section, 'title');

site_header($title, ['canonical' => abs_url('section.php', ['id' => $section['id'], 'page' => $page > 1 ? $page : null])]);
?>
<?= breadcrumbs([[t('home'), url()], [t('sections'), null], [$title, null]]) ?>
<div class="page-head">
  <div class="kicker"><?= e(t('section')) ?></div>
  <h1 class="h1"><?= e($title) ?></h1>
  <p class="muted small"><?= e(t('articlesN', ['n' => $total])) ?></p>
</div>
<?php if (!$rows): ?>
  <div class="card pad muted"><?= e(t('noArticles')) ?></div>
<?php else: ?>
  <div class="card divided"><?php foreach ($rows as $a) article_item($a, true); ?></div>
<?php endif ?>
<?= pager($page, $pages) ?>
<?php site_footer();
