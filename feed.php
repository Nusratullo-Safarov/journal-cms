<?php
// RSS 2.0: мақолаҳои нав (feed.php) ё хабарҳо (feed.php?type=news)
require __DIR__ . '/includes/bootstrap.php';

$news = get_str('type', 10) === 'news';
$journal = setting_tr('journal_name');
$items = [];
if ($news) {
    foreach (latest_news(30) as $n) {
        $items[] = [tr($n, 'title'), abs_url('news.php', ['id' => $n['id']]), html_to_text(tr($n, 'body'), 500), $n['published_at'], []];
    }
} else {
    foreach (db_all('SELECT ' . ARTICLE_ISSUE_COLS . ', i.published_at AS issue_published_at ' . PUBLISHED_ARTICLES . ' ORDER BY i.year DESC, i.published_at DESC, a.id DESC LIMIT 50') as $a) {
        $items[] = [tr($a, 'title'), abs_url('article.php', ['id' => $a['id']]), tr($a, 'abstract'), $a['issue_published_at'] ?: $a['created_at'], parse_authors(tr($a, 'authors'))];
    }
}

header('Content-Type: application/rss+xml; charset=utf-8');
$x = fn($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
  <title><?= $x($journal . ' — ' . ($news ? t('news') : t('rssArticles'))) ?></title>
  <link><?= $x(abs_url()) ?></link>
  <description><?= $x(setting_tr('tagline')) ?></description>
  <language><?= html_lang() ?></language>
  <atom:link href="<?= $x(abs_url('feed.php', ['type' => $news ? 'news' : null])) ?>" rel="self" type="application/rss+xml"/>
<?php foreach ($items as [$title, $link, $desc, $date, $authors]): ?>
  <item>
    <title><?= $x($title) ?></title>
    <link><?= $x($link) ?></link>
    <guid isPermaLink="true"><?= $x($link) ?></guid>
    <description><?= $x($desc) ?></description>
    <pubDate><?= date(DATE_RSS, strtotime($date)) ?></pubDate>
<?php foreach ($authors as $au): ?>
    <dc:creator><?= $x($au['name']) ?></dc:creator>
<?php endforeach ?>
  </item>
<?php endforeach ?>
</channel>
</rss>
