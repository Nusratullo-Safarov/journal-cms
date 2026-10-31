<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$pages = db_all('SELECT id, slug, title_tj, title_ru, title_en, sort, in_menu FROM pages ORDER BY sort, id');

admin_header(ta('navPages'), 'pages');
?>
<div class="section-head mb">
  <div>
    <h1 class="h1"><?= e(ta('navPages')) ?></h1>
    <p class="muted small"><?= e(ta('pagesHint')) ?></p>
  </div>
  <a href="<?= e(url('admin/page.php')) ?>" class="btn btn-primary"><?= e(ta('addPage')) ?></a>
</div>
<div class="card scroll-x">
  <table class="table">
    <thead><tr><th><?= e(ta('colTitle')) ?></th><th><?= e(ta('fSlug')) ?></th><th><?= e(ta('fInMenu')) ?></th><th class="right"><?= e(ta('fSort')) ?></th></tr></thead>
    <tbody>
      <?php foreach ($pages as $p): ?>
        <tr>
          <td><a href="<?= e(url('admin/page.php', ['id' => $p['id']])) ?>" class="link strong"><?= e(tr($p, 'title', admin_lang())) ?></a></td>
          <td class="muted"><a href="<?= e(url('page.php', ['slug' => $p['slug']])) ?>" target="_blank"><?= e($p['slug']) ?> ↗</a></td>
          <td><?= $p['in_menu'] ? '✓' : '—' ?></td>
          <td class="right muted"><?= (int) $p['sort'] ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php admin_footer();
