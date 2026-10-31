<?php
// Доступен как /robots.txt (см. .htaccess). Адрес sitemap берётся из site_url в config.php.
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
$base = base_path();
echo "User-agent: *\n";
echo "Allow: /\n";
foreach (['admin/', 'search.php', 'lang.php', 'install.php', 'cite.php'] as $path) {
    echo "Disallow: $base/$path\n";
}
echo 'Sitemap: ', abs_url('sitemap.xml'), "\n";
