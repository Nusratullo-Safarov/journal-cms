<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$status = in_array(get_str('status', 20), SUBMISSION_STATUSES, true) ? get_str('status', 20) : '';
$items = db_all('SELECT id, name, email, title, status, created_at FROM submissions' . ($status ? ' WHERE status = ?' : '') . ' ORDER BY id DESC LIMIT 500',
    $status ? [$status] : []);

admin_header(ta('navSubmissions'), 'submissions');
?>
<h1 class="h1 mb"><?= e(ta('navSubmissions')) ?></h1>
<div class="tabs mb">
  <a href="<?= e(url('admin/submissions.php')) ?>"<?= $status === '' ? ' class="active"' : '' ?>><?= e(ta('all')) ?></a>
  <?php foreach (SUBMISSION_STATUSES as $s): ?>
    <a href="<?= e(url('admin/submissions.php', ['status' => $s])) ?>"<?= $status === $s ? ' class="active"' : '' ?>><?= e(ta("sub_$s")) ?></a>
  <?php endforeach ?>
</div>
<div class="card scroll-x">
  <table class="table">
    <thead><tr><th>№</th><th><?= e(ta('colDate')) ?></th><th><?= e(ta('colAuthor')) ?></th><th><?= e(ta('colTitle')) ?></th><th><?= e(ta('colStatus')) ?></th></tr></thead>
    <tbody>
      <?php foreach ($items as $s): ?>
        <tr>
          <td><a href="<?= e(url('admin/submission.php', ['id' => $s['id']])) ?>" class="link strong">№<?= (int) $s['id'] ?></a></td>
          <td class="muted nowrap"><?= e(format_date($s['created_at'], true)) ?></td>
          <td><?= e($s['name']) ?><div class="tiny muted"><?= e($s['email']) ?></div></td>
          <td><a href="<?= e(url('admin/submission.php', ['id' => $s['id']])) ?>"><?= e($s['title']) ?></a></td>
          <td><?= submission_badge($s['status']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$items): ?><div class="empty-box muted"><?= e(ta('noSubmissions')) ?></div><?php endif ?>
</div>
<?php admin_footer();
