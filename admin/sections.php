<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_login();

if (is_post()) {
    admin_require_csrf();
    $id = post_int('id');
    if (post_str('action', 20) === 'delete') {
        if (is_int($id)) {
            db_exec('DELETE FROM sections WHERE id = ?', [$id]);
            log_action('delete', 'section', $id);
        }
        flash('ok', ta('deleted'));
        redirect('admin/sections.php');
    }
    $values = post_lang_fields(['title'], 200);
    $sort = post_int('sort') ?? 0;
    if ($values['title_tj'] === '' && $values['title_ru'] === '') flash('error', ta('errTitle'));
    elseif ($sort === false || $id === false) flash('error', ta('errData'));
    else {
        if ($id) db_exec('UPDATE sections SET title_tj = ?, title_ru = ?, title_en = ?, sort = ? WHERE id = ?', [...array_values($values), $sort, $id]);
        else db_exec('INSERT INTO sections (title_tj, title_ru, title_en, sort) VALUES (?, ?, ?, ?)', [...array_values($values), $sort]);
        log_action($id ? 'update' : 'create', 'section', $id ?: (int) db()->lastInsertId(), tr($values, 'title', admin_lang()));
        flash('ok', ta('saved'));
    }
    redirect('admin/sections.php');
}

$counts = array_column(db_all('SELECT section_id, COUNT(*) AS n FROM articles WHERE section_id IS NOT NULL GROUP BY section_id'), 'n', 'section_id');

function section_form(?array $s = null): void
{
    ?>
<form method="post" class="inline-form">
  <?= csrf_field() ?>
  <?php if ($s): ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><?php endif ?>
  <?php foreach (LANGS as $l): ?>
    <input name="title_<?= $l ?>" value="<?= e($s["title_$l"] ?? '') ?>" placeholder="<?= e(LANG_NAMES[$l]) ?>" aria-label="<?= e(LANG_NAMES[$l]) ?>" maxlength="200" class="input grow min-40"<?= $l === 'tj' ? ' required' : '' ?>>
  <?php endforeach ?>
  <input name="sort" value="<?= (int) ($s['sort'] ?? 0) ?>" inputmode="numeric" aria-label="<?= e(ta('fSort')) ?>" title="<?= e(ta('fSort')) ?>" class="input w-16">
  <button class="btn <?= $s ? 'btn-ghost' : 'btn-primary' ?>"><?= e($s ? ta('save') : ta('add')) ?></button>
</form>
<?php
}

admin_header(ta('navSections'), 'sections');
?>
<div class="wide stack-lg">
  <div>
    <h1 class="h1"><?= e(ta('navSections')) ?></h1>
    <p class="muted small"><?= e(ta('sectionsHint')) ?></p>
  </div>
  <div class="card pad">
    <h2 class="h3 mb-sm"><?= e(ta('newSection')) ?></h2>
    <?php section_form() ?>
  </div>
  <div class="card divided">
    <?php foreach (get_sections() as $s): ?>
      <div class="cat-row">
        <div class="grow"><?php section_form($s) ?></div>
        <span class="small muted nowrap"><?= e(ta('articlesCount', ['n' => $counts[$s['id']] ?? 0])) ?></span>
        <form method="post" data-confirm="<?= e(ta('confirmDeleteSection')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
          <button class="link-danger small" name="action" value="delete"><?= e(ta('delete')) ?></button>
        </form>
      </div>
    <?php endforeach ?>
  </div>
</div>
<?php admin_footer();
