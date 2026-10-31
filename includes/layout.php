<?php
// Общая разметка сайта: шапка, подвал, элементы списков статей.

function asset(string $path): string
{
    $file = APP_ROOT . '/' . $path;
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Основной цвет из настроек (только формат #rrggbb, чтобы в CSS нельзя было ничего внедрить). */
function brand_color(): string
{
    $c = setting('color', '#1d4ed8');
    return preg_match('/^#[0-9a-f]{6}$/i', $c) ? $c : '#1d4ed8';
}

function lang_switch(): string
{
    $back = $_SERVER['REQUEST_URI'] ?? url();
    $html = '<div class="lang-switch">';
    foreach (LANG_LABELS as $l => $label) {
        $active = $l === current_lang();
        $html .= '<a href="' . e(url('lang.php', ['l' => $l, 'back' => $back])) . '" rel="nofollow" hreflang="' . $l . '"'
            . ($active ? ' class="active" aria-current="true"' : '') . '>' . $label . '</a>';
    }
    return $html . '</div>';
}

function logo_html(): string
{
    $logo = setting('logo');
    if ($logo !== '') return '<img src="' . e(file_url($logo)) . '" alt="" class="logo-img">';
    $name = setting_tr('journal_name');
    return '<span class="logo-mark">' . e(mb_strtoupper(mb_substr($name !== '' ? $name : 'J', 0, 1))) . '</span>';
}

/**
 * @param array{description?: string, canonical?: string, image?: string, noindex?: bool, head?: string, nav?: string, type?: string} $meta
 */
function site_header(string $title = '', array $meta = []): void
{
    $journal = setting_tr('journal_name');
    $fullTitle = $title !== '' ? "$title — $journal" : $journal;
    $description = $meta['description'] ?? setting_tr('tagline');
    $nav = $meta['nav'] ?? '';
    $q = get_str('q', 100);
    $pages = get_menu_pages();
    $announcement = setting_tr('announcement');
    $isPageNav = str_starts_with($nav, 'page-');
    ?>
<!DOCTYPE html>
<html lang="<?= html_lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="theme-color" content="<?= brand_color() ?>">
<?php if (!empty($meta['noindex'])): ?><meta name="robots" content="noindex"><?php endif ?>
<?php if (!empty($meta['canonical'])): ?><link rel="canonical" href="<?= e($meta['canonical']) ?>"><?php endif ?>
<meta property="og:site_name" content="<?= e($journal) ?>">
<meta property="og:type" content="<?= e($meta['type'] ?? 'website') ?>">
<meta property="og:locale" content="<?= e(t('ogLocale')) ?>">
<meta property="og:title" content="<?= e($title !== '' ? $title : $journal) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<?php if (!empty($meta['canonical'])): ?><meta property="og:url" content="<?= e($meta['canonical']) ?>"><?php endif ?>
<?php if (!empty($meta['image'])): ?><meta property="og:image" content="<?= e(abs_url($meta['image'])) ?>"><?php endif ?>
<meta name="twitter:card" content="summary">
<link rel="alternate" type="application/rss+xml" title="<?= e($journal . ' — ' . t('rssArticles')) ?>" href="<?= e(abs_url('feed.php')) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= e($journal . ' — ' . t('news')) ?>" href="<?= e(abs_url('feed.php', ['type' => 'news'])) ?>">
<?php if (($logo = setting('logo')) !== ''): ?><link rel="icon" href="<?= e(file_url($logo)) ?>"><?php endif ?>
<script>try{var d=document.documentElement,t=localStorage.getItem('theme'),f=localStorage.getItem('fs');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))d.dataset.theme='dark';if(f)d.dataset.fs=f;if(localStorage.getItem('contrast')==='1')d.dataset.contrast='1'}catch(e){}</script>
<link rel="stylesheet" href="<?= e(asset('assets/style.css')) ?>">
<style>:root { --brand: <?= brand_color() ?>; }</style>
<?= $meta['head'] ?? '' ?>
</head>
<body>
<a href="#main" class="skip-link"><?= e(t('skipToContent')) ?></a>
<?php if ($announcement !== ''): ?>
  <div class="announcement" data-announcement="<?= e(substr(md5($announcement), 0, 8)) ?>">
    <div class="container"><span><?= e($announcement) ?></span><button type="button" class="announcement-close" aria-label="<?= e(t('close')) ?>" data-announcement-close>×</button></div>
  </div>
<?php endif ?>
<header class="site-header">
  <div class="container header-top">
    <a href="<?= e(url()) ?>" class="brand">
      <?= logo_html() ?>
      <span class="brand-text">
        <span class="brand-name"><?= e($journal) ?></span>
        <?php if (($tag = setting_tr('tagline')) !== ''): ?><span class="brand-tagline"><?= e($tag) ?></span><?php endif ?>
      </span>
    </a>
    <div class="header-tools">
      <form action="<?= e(url('search.php')) ?>" class="header-search" role="search">
        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('searchPlaceholder')) ?>" class="input" aria-label="<?= e(t('search')) ?>">
      </form>
      <div class="tool-buttons">
        <button type="button" class="icon-btn" data-font-size title="<?= e(t('fontSize')) ?>" aria-label="<?= e(t('fontSize')) ?>">A<sup>+</sup></button>
        <button type="button" class="icon-btn" data-contrast title="<?= e(t('contrast')) ?>" aria-label="<?= e(t('contrast')) ?>"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg></button>
        <button type="button" class="icon-btn" data-theme-toggle title="<?= e(t('darkMode')) ?>" aria-label="<?= e(t('darkMode')) ?>">
          <svg class="i-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
          <svg class="i-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        </button>
      </div>
      <?= lang_switch() ?>
    </div>
  </div>
  <nav class="main-nav" aria-label="<?= e(t('menu')) ?>">
    <div class="container nav-row">
      <button type="button" class="nav-toggle" aria-expanded="false" data-nav-toggle>☰ <?= e(t('menu')) ?></button>
      <ul data-nav>
        <li><a href="<?= e(url()) ?>"<?= $nav === 'home' ? ' class="active" aria-current="page"' : '' ?>><?= e(t('home')) ?></a></li>
        <li><a href="<?= e(url('issue.php')) ?>"<?= $nav === 'current' ? ' class="active" aria-current="page"' : '' ?>><?= e(t('currentIssue')) ?></a></li>
        <li><a href="<?= e(url('archive.php')) ?>"<?= $nav === 'archive' ? ' class="active" aria-current="page"' : '' ?>><?= e(t('archive')) ?></a></li>
        <?php if ($pages): ?>
          <li class="has-sub">
            <button type="button" class="<?= $isPageNav ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false" data-sub-toggle><?= e(t('aboutJournal')) ?> <span aria-hidden="true">▾</span></button>
            <ul class="sub">
              <?php foreach ($pages as $p): ?>
                <li><a href="<?= e(url('page.php', ['slug' => $p['slug']])) ?>"<?= $nav === 'page-' . $p['slug'] ? ' class="active" aria-current="page"' : '' ?>><?= e(tr($p, 'title')) ?></a></li>
              <?php endforeach ?>
            </ul>
          </li>
        <?php endif ?>
        <li><a href="<?= e(url('authors.php')) ?>"<?= $nav === 'authors' ? ' class="active" aria-current="page"' : '' ?>><?= e(t('authors')) ?></a></li>
        <li><a href="<?= e(url('news.php')) ?>"<?= $nav === 'news' ? ' class="active" aria-current="page"' : '' ?>><?= e(t('news')) ?></a></li>
      </ul>
      <a href="<?= e(url('submit.php')) ?>" class="nav-cta<?= $nav === 'submit' ? ' active' : '' ?>"><?= e(t('submit')) ?></a>
    </div>
  </nav>
