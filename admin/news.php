<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$items = db_all('SELECT id, title_tj, title_ru, title_en, published_at, is_published FROM news ORDER BY published_at DESC, id DESC LIMIT 300');

admin_header(ta('navNews'), 'news');
?>
<div class="section-head mb">
  <h1 class="h1"><?= e(ta('navNews')) ?></h1>
  <a href="<?= e(url('admin/news-item.php')) ?>" class="btn btn-primary"><?= e(ta('addNews')) ?></a>
</div>
<div class="card scroll-x">
  <table class="table">
    <thead><tr><th><?= e(ta('colTitle')) ?></th><th><?= e(ta('colDate')) ?></th><th><?= e(ta('colStatus')) ?></th></tr></thead>
    <tbody>
      <?php foreach ($items as $n): ?>
        <tr>
          <td><a href="<?= e(url('admin/news-item.php', ['id' => $n['id']])) ?>" class="link strong"><?= e(tr($n, 'title', admin_lang())) ?></a></td>
          <td class="muted nowrap"><?= e(format_date($n['published_at'], true)) ?></td>
          <td><?= yes_no_badge($n['is_published']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$items): ?><div class="empty-box muted"><?= e(ta('noNews')) ?></div><?php endif ?>
</div>
<?php admin_footer();
