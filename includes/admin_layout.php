<?php
// Разметка и элементы форм админ-панели. Подключается в каждой странице admin/*.php после bootstrap.php.

function admin_header(string $title, string $active = ''): void
{
    $user = current_user();
    $newSubs = (int) db_val("SELECT COUNT(*) FROM submissions WHERE status = 'new'");
    $nav = [
        'overview' => ['admin/', ta('navOverview')],
        'issues' => ['admin/issues.php', ta('navIssues')],
        'articles' => ['admin/articles.php', ta('navArticles')],
        'submissions' => ['admin/submissions.php', ta('navSubmissions') . ($newSubs ? " ($newSubs)" : '')],
        'news' => ['admin/news.php', ta('navNews')],
        'pages' => ['admin/pages.php', ta('navPages')],
        'sections' => ['admin/sections.php', ta('navSections')],
    ];
    if (is_admin_role()) {
        $nav['settings'] = ['admin/settings.php', ta('navSettings')];
        $nav['users'] = ['admin/users.php', ta('navUsers')];
    }
    ?>
<!DOCTYPE html>
<html lang="<?= admin_lang() === 'ru' ? 'ru' : 'tg' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> — <?= e(ta('panel')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/style.css')) ?>">
<style>:root { --brand: <?= brand_color() ?>; }</style>
</head>
<body class="admin">
<aside class="admin-side">
  <a href="<?= e(url('admin/')) ?>" class="admin-brand"><?= logo_html() ?><span><?= e(ta('panel')) ?></span></a>
  <nav class="admin-nav">
    <?php foreach ($nav as $key => [$href, $label]): ?>
      <a href="<?= e(url($href)) ?>"<?= $key === $active ? ' class="active"' : '' ?>><?= e($label) ?></a>
    <?php endforeach ?>
    <a href="<?= e(url()) ?>" target="_blank"><?= e(ta('openSite')) ?></a>
  </nav>
  <div class="admin-user">
    <a href="<?= e(url('admin/profile.php')) ?>"<?= $active === 'profile' ? ' class="active"' : '' ?>>👤 <?= e($user['name'] !== '' ? $user['name'] : $user['login']) ?></a>
    <form method="post" action="<?= e(url('admin/logout.php')) ?>">
      <?= csrf_field() ?>
      <button><?= e(ta('logout')) ?></button>
    </form>
    <?= admin_lang_switch() ?>
  </div>
</aside>
<main class="admin-main">
<?php if ($msg = flash('ok')): ?><div class="alert-ok mb"><?= e($msg) ?></div><?php endif ?>
<?php if ($msg = flash('error')): ?><div class="alert-error mb"><?= e($msg) ?></div><?php endif ?>
<?php
}

