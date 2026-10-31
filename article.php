<?php
require __DIR__ . '/includes/bootstrap.php';

$preview = is_preview();
$a = get_article(get_id(), !$preview);
if (!$a) not_found();
$isDraft = !$a['is_published'] || !$a['issue_is_published'];

// Дидан як бор дар як сессия ҳисоб мешавад; пешнамоиши админ ҳисоб намешавад
if (!$isDraft && empty($_SESSION['viewed'][$a['id']])) {
    $_SESSION['viewed'][$a['id']] = 1;
    track_article((int) $a['id'], 'views');
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
$cite = gost_citation($a);
[$prev, $next] = $isDraft ? [null, null] : neighbor_articles($a);
$related = $isDraft ? [] : related_articles($a);

// Мета-тегҳо барои Google Scholar
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
$jsonLd = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'ScholarlyArticle',
    'headline' => mb_substr($title, 0, 110),
    'name' => $title,
    'author' => array_map(fn($au) => array_filter(['@type' => 'Person', 'name' => $au['name'],
        'affiliation' => $au['affiliation'] !== '' ? ['@type' => 'Organization', 'name' => $au['affiliation']] : null]), $authors) ?: null,
    'abstract' => $abstract ?: null,
    'keywords' => $keywords ? implode(', ', $keywords) : null,
    'datePublished' => $a['issue_published_at'] ?: (string) $a['issue_year'],
    'inLanguage' => html_lang(),
    'url' => $pageUrl,
    'sameAs' => $a['doi'] !== '' ? 'https://doi.org/' . $a['doi'] : null,
    'pagination' => $a['pages'] ?: null,
    'isPartOf' => ['@type' => 'PublicationIssue', 'issueNumber' => $a['issue_number'], 'datePublished' => (string) $a['issue_year'],
        'isPartOf' => array_filter(['@type' => 'Periodical', 'name' => $journal, 'issn' => setting('issn_online') ?: setting('issn_print') ?: null])],
    'encoding' => $pdfUrl !== '' ? ['@type' => 'MediaObject', 'encodingFormat' => 'application/pdf', 'contentUrl' => $pdfUrl] : null,
]);
$head .= '<script type="application/ld+json">' . json_encode($jsonLd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

site_header($title, [
    'description' => mb_substr($abstract, 0, 300),
    'canonical' => $pageUrl,
    'head' => $head,
    'nav' => 'archive',
    'type' => 'article',
    'noindex' => $isDraft,
]);
$shareText = rawurlencode($title);
$shareUrl = rawurlencode($pageUrl);
?>
<?= breadcrumbs([[t('home'), url()], [t('archive'), url('archive.php')], [$issueLabel, url('issue.php', ['id' => $a['issue_id'], 'preview' => $preview ? 1 : null])], [t('article'), null]]) ?>
<?php if ($isDraft): ?><div class="alert-warn mb"><?= e(t('previewNotice')) ?></div><?php endif ?>
<div class="layout-sidebar article-page">
  <div class="stack">
    <article class="card pad-lg stack">
      <?php if ($section): ?><a href="<?= e(url('section.php', ['id' => $section['id']])) ?>" class="kicker"><?= e(tr($section, 'title')) ?></a><?php endif ?>
      <h1 class="article-h1"><?= e($title) ?></h1>
      <?php if ($authors): ?>
        <ul class="authors">
          <?php foreach ($authors as $i => $au): ?>
            <li>
              <a href="<?= e(url('author.php', ['name' => $au['name']])) ?>" class="author-name"><span class="avatar" aria-hidden="true"><?= e(mb_substr($au['name'], 0, 1)) ?></span><?= e($au['name']) ?></a>
              <?php if ($au['affiliation'] !== ''): ?><span class="muted small"><?= e($au['affiliation']) ?></span><?php endif ?>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
      <div class="article-facts">
        <span><?= e(t('inIssue')) ?>: <a href="<?= e(url('issue.php', ['id' => $a['issue_id']])) ?>" class="link"><?= e($issueLabel) ?></a></span>
        <?php if ($a['pages'] !== ''): ?><span><?= e(t('pages', ['p' => $a['pages']])) ?></span><?php endif ?>
        <?php if ($a['udc'] !== ''): ?><span><?= e(t('udc')) ?> <?= e($a['udc']) ?></span><?php endif ?>
        <?php if ($a['doi'] !== ''): ?><span>DOI: <a href="https://doi.org/<?= e($a['doi']) ?>" class="link" target="_blank" rel="noopener"><?= e($a['doi']) ?></a></span><?php endif ?>
      </div>

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

      <?php if ($a['pdf'] !== ''): ?>
        <details class="pdf-viewer" data-pdf-viewer>
          <summary><?= e(t('readOnline')) ?></summary>
          <object data-src="<?= e(file_url($a['pdf'])) ?>#view=FitH" type="application/pdf" aria-label="PDF">
            <p class="pad small"><?= e(t('pdfNoPreview')) ?> <a href="<?= e(url('download.php', ['id' => $a['id']])) ?>" class="link"><?= e(t('downloadPdf')) ?></a></p>
          </object>
        </details>
      <?php endif ?>

      <?php
      $others = array_filter(LANGS, fn($l) => $l !== $lang && ($a["title_$l"] !== '' || $a["abstract_$l"] !== ''));
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
            <?php foreach (preg_split('/\R/u', trim($a['refs'])) as $ref): ?>
              <?php if (trim($ref) !== ''): ?><li><?= e(preg_replace('/^\s*\d+[.)]\s*/', '', $ref)) ?></li><?php endif ?>
            <?php endforeach ?>
          </ol>
        </section>
      <?php endif ?>
    </article>

    <?php if ($prev || $next): ?>
      <nav class="prev-next">
        <?php if ($prev): ?><a href="<?= e(url('article.php', ['id' => $prev['id']])) ?>" class="card"><span class="tiny muted">← <?= e(t('prevArticle')) ?></span><span><?= e(tr($prev, 'title')) ?></span></a><?php else: ?><span></span><?php endif ?>
        <?php if ($next): ?><a href="<?= e(url('article.php', ['id' => $next['id']])) ?>" class="card next"><span class="tiny muted"><?= e(t('nextArticle')) ?> →</span><span><?= e(tr($next, 'title')) ?></span></a><?php endif ?>
      </nav>
    <?php endif ?>

    <?php if ($related): ?>
      <section>
        <h2 class="h2 mb"><?= e(t('related')) ?></h2>
        <div class="card divided"><?php foreach ($related as $r) article_item($r, true); ?></div>
      </section>
    <?php endif ?>
  </div>

  <aside class="stack sticky-aside">
    <div class="card pad stack-sm">
      <?php if ($pdfUrl !== ''): ?>
        <a href="<?= e(url('download.php', ['id' => $a['id']])) ?>" class="btn btn-primary btn-block btn-lg" target="_blank">⬇ <?= e(t('downloadPdf')) ?></a>
      <?php else: ?>
        <p class="muted small"><?= e(t('noPdf')) ?></p>
      <?php endif ?>
      <div class="mini-stats">
        <div><b><?= (int) $a['views'] ?></b><span><?= e(t('viewsLabel')) ?></span></div>
        <div><b><?= (int) $a['downloads'] ?></b><span><?= e(t('downloadsLabel')) ?></span></div>
      </div>
    </div>
    <div class="card pad stack-sm">
      <h2 class="h3"><?= e(t('howToCite')) ?></h2>
      <p class="cite" id="cite-text"><?= e($cite) ?></p>
      <div class="row-gap">
        <button type="button" class="btn btn-ghost btn-sm" data-copy="#cite-text" data-copied="<?= e(t('copied')) ?>">📋 <?= e(t('copy')) ?></button>
        <a href="<?= e(url('cite.php', ['id' => $a['id'], 'format' => 'bibtex'])) ?>" class="btn btn-ghost btn-sm">BibTeX</a>
        <a href="<?= e(url('cite.php', ['id' => $a['id'], 'format' => 'ris'])) ?>" class="btn btn-ghost btn-sm">RIS</a>
      </div>
    </div>
    <div class="card pad stack-sm">
      <h2 class="h3"><?= e(t('share')) ?></h2>
      <div class="share">
        <a href="https://t.me/share/url?url=<?= e($shareUrl) ?>&amp;text=<?= e($shareText) ?>" target="_blank" rel="noopener" class="share-btn tg">Telegram</a>
        <a href="https://wa.me/?text=<?= e($shareText . '%20' . $shareUrl) ?>" target="_blank" rel="noopener" class="share-btn wa">WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e($shareUrl) ?>" target="_blank" rel="noopener" class="share-btn fb">Facebook</a>
        <button type="button" class="share-btn" data-copy-text="<?= e($pageUrl) ?>" data-copied="<?= e(t('copied')) ?>">🔗 <?= e(t('copyLink')) ?></button>
      </div>
    </div>
  </aside>
</div>
<?php site_footer();
