<?php
require __DIR__ . '/includes/bootstrap.php';

$a = get_article(get_id());
if (!$a) not_found();

// Считаем просмотр один раз за сессию
if (empty($_SESSION['viewed'][$a['id']])) {
    $_SESSION['viewed'][$a['id']] = 1;
    db_exec('UPDATE articles SET views = views + 1 WHERE id = ?', [$a['id']]);
    $a['views']++;
}

$lang = current_lang();
$title = tr($a, 'title');
$authors = parse_authors(tr($a, 'authors'));
$abstract = tr($a, 'abstract');
$keywords = keywords_list(tr($a, 'keywords'));
$section = $a['section_id'] ? (array_column(get_sections(), null, 'id')[$a['section_id']] ?? null) : null;
$journal = setting_tr('journal_name');
$issueLabel = issue_label($a, 'issue_');
$pageUrl = abs_url('article.php', ['id' => $a['id']]);
$pdfUrl = $a['pdf'] !== '' ? abs_url('download.php', ['id' => $a['id']]) : '';

// Цитирование по ГОСТ: Авторы. Название // Журнал. – Год. – № N. – С. 5–14.
$cite = implode(', ', array_column($authors, 'name'));
$cite = ($cite !== '' ? rtrim($cite, '.') . '. ' : '') . rtrim($title, '.') . ' // ' . $journal . '. – ' . $a['issue_year'] . '.';
if ($a['issue_volume'] !== '') $cite .= ' – ' . ($lang === 'en' ? 'Vol. ' : 'Т. ') . $a['issue_volume'] . '.';
$cite .= ' – № ' . $a['issue_number'] . '.';
if ($a['pages'] !== '') $cite .= ' – ' . ($lang === 'en' ? 'P. ' : 'С. ') . str_replace('-', '–', $a['pages']) . '.';
if ($a['doi'] !== '') $cite .= ' – DOI: ' . $a['doi'] . '.';

// Мета-теги для Google Scholar
$meta = [
    ['citation_title', $title],
    ['citation_journal_title', $journal],
    ['citation_publisher', setting_tr('publisher')],
    ['citation_publication_date', $a['issue_published_at'] ? str_replace('-', '/', $a['issue_published_at']) : (string) $a['issue_year']],
    ['citation_volume', $a['issue_volume']],
    ['citation_issue', $a['issue_number']],
    ['citation_issn', setting('issn_online') ?: setting('issn_print')],
    ['citation_doi', $a['doi']],
    ['citation_language', html_lang()],
    ['citation_abstract_html_url', $pageUrl],
    ['citation_pdf_url', $pdfUrl],
    ['citation_keywords', implode('; ', $keywords)],
];
if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', $a['pages'], $m)) {
    $meta[] = ['citation_firstpage', $m[1]];
    $meta[] = ['citation_lastpage', $m[2]];
}
foreach ($authors as $au) {
    $meta[] = ['citation_author', $au['name']];
    if ($au['affiliation'] !== '') $meta[] = ['citation_author_institution', $au['affiliation']];
}
$head = '';
foreach ($meta as [$name, $content]) {
    if ((string) $content !== '') $head .= '<meta name="' . $name . '" content="' . e($content) . "\">\n";
}

