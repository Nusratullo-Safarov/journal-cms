<?php
// Настройки журнала: название, ISSN, контакты, логотип, цвет, Telegram. Только для роли admin.
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_admin_role();

const LANG_SETTINGS = ['journal_name', 'tagline', 'publisher', 'frequency', 'address', 'announcement'];
const PLAIN_SETTINGS = ['issn_print' => 20, 'issn_online' => 20, 'email' => 150, 'phone' => 30, 'telegram_bot_token' => 100, 'telegram_chat_id' => 50];

$row = settings_all();
$error = null;
if (is_post()) {
    admin_require_csrf();
    $values = post_lang_fields(LANG_SETTINGS, 300);
    foreach (PLAIN_SETTINGS as $k => $max) $values[$k] = post_str($k, $max);
    $values['color'] = strtolower(post_str('color', 7));
    $values['submissions_open'] = (string) post_bool('submissions_open');
    $values['indexing'] = str_replace("\r\n", "\n", post_str('indexing', 3000));
    if ($values['journal_name_tj'] === '' && $values['journal_name_ru'] === '') $error = ta('errJournalName');
    elseif ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $error = ta('errEmail');
    elseif (!preg_match('/^#[0-9a-f]{6}$/', $values['color'])) $error = ta('errColor');
    else {
        [$files, $error] = handle_files(['logo' => 'image'], $row);
        if (!$error) {
            save_settings($values + $files);
            cleanup_replaced_files($row, $files);
            log_action('update', 'settings');
            flash('ok', ta('saved'));
            redirect('admin/settings.php');
        }
    }
}

admin_header(ta('navSettings'), 'settings');
?>
<h1 class="h1 mb"><?= e(ta('navSettings')) ?></h1>
<?php if ($error): ?><div class="alert-error mb"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="card pad form-grid" data-dirty-check>
  <?= csrf_field() ?>
  <div class="stack">
    <h2 class="h3"><?= e(ta('setJournal')) ?></h2>
    <?php lang_tabs([
        ['journal_name', ta('sJournalName'), 'input', ['max' => 300, 'required' => true]],
        ['tagline', ta('sTagline'), 'input', ['max' => 300]],
        ['publisher', ta('sPublisher'), 'input', ['max' => 300]],
        ['frequency', ta('sFrequency'), 'input', ['max' => 300]],
        ['address', ta('sAddress'), 'input', ['max' => 300]],
        ['announcement', ta('sAnnouncement'), 'input', ['max' => 300, 'hint' => ta('hintAnnouncement')]],
    ], $row) ?>
    <div>
      <label class="label" for="indexing"><?= e(ta('sIndexing')) ?></label>
      <textarea id="indexing" name="indexing" rows="4" class="input" placeholder="Google Scholar | https://scholar.google.com"><?= e(form_val($row, 'indexing')) ?></textarea>
      <p class="tiny muted"><?= e(ta('hintIndexing')) ?></p>
    </div>
    <div class="alert-ok small"><?= e(ta('oaiHint')) ?> <code><?= e(abs_url('oai.php')) ?></code></div>
  </div>
  <div class="stack">
    <div class="grid-2">
      <div><label class="label" for="issn_print"><?= e(ta('sIssnPrint')) ?></label><input id="issn_print" name="issn_print" maxlength="20" placeholder="0000-0000" class="input" value="<?= e(form_val($row, 'issn_print')) ?>"></div>
      <div><label class="label" for="issn_online"><?= e(ta('sIssnOnline')) ?></label><input id="issn_online" name="issn_online" maxlength="20" placeholder="0000-0000" class="input" value="<?= e(form_val($row, 'issn_online')) ?>"></div>
      <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" maxlength="150" class="input" value="<?= e(form_val($row, 'email')) ?>"></div>
      <div><label class="label" for="phone"><?= e(ta('colPhone')) ?></label><input id="phone" name="phone" maxlength="30" class="input" value="<?= e(form_val($row, 'phone')) ?>"></div>
    </div>
    <h2 class="h3"><?= e(ta('setDesign')) ?></h2>
    <div class="row-gap">
      <div><label class="label" for="color"><?= e(ta('sColor')) ?></label><input id="color" name="color" type="color" class="color-input" value="<?= e(form_val($row, 'color') ?: '#1d4ed8') ?>"></div>
    </div>
    <?php file_field('logo', ta('sLogo'), $row['logo'] ?? '', 'image') ?>
    <h2 class="h3"><?= e(ta('setSubmissions')) ?></h2>
    <label class="check"><input type="checkbox" name="submissions_open" value="1"<?= (is_post() ? post_bool('submissions_open') : ($row['submissions_open'] ?? '1') === '1') ? ' checked' : '' ?>> <?= e(ta('sSubmissionsOpen')) ?></label>
    <div class="grid-2">
      <div><label class="label" for="telegram_bot_token"><?= e(ta('sTgToken')) ?></label><input id="telegram_bot_token" name="telegram_bot_token" maxlength="100" autocomplete="off" class="input" value="<?= e(form_val($row, 'telegram_bot_token')) ?>"></div>
      <div><label class="label" for="telegram_chat_id"><?= e(ta('sTgChat')) ?></label><input id="telegram_chat_id" name="telegram_chat_id" maxlength="50" class="input" value="<?= e(form_val($row, 'telegram_chat_id')) ?>"></div>
    </div>
    <p class="tiny muted"><?= e(ta('hintTelegram')) ?></p>
  </div>
  <div class="form-actions"><button class="btn btn-primary"><?= e(ta('save')) ?></button></div>
</form>
<?php admin_footer();
