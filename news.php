<?php
// Список новостей и отдельная новость (news.php?id=5)
require __DIR__ . '/includes/bootstrap.php';

$id = get_id();
if ($id) {
    $n = db_one('SELECT * FROM news WHERE id = ? AND is_published = 1 AND published_at <= NOW()', [$id]);
    if (!$n) not_found();
    $title = tr($n, 'title');
    $body = tr($n, 'body');
    site_header($title, [
        'description' => html_to_text($body),
        'canonical' => abs_url('news.php', ['id' => $n['id']]),
        'image' => $n['image'],
        'nav' => 'news',
    ]);
    ?>
<a href="<?= e(url('news.php')) ?>" class="back-link">← <?= e(t('news')) ?></a>
<article class="card pad prose narrow">
  <div class="muted small"><?= e(format_date($n['published_at'])) ?></div>
  <h1 class="h1"><?= e($title) ?></h1>
  <?php if ($n['image'] !== ''): ?><img src="<?= e(file_url($n['image'])) ?>" alt="" class="news-image"><?php endif ?>
  <?= $body /* HTML очищен sanitize_html() при сохранении */ ?>
</article>
<?php
    site_footer();
    exit;
}

$page = max(1, get_id('page'));
$perPage = 10;
$total = (int) db_val('SELECT COUNT(*) FROM news WHERE is_published = 1 AND published_at <= NOW()');
$items = db_all('SELECT * FROM news WHERE is_published = 1 AND published_at <= NOW() ORDER BY published_at DESC, id DESC LIMIT '
    . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
$pages = max(1, (int) ceil($total / $perPage));

site_header(t('news'), ['canonical' => abs_url('news.php'), 'nav' => 'news']);
?>
<h1 class="h1 mb"><?= e(t('news')) ?></h1>
<?php if (!$items): ?>
  <div class="card pad muted"><?= e(t('noNews')) ?></div>
<?php endif ?>
<div class="news-list">
  <?php foreach ($items as $n): ?>
    <a href="<?= e(url('news.php', ['id' => $n['id']])) ?>" class="card news-card">
      <?php if ($n['image'] !== ''): ?><img src="<?= e(file_url($n['image'])) ?>" alt="" loading="lazy"><?php endif ?>
      <div class="pad stack-sm">
        <div class="tiny muted"><?= e(format_date($n['published_at'])) ?></div>
        <div class="strong"><?= e(tr($n, 'title')) ?></div>
        <p class="small muted"><?= e(html_to_text(tr($n, 'body'), 200)) ?></p>
      </div>
    </a>
  <?php endforeach ?>
</div>
<?php if ($pages > 1): ?>
  <nav class="pager">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a href="<?= e(url('news.php', ['page' => $p > 1 ? $p : null])) ?>"<?= $p === $page ? ' class="active"' : '' ?>><?= $p ?></a>
    <?php endfor ?>
  </nav>
<?php endif ?>
<?php site_footer();