</header>
<main class="container main" id="main">
<?php
}

/** Навигатсияи «нонрезаҳо»: [['Асосӣ', url()], ['Бойгонӣ', url('archive.php')], ['Мақола', null]] */
function breadcrumbs(array $items): string
{
    $html = '<nav class="breadcrumbs" aria-label="breadcrumb"><ol>';
    foreach ($items as [$label, $href]) {
        $html .= '<li>' . ($href ? '<a href="' . e($href) . '">' . e($label) . '</a>' : '<span aria-current="page">' . e($label) . '</span>') . '</li>';
    }
    return $html . '</ol></nav>';
}

/** Индексатсия: аз танзимоти indexing, ҳар сатр «Ном | https://…» */
function indexing_links(): array
{
    $out = [];
    foreach (preg_split('/\R/u', setting('indexing')) as $line) {
        [$name, $link] = array_map('trim', explode('|', $line, 2) + [1 => '']);
        if ($name === '') continue;
        $out[] = ['name' => $name, 'url' => preg_match('~^https?://~i', $link) ? $link : ''];
    }
    return $out;
}

function site_footer(): void
{
    $email = setting('email');
    $phone = setting('phone');
    $indexing = indexing_links();
    ?>
</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="footer-brand"><?= logo_html() ?><span class="footer-title"><?= e(setting_tr('journal_name')) ?></span></div>
      <?php if (($v = setting_tr('tagline')) !== ''): ?><p class="footer-text"><?= e($v) ?></p><?php endif ?>
      <?php if (($v = setting_tr('publisher')) !== ''): ?><div><?= e(t('publisher')) ?>: <?= e($v) ?></div><?php endif ?>
      <?php if (($v = setting('issn_print')) !== ''): ?><div><?= e(t('issnPrint')) ?>: <?= e($v) ?></div><?php endif ?>
      <?php if (($v = setting('issn_online')) !== ''): ?><div><?= e(t('issnOnline')) ?>: <?= e($v) ?></div><?php endif ?>
    </div>
    <div>
      <div class="footer-title"><?= e(t('aboutJournal')) ?></div>
      <?php foreach (get_menu_pages() as $p): ?>
        <div><a href="<?= e(url('page.php', ['slug' => $p['slug']])) ?>"><?= e(tr($p, 'title')) ?></a></div>
      <?php endforeach ?>
      <div><a href="<?= e(url('authors.php')) ?>"><?= e(t('authors')) ?></a></div>
      <div><a href="<?= e(url('submit.php')) ?>"><?= e(t('submit')) ?></a></div>
    </div>
    <div>
      <div class="footer-title"><?= e(t('contacts')) ?></div>
      <?php if (($v = setting_tr('address')) !== ''): ?><div><?= e($v) ?></div><?php endif ?>
      <?php if ($phone !== ''): ?><div><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></div><?php endif ?>
      <?php if ($email !== ''): ?><div><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div><?php endif ?>
      <div class="footer-feeds">
        <a href="<?= e(url('feed.php')) ?>">RSS</a>
        <a href="<?= e(url('oai.php', ['verb' => 'Identify'])) ?>">OAI-PMH</a>
        <a href="<?= e(url('sitemap.xml')) ?>">Sitemap</a>
      </div>
    </div>
    <?php if ($indexing): ?>
    <div>
      <div class="footer-title"><?= e(t('indexing')) ?></div>
      <?php foreach ($indexing as $ix): ?>
        <div><?php if ($ix['url'] !== ''): ?><a href="<?= e($ix['url']) ?>" target="_blank" rel="noopener"><?= e($ix['name']) ?></a><?php else: ?><?= e($ix['name']) ?><?php endif ?></div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> <?= e(setting_tr('journal_name')) ?>. <?= e(t('rights')) ?></span>
    <a href="#main" class="to-top"><?= e(t('toTop')) ?> ↑</a>
  </div>
</footer>
<script src="<?= e(asset('assets/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

function issue_cover(array $issue, string $class = ''): string
{
    if ($issue['cover'] !== '') {
        return '<img src="' . e(file_url($issue['cover'])) . '" alt="' . e(issue_label($issue)) . '" class="cover ' . e($class) . '" loading="lazy">';
    }
    return '<div class="cover cover-blank ' . e($class) . '"><span>' . e(setting_tr('journal_name')) . '</span><b>' . e(issue_label($issue)) . '</b></div>';
}

/** Строка статьи в содержании выпуска, поиске и на главной. $highlight — подсветить слово из поиска. */
function article_item(array $a, bool $showIssue = false, string $highlight = ''): void
{
    $href = url('article.php', ['id' => $a['id']]);
    $title = e(tr($a, 'title'));
    $authors = e(authors_names($a));
    if ($highlight !== '') {
        // Пропускаем HTML-сущности (&amp; и т.п.), чтобы подсветка не ломала разметку
        $re = '/&[#a-z0-9]+;(*SKIP)(*FAIL)|(' . preg_quote(e($highlight), '/') . ')/iu';
        $title = preg_replace($re, '<mark>$1</mark>', $title);
        $authors = preg_replace($re, '<mark>$1</mark>', $authors);
    }
    ?>
<article class="article-item">
  <a href="<?= e($href) ?>" class="article-title"><?= $title ?></a>
  <?php if ($authors !== ''): ?><div class="article-authors"><?= $authors ?></div><?php endif ?>
  <div class="article-meta">
    <?php if ($showIssue && isset($a['issue_year'])): ?>
      <a href="<?= e(url('issue.php', ['id' => $a['issue_id']])) ?>"><?= e(issue_label($a, 'issue_')) ?></a>
    <?php endif ?>
    <?php if ($a['pages'] !== ''): ?><span><?= e(t('pages', ['p' => $a['pages']])) ?></span><?php endif ?>
    <?php if ($a['doi'] !== ''): ?><span>DOI: <?= e($a['doi']) ?></span><?php endif ?>
    <span class="meta-stats" title="<?= e(t('stats', ['v' => $a['views'], 'd' => $a['downloads']])) ?>">👁 <?= (int) $a['views'] ?></span>
    <?php if ($a['pdf'] !== ''): ?><a href="<?= e(url('download.php', ['id' => $a['id']])) ?>" class="pdf-link">PDF ↓</a><?php endif ?>
  </div>
</article>
<?php
}

function not_found(): never
{
    http_response_code(404);
    site_header(t('notFound'), ['noindex' => true]);
    ?>
<div class="card empty-box">
  <div class="big-404">404</div>
  <h1 class="h2"><?= e(t('notFound')) ?></h1>
  <a href="<?= e(url()) ?>" class="btn btn-primary"><?= e(t('toHome')) ?></a>
</div>
<?php
    site_footer();
    exit;
}