function admin_footer(): void
{
    ?>
</main>
<script src="<?= e(asset('assets/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

function admin_lang_switch(): string
{
    $back = $_SERVER['REQUEST_URI'] ?? url('admin/');
    $html = '<div class="lang-switch">';
    foreach (['tj' => 'ТҶ', 'ru' => 'РУ'] as $l => $label) {
        $html .= '<a href="' . e(url('lang.php', ['l' => $l, 'back' => $back])) . '"' . ($l === admin_lang() ? ' class="active"' : '') . '>' . $label . '</a>';
    }
    return $html . '</div>';
}

/** Проверка CSRF для POST-запросов админки: при ошибке возвращаем на ту же страницу с сообщением. */
function admin_require_csrf(): void
{
    if (csrf_ok()) return;
    flash('error', post_too_large() ? ta('errPostSize') : ta('errCsrf'));
    header('Location: ' . safe_back($_SERVER['REQUEST_URI'] ?? '', 'admin/'), true, 303);
    exit;
}

function admin_not_found(): never
{
    http_response_code(404);
    admin_header('404');
    echo '<div class="card empty-box"><div class="big-404">404</div><a class="btn btn-ghost" href="' . e(url('admin/')) . '">' . e(ta('navOverview')) . '</a></div>';
    admin_footer();
    exit;
}

/** Значение поля формы: после неудачной отправки — то, что ввёл пользователь, иначе — из базы. */
function form_val(array $row, string $key): string
{
    if (is_post() && array_key_exists($key, $_POST)) return is_string($_POST[$key]) ? $_POST[$key] : '';
    return (string) ($row[$key] ?? '');
}

/**
 * Поля на трёх языках во вкладках «Тоҷикӣ / Русский / English».
 * $fields: [['title', 'Название', 'input'|'textarea'|'editor', ['required' => true, 'rows' => 4, 'hint' => '...']], ...]
 */
function lang_tabs(array $fields, array $row): void
{
    $uid = 'lt' . bin2hex(random_bytes(3));
    ?>
<div class="lang-tabs" data-lang-tabs>
  <div class="lang-tabs-bar" role="tablist">
    <?php foreach (LANGS as $i => $l): ?>
      <button type="button" role="tab" data-tab="<?= $uid . $l ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= e(LANG_NAMES[$l]) ?></button>
    <?php endforeach ?>
  </div>
  <?php foreach (LANGS as $l): ?>
    <div class="lang-tabs-panel stack-sm" id="<?= $uid . $l ?>" role="tabpanel" lang="<?= ['tj' => 'tg', 'ru' => 'ru', 'en' => 'en'][$l] ?>">
      <div class="lang-tabs-heading"><?= e(LANG_NAMES[$l]) ?></div>
      <?php foreach ($fields as $f):
          [$name, $label, $type] = $f;
          $opt = $f[3] ?? [];
          $key = "{$name}_$l";
          $req = !empty($opt['required']) && $l === 'tj';
          ?>
        <div>
          <label class="label" for="<?= $uid . $key ?>"><?= e($label) ?><?= $req ? ' *' : '' ?></label>
          <?php if ($type === 'input'): ?>
            <input id="<?= $uid . $key ?>" name="<?= $key ?>" class="input" maxlength="<?= (int) ($opt['max'] ?? 500) ?>" value="<?= e(form_val($row, $key)) ?>"<?= $req ? ' required' : '' ?>>
          <?php else: ?>
            <textarea id="<?= $uid . $key ?>" name="<?= $key ?>" class="input<?= $type === 'editor' ? ' rich' : '' ?>" rows="<?= (int) ($opt['rows'] ?? 4) ?>"<?= $type === 'editor' ? ' data-editor' : '' ?><?= $req ? ' required' : '' ?>><?= e(form_val($row, $key)) ?></textarea>
          <?php endif ?>
          <?php if (!empty($opt['hint'])): ?><p class="tiny muted"><?= e($opt['hint']) ?></p><?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  <?php endforeach ?>
</div>
<?php
}

/** Поле загрузки файла с превью текущего файла и галочкой «удалить». */
function file_field(string $name, string $label, string $current, string $kind): void
{
    ?>
<div class="file-field">
  <label class="label" for="ff-<?= e($name) ?>"><?= e($label) ?></label>
  <?php if ($current !== ''): ?>
    <div class="file-current">
      <?php if ($kind === 'image'): ?>
        <img src="<?= e(file_url($current)) ?>" alt="" class="thumb">
      <?php else: ?>
        <a href="<?= e(file_url($current)) ?>" target="_blank" class="link">📄 <?= e(basename($current)) ?></a>
      <?php endif ?>
      <label class="check tiny"><input type="checkbox" name="<?= e($name) ?>_remove" value="1"> <?= e(ta('removeFile')) ?></label>
    </div>
  <?php endif ?>
  <input id="ff-<?= e($name) ?>" type="file" name="<?= e($name) ?>" accept="<?= $kind === 'image' ? 'image/jpeg,image/png,image/webp' : 'application/pdf' ?>" class="small">
  <p class="tiny muted"><?= e(ta($kind === 'image' ? 'hintImage' : 'hintPdf')) ?></p>
</div>
<?php
}

/** Собирает многоязычные поля из POST: ['title_tj' => ..., 'title_ru' => ..., ...] */
function post_lang_fields(array $names, int $max = 500, bool $html = false): array
{
    $out = [];
    foreach ($names as $n) {
        foreach (LANGS as $l) {
            $v = post_str("{$n}_$l", $max);
            $out["{$n}_$l"] = $html ? sanitize_html($v) : $v;
        }
    }
    return $out;
}

/** Сохраняет загруженные файлы формы. Возвращает [новые значения, ошибка]. При ошибке уже загруженное удаляется. */
function handle_files(array $spec, array $row): array
{
    $values = [];
    try {
        foreach ($spec as $field => $kind) $values[$field] = handle_file_field($field, $kind, (string) ($row[$field] ?? ''));
    } catch (UploadError $ex) {
        foreach ($values as $field => $path) if ($path !== ($row[$field] ?? '')) delete_upload($path);
        return [[], upload_error_text($ex, $kind)];
    }
    return [$values, null];
}

/** После успешного сохранения удаляет файлы, которые были заменены или убраны. */
function cleanup_replaced_files(array $old, array $new): void
{
    foreach ($new as $field => $path) {
        if (($old[$field] ?? '') !== '' && $old[$field] !== $path) delete_upload($old[$field]);
    }
}

function yes_no_badge(int|string $flag): string
{
    return $flag ? '<span class="tag tag-green">' . e(ta('published')) . '</span>' : '<span class="tag">' . e(ta('draft')) . '</span>';
}

function submission_badge(string $status): string
{
    $cls = ['new' => 'st-new', 'review' => 'st-review', 'accepted' => 'st-done', 'rejected' => 'st-cancelled'][$status] ?? '';
    return '<span class="status ' . $cls . '">' . e(ta("sub_$status")) . '</span>';
}
