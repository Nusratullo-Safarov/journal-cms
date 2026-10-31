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
 * @param array{description?: string, canonical?: string, image?: string, noindex?: bool, head?: string, nav?: string} $meta
 */
function site_header(string $title = '', array $meta = []): void
{
    $journal = setting_tr('journal_name');
    $fullTitle = $title !== '' ? "$title — $journal" : $journal;
    $description = $meta['description'] ?? setting_tr('tagline');
    $nav = $meta['nav'] ?? '';
    $q = get_str('q', 100);
    $navItems = [
        ['home', url(), t('home')],
        ['current', url('issue.php'), t('currentIssue')],
        ['archive', url('archive.php'), t('archive')],
    ];
    foreach (get_menu_pages() as $p) $navItems[] = ['page-' . $p['slug'], url('page.php', ['slug' => $p['slug']]), tr($p, 'title')];
    $navItems[] = ['news', url('news.php'), t('news')];
    $navItems[] = ['submit', url('submit.php'), t('submit')];
    ?>
<!DOCTYPE html>
<html lang="<?= html_lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if (!empty($meta['noindex'])): ?><meta name="robots" content="noindex"><?php endif ?>
<?php if (!empty($meta['canonical'])): ?><link rel="canonical" href="<?= e($meta['canonical']) ?>"><?php endif ?>
<meta property="og:site_name" content="<?= e($journal) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= e(t('ogLocale')) ?>">
<meta property="og:title" content="<?= e($title !== '' ? $title : $journal) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<?php if (!empty($meta['image'])): ?><meta property="og:image" content="<?= e(abs_url($meta['image'])) ?>"><?php endif ?>
<link rel="stylesheet" href="<?= e(asset('assets/style.css')) ?>">
<style>:root { --brand: <?= brand_color() ?>; }</style>
<?= $meta['head'] ?? '' ?>
</head>
<body>
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
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('searchPlaceholder')) ?>" class="input" aria-label="<?= e(t('search')) ?>">
      </form>
      <?= lang_switch() ?>
    </div>
  </div>
  <nav class="main-nav">
    <div class="container">
      <button type="button" class="nav-toggle" aria-expanded="false" data-nav-toggle><?= e(t('menu')) ?> ☰</button>
      <ul data-nav>
        <?php foreach ($navItems as [$key, $href, $label]): ?>
          <li><a href="<?= e($href) ?>"<?= $key === $nav ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach ?>
      </ul>
    </div>
  </nav>
</header>
<main class="container main">
<?php
}

function site_footer(): void
{
    $email = setting('email');
    $phone = setting('phone');
    ?>
</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="footer-title"><?= e(setting_tr('journal_name')) ?></div>
      <?php if (($v = setting_tr('publisher')) !== ''): ?><div><?= e(t('publisher')) ?>: <?= e($v) ?></div><?php endif ?>
      <?php if (($v = setting('issn_print')) !== ''): ?><div><?= e(t('issnPrint')) ?>: <?= e($v) ?></div><?php endif ?>
      <?php if (($v = setting('issn_online')) !== ''): ?><div><?= e(t('issnOnline')) ?>: <?= e($v) ?></div><?php endif ?>
    </div>
    <div>
      <div class="footer-title"><?= e(t('contacts')) ?></div>
      <?php if (($v = setting_tr('address')) !== ''): ?><div><?= e($v) ?></div><?php endif ?>
      <?php if ($phone !== ''): ?><div><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></div><?php endif ?>
      <?php if ($email !== ''): ?><div><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div><?php endif ?>
    </div>
    <div>
      <div class="footer-title"><?= e(t('aboutJournal')) ?></div>
      <?php foreach (get_menu_pages() as $p): ?>
        <div><a href="<?= e(url('page.php', ['slug' => $p['slug']])) ?>"><?= e(tr($p, 'title')) ?></a></div>
      <?php endforeach ?>
    </div>
  </div>
  <div class="container footer-bottom">© <?= date('Y') ?> <?= e(setting_tr('journal_name')) ?>. <?= e(t('rights')) ?></div>
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

/** Строка статьи в содержании выпуска, поиске и на главной. */
function article_item(array $a, bool $showIssue = false): void
{
    $href = url('article.php', ['id' => $a['id']]);
    ?>
<article class="article-item">
  <a href="<?= e($href) ?>" class="article-title"><?= e(tr($a, 'title')) ?></a>
  <?php if (($names = authors_names($a)) !== ''): ?><div class="article-authors"><?= e($names) ?></div><?php endif ?>
  <div class="article-meta">
    <?php if ($showIssue && isset($a['issue_year'])): ?>
      <a href="<?= e(url('issue.php', ['id' => $a['issue_id']])) ?>"><?= e(issue_label($a, 'issue_')) ?></a>
    <?php endif ?>
    <?php if ($a['pages'] !== ''): ?><span><?= e(t('pages', ['p' => $a['pages']])) ?></span><?php endif ?>
    <?php if ($a['pdf'] !== ''): ?><a href="<?= e(url('download.php', ['id' => $a['id']])) ?>" class="pdf-link">PDF</a><?php endif ?>
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
