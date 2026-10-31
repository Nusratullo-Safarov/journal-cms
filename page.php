<?php
// Текстовые страницы: «О журнале», «Редколлегия», «Требования к авторам» и т.д. — page.php?slug=about
require __DIR__ . '/includes/bootstrap.php';

$slug = get_str('slug', 60);
$page = $slug !== '' ? db_one('SELECT * FROM pages WHERE slug = ?', [$slug]) : null;
if (!$page) not_found();

$title = tr($page, 'title');
$body = tr($page, 'body');

site_header($title, [
    'description' => html_to_text($body),
    'canonical' => abs_url('page.php', ['slug' => $page['slug']]),
    'nav' => 'page-' . $page['slug'],
]);
?>
<article class="card pad prose narrow">
  <h1 class="h1"><?= e($title) ?></h1>
  <?= $body /* HTML очищен sanitize_html() при сохранении */ ?>
</article>
<?php site_footer();
