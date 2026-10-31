<?php
// Подача статьи авторами. Файлы сохраняются в storage/submissions/ — эта папка закрыта от доступа из браузера.
require __DIR__ . '/includes/bootstrap.php';

const SUBMISSION_MAX_SIZE = 10 * 1024 * 1024;

$open = setting('submissions_open', '1') === '1';
$error = '';
$doneId = (int) flash('submitted');

if ($open && is_post()) {
    $name = post_str('name', 150);
    $email = post_str('email', 150);
    $title = post_str('title', 500);
    $file = $_FILES['file'] ?? null;
    if (!csrf_ok()) $error = post_too_large() ? t('errFileSize') : t('errCsrf');
    elseif (mb_strlen($name) < 3) $error = t('errName');
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = t('errEmail');
    elseif ($title === '') $error = t('errTitle');
    elseif (!has_upload($file)) $error = t('errFile');
    elseif (empty($_POST['agree'])) $error = t('errAgree');
    elseif (!rate_limit('submit', 5, 3600)) $error = t('errRate');
    else {
        try {
            $ext = check_upload($file, SUBMISSION_MAX_SIZE, ['pdf', 'doc', 'docx']);
            if (!is_dir(SUBMISSIONS_DIR)) mkdir(SUBMISSIONS_DIR, 0755, true);
            $stored = bin2hex(random_bytes(12)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], SUBMISSIONS_DIR . '/' . $stored)) throw new UploadError('fail');
            $original = mb_substr(preg_replace('/[^\p{L}\p{N}._\- ]/u', '_', basename((string) $file['name'])), 0, 200);
            db_exec('INSERT INTO submissions (name, email, phone, affiliation, title, comment, file, original_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                $name, $email, post_str('phone', 30), post_str('affiliation', 300), $title, post_str('comment', 3000), $stored, $original,
            ]);
            $id = (int) db()->lastInsertId();
            notify_telegram("📄 Мақолаи нав №$id\n$title\n$name, $email");
            flash('submitted', $id);
            redirect('submit.php');
        } catch (UploadError $ex) {
            $error = match ($ex->getMessage()) { 'size' => t('errFileSize'), 'type' => t('errFileType'), default => t('errGeneric') };
        }
    }
}

$guidelines = db_one("SELECT slug, title_tj, title_ru, title_en FROM pages WHERE slug = 'for-authors'");

site_header(t('submit'), ['canonical' => abs_url('submit.php'), 'nav' => 'submit']);
?>
<div class="narrow">
  <h1 class="h1 mb"><?= e(t('submit')) ?></h1>
  <?php if ($doneId): ?>
    <div class="alert-ok mb"><?= e(t('submitDone', ['n' => $doneId])) ?></div>
  <?php elseif (!$open): ?>
    <div class="card pad muted"><?= e(t('submissionsClosed')) ?></div>
  <?php endif ?>

  <?php if ($open && !$doneId): ?>
  <form method="post" enctype="multipart/form-data" class="card pad stack" data-submit-once>
    <?= csrf_field() ?>
    <p class="muted small">
      <?= e(t('submitIntro')) ?>
      <?php if ($guidelines): ?><a href="<?= e(url('page.php', ['slug' => $guidelines['slug']])) ?>" class="link"><?= e(tr($guidelines, 'title')) ?> →</a><?php endif ?>
    </p>
    <div class="grid-2">
      <div>
        <label class="label" for="name"><?= e(t('fName')) ?> *</label>
        <input id="name" name="name" required maxlength="150" class="input" value="<?= e(post_str('name', 150)) ?>">
      </div>
      <div>
        <label class="label" for="email"><?= e(t('fEmail')) ?> *</label>
        <input id="email" name="email" type="email" required maxlength="150" class="input" value="<?= e(post_str('email', 150)) ?>">
      </div>
      <div>
        <label class="label" for="phone"><?= e(t('fPhone')) ?></label>
        <input id="phone" name="phone" type="tel" maxlength="30" class="input" placeholder="+992 ..." value="<?= e(post_str('phone', 30)) ?>">
      </div>
      <div>
        <label class="label" for="affiliation"><?= e(t('fAffiliation')) ?></label>
        <input id="affiliation" name="affiliation" maxlength="300" class="input" value="<?= e(post_str('affiliation', 300)) ?>">
      </div>
    </div>
    <div>
      <label class="label" for="title"><?= e(t('fTitle')) ?> *</label>
      <input id="title" name="title" required maxlength="500" class="input" value="<?= e(post_str('title', 500)) ?>">
    </div>
    <div>
      <label class="label" for="comment"><?= e(t('fComment')) ?></label>
      <textarea id="comment" name="comment" rows="3" maxlength="3000" class="input"><?= e(post_str('comment', 3000)) ?></textarea>
    </div>
    <div>
      <label class="label" for="file"><?= e(t('fFile')) ?> *</label>
      <input id="file" name="file" type="file" required accept=".doc,.docx,.pdf,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
    </div>
    <label class="check"><input type="checkbox" name="agree" value="1" required<?= post_str('agree') ? ' checked' : '' ?>> <?= e(t('fAgree')) ?></label>
    <?php if ($error !== ''): ?><div class="alert-error"><?= e($error) ?></div><?php endif ?>
    <div><button class="btn btn-primary"><?= e(t('send')) ?></button></div>
  </form>
  <?php endif ?>
</div>
<?php site_footer();
