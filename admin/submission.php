<?php
// Просмотр поданной статьи, скачивание файла (?download=1), смена статуса, удаление
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

$id = get_id();
$s = db_one('SELECT * FROM submissions WHERE id = ?', [$id]);
if (!$s) admin_not_found();
$path = SUBMISSIONS_DIR . '/' . $s['file'];
$fileOk = preg_match('/^[a-f0-9]{24}\.(pdf|docx?)$/', $s['file']) && is_file($path);

if (isset($_GET['download'])) {
    if (!$fileOk) admin_not_found();
    $ext = pathinfo($s['file'], PATHINFO_EXTENSION);
    $types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $name = 'submission-' . $s['id'] . '.' . $ext;
    header('Content-Type: ' . $types[$ext]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($s['original_name'] ?: $name));
    readfile($path);
    exit;
}

if (is_post()) {
    admin_require_csrf();
    if (post_str('action', 20) === 'delete') {
        db_exec('DELETE FROM submissions WHERE id = ?', [$id]);
        log_action('delete', 'submission', $id, $s['title']);
        if ($fileOk) @unlink($path);
        flash('ok', ta('deleted'));
        redirect('admin/submissions.php');
    }
    $status = post_str('status', 20);
    if (in_array($status, SUBMISSION_STATUSES, true)) {
        db_exec('UPDATE submissions SET status = ?, notes = ? WHERE id = ?', [$status, post_str('notes', 10000), $id]);
        log_action('update', 'submission', $id, $s['title'] . ' → ' . ta("sub_$status"));
        flash('ok', ta('saved'));
    }
    redirect('admin/submission.php', ['id' => $id]);
}

admin_header(ta('submissionN', ['n' => $s['id']]), 'submissions');
?>
<div class="narrow stack">
  <a href="<?= e(url('admin/submissions.php')) ?>" class="back-link"><?= e(ta('backSubmissions')) ?></a>
  <div class="section-head">
    <h1 class="h1"><?= e(ta('submissionN', ['n' => $s['id']])) ?></h1>
    <?= submission_badge($s['status']) ?>
  </div>
  <div class="card pad info-grid">
    <div class="muted"><?= e(ta('colDate')) ?></div><div><?= e(format_date($s['created_at'], true)) ?></div>
    <div class="muted"><?= e(ta('colTitle')) ?></div><div class="strong"><?= e($s['title']) ?></div>
    <div class="muted"><?= e(ta('colAuthor')) ?></div><div><?= e($s['name']) ?></div>
    <div class="muted">Email</div><div><a href="mailto:<?= e($s['email']) ?>" class="link"><?= e($s['email']) ?></a></div>
    <?php if ($s['phone'] !== ''): ?><div class="muted"><?= e(ta('colPhone')) ?></div><div><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $s['phone'])) ?>" class="link"><?= e($s['phone']) ?></a></div><?php endif ?>
    <?php if ($s['affiliation'] !== ''): ?><div class="muted"><?= e(ta('colAffiliation')) ?></div><div><?= e($s['affiliation']) ?></div><?php endif ?>
    <?php if ($s['comment'] !== ''): ?><div class="muted"><?= e(ta('colComment')) ?></div><div class="pre-line"><?= e($s['comment']) ?></div><?php endif ?>
    <div class="muted"><?= e(ta('colFile')) ?></div>
    <div><?php if ($fileOk): ?><a href="<?= e(url('admin/submission.php', ['id' => $s['id'], 'download' => 1])) ?>" class="btn btn-ghost">⬇ <?= e($s['original_name']) ?></a><?php else: ?><span class="text-red">—</span><?php endif ?></div>
  </div>
  <form method="post" class="card pad stack">
    <?= csrf_field() ?>
    <div>
      <label class="label" for="notes"><?= e(ta('editorNotes')) ?></label>
      <textarea id="notes" name="notes" rows="4" class="input" placeholder="<?= e(ta('editorNotesHint')) ?>"><?= e($s['notes'] ?? '') ?></textarea>
    </div>
    <div class="row-gap align-end">
    <div>
      <label class="label" for="status"><?= e(ta('changeStatus')) ?></label>
      <select id="status" name="status" class="input w-auto">
        <?php foreach (SUBMISSION_STATUSES as $st): ?><option value="<?= $st ?>"<?= $s['status'] === $st ? ' selected' : '' ?>><?= e(ta("sub_$st")) ?></option><?php endforeach ?>
      </select>
    </div>
    <button class="btn btn-primary"><?= e(ta('save')) ?></button>
    <a href="mailto:<?= e($s['email']) ?>?subject=<?= e(rawurlencode(ta('submissionN', ['n' => $s['id']]) . ': ' . $s['title'])) ?>" class="btn btn-ghost"><?= e(ta('replyEmail')) ?></a>
    </div>
  </form>
  <form method="post" data-confirm="<?= e(ta('confirmDelete')) ?>">
    <?= csrf_field() ?><button class="link-danger small" name="action" value="delete"><?= e(ta('deleteSubmission')) ?></button>
  </form>
</div>
<?php admin_footer();
