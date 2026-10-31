<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$issues = db_all('SELECT i.*, (SELECT COUNT(*) FROM articles a WHERE a.issue_id = i.id) AS n
                  FROM issues i ORDER BY i.year DESC, i.published_at DESC, i.id DESC');

admin_header(ta('navIssues'), 'issues');
?>
<div class="section-head mb">
  <h1 class="h1"><?= e(ta('navIssues')) ?></h1>
  <a href="<?= e(url('admin/issue.php')) ?>" class="btn btn-primary"><?= e(ta('addIssue')) ?></a>
</div>
<div class="card scroll-x">
  <table class="table">
    <thead><tr>
      <th><?= e(ta('colIssue')) ?></th><th><?= e(ta('colDate')) ?></th><th class="right"><?= e(ta('colArticles')) ?></th><th><?= e(ta('colStatus')) ?></th><th></th>
    </tr></thead>
    <tbody>
      <?php foreach ($issues as $i): ?>
        <tr>
          <td><a href="<?= e(url('admin/issue.php', ['id' => $i['id']])) ?>" class="link strong"><?= e(issue_label($i)) ?></a>
            <?php if (($title = tr($i, 'title')) !== ''): ?><div class="tiny muted"><?= e($title) ?></div><?php endif ?></td>
          <td class="muted nowrap"><?= e(format_date($i['published_at'])) ?></td>
          <td class="right"><a href="<?= e(url('admin/articles.php', ['issue' => $i['id']])) ?>" class="link"><?= (int) $i['n'] ?></a></td>
          <td><?= yes_no_badge($i['is_published']) ?></td>
          <td class="right"><a href="<?= e(url('admin/article.php', ['issue' => $i['id']])) ?>" class="small link"><?= e(ta('addArticleHere')) ?></a></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <?php if (!$issues): ?><div class="empty-box muted"><?= e(ta('noIssues')) ?></div><?php endif ?>
</div>
<?php admin_footer();
