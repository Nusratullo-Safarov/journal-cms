<?php
// Таърихи амалҳо дар панели идоракунӣ. Танҳо барои администратор.
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_admin_role();

$total = (int) db_val('SELECT COUNT(*) FROM activity_log');
[$page, $offset, $pages] = paginate($total, 50);
$rows = db_all('SELECT * FROM activity_log ORDER BY id DESC LIMIT 50 OFFSET ' . $offset);

admin_header(ta('navActivity'), 'activity');
?>
<h1 class="h1"><?= e(ta('navActivity')) ?></h1>
<p class="muted small mb"><?= e(ta('activityHint')) ?></p>
<div class="card">
  <?php if (!$rows): ?><div class="pad muted"><?= e(ta('noActivity')) ?></div><?php endif ?>
  <ul class="activity"><?php foreach ($rows as $act) echo activity_row($act); ?></ul>
</div>
<?= pager($page, $pages) ?>
<?php admin_footer();