site_header($title, [
    'description' => mb_substr($abstract, 0, 300),
    'canonical' => $pageUrl,
    'head' => $head,
    'nav' => 'archive',
]);
?>
<a href="<?= e(url('issue.php', ['id' => $a['issue_id']])) ?>" class="back-link"><?= e(t('backToIssue')) ?></a>
<div class="layout-sidebar article-page">
  <article class="card pad stack">
    <?php if ($section): ?><div class="kicker"><?= e(tr($section, 'title')) ?></div><?php endif ?>
    <h1 class="h1"><?= e($title) ?></h1>
    <?php if ($authors): ?>
      <ul class="authors">
        <?php foreach ($authors as $au): ?>
          <li><b><?= e($au['name']) ?></b><?php if ($au['affiliation'] !== ''): ?> <span class="muted">— <?= e($au['affiliation']) ?></span><?php endif ?></li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>

    <?php if ($abstract !== ''): ?>
      <section>
        <h2 class="h3"><?= e(t('abstract')) ?></h2>
        <p class="abstract"><?= nl2br(e($abstract)) ?></p>
      </section>
    <?php endif ?>
    <?php if ($keywords): ?>
      <section>
        <h2 class="h3"><?= e(t('keywords')) ?></h2>
        <div class="keywords">
          <?php foreach ($keywords as $k): ?><a href="<?= e(url('search.php', ['q' => $k])) ?>" class="tag"><?= e($k) ?></a><?php endforeach ?>
        </div>
      </section>
    <?php endif ?>

    <?php
    $others = [];
    foreach (LANGS as $l) {
        if ($l === $lang || ($a["title_$l"] === '' && $a["abstract_$l"] === '')) continue;
        $others[$l] = $l;
    }
    if ($others): ?>
      <section>
        <h2 class="h3"><?= e(t('otherLanguages')) ?></h2>
        <?php foreach ($others as $l): ?>
          <details class="other-lang" lang="<?= ['tj' => 'tg', 'ru' => 'ru', 'en' => 'en'][$l] ?>">
            <summary><?= e(LANG_NAMES[$l]) ?></summary>
            <?php if ($a["title_$l"] !== ''): ?><p><b><?= e($a["title_$l"]) ?></b></p><?php endif ?>
            <?php if ($a["authors_$l"] !== ''): ?><p class="muted small"><?= e(authors_names($a, $l)) ?></p><?php endif ?>
            <?php if ($a["abstract_$l"] !== ''): ?><p><?= nl2br(e($a["abstract_$l"])) ?></p><?php endif ?>
            <?php if ($a["keywords_$l"] !== ''): ?><p class="small"><i><?= e($a["keywords_$l"]) ?></i></p><?php endif ?>
          </details>
        <?php endforeach ?>
      </section>
    <?php endif ?>

    <?php if (trim($a['refs']) !== ''): ?>
      <section>
        <h2 class="h3"><?= e(t('references')) ?></h2>
        <ol class="refs">
          <?php foreach (preg_split('/\R/', trim($a['refs'])) as $ref): ?>
            <?php if (trim($ref) !== ''): ?><li><?= e(preg_replace('/^\s*\d+[.)]\s*/', '', $ref)) ?></li><?php endif ?>
          <?php endforeach ?>
        </ol>
      </section>
    <?php endif ?>
  </article>

  <aside class="stack">
    <div class="card pad stack-sm">
      <?php if ($pdfUrl !== ''): ?>
        <a href="<?= e(url('download.php', ['id' => $a['id']])) ?>" class="btn btn-primary btn-block" target="_blank"><?= e(t('downloadPdf')) ?></a>
      <?php else: ?>
        <p class="muted small"><?= e(t('noPdf')) ?></p>
      <?php endif ?>
      <dl class="info-list">
        <dt><?= e(t('inIssue')) ?></dt>
        <dd><a href="<?= e(url('issue.php', ['id' => $a['issue_id']])) ?>" class="link"><?= e($issueLabel) ?></a></dd>
        <?php if ($a['pages'] !== ''): ?><dt><?= e(t('pages', ['p' => ''])) ?></dt><dd><?= e($a['pages']) ?></dd><?php endif ?>
        <?php if ($a['udc'] !== ''): ?><dt><?= e(t('udc')) ?></dt><dd><?= e($a['udc']) ?></dd><?php endif ?>
        <?php if ($a['doi'] !== ''): ?>
          <dt><?= e(t('doi')) ?></dt>
          <dd><a href="https://doi.org/<?= e($a['doi']) ?>" class="link" target="_blank" rel="noopener"><?= e($a['doi']) ?></a></dd>
        <?php endif ?>
      </dl>
      <p class="tiny muted"><?= e(t('stats', ['v' => $a['views'], 'd' => $a['downloads']])) ?></p>
    </div>
    <div class="card pad">
      <h2 class="h3 mb-sm"><?= e(t('howToCite')) ?></h2>
      <p class="cite" data-copy><?= e($cite) ?></p>
    </div>
  </aside>
</div>
<?php site_footer();
