<?php
// Карта сайта для поисковиков. Доступна как /sitemap.xml (см. .htaccess).
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [[abs_url(), null], [abs_url('archive.php'), null], [abs_url('news.php'), null], [abs_url('submit.php'), null]];
foreach (db_all('SELECT slug FROM pages') as $p) $urls[] = [abs_url('page.php', ['slug' => $p['slug']]), null];
foreach (db_all('SELECT id, published_at FROM issues WHERE is_published = 1') as $i) {
    $urls[] = [abs_url('issue.php', ['id' => $i['id']]), $i['published_at']];
}
foreach (db_all('SELECT a.id, a.created_at FROM articles a JOIN issues i ON i.id = a.issue_id WHERE a.is_published = 1 AND i.is_published = 1') as $a) {
    $urls[] = [abs_url('article.php', ['id' => $a['id']]), $a['created_at']];
}
foreach (db_all('SELECT id, published_at FROM news WHERE is_published = 1 AND published_at <= NOW()') as $n) {
    $urls[] = [abs_url('news.php', ['id' => $n['id']]), $n['published_at']];
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $mod]) {
    echo '  <url><loc>', e($loc), '</loc>', $mod ? '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>' : '', "</url>\n";
}
echo '</urlset>', "\n";
