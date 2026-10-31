<?php
// Танзимоти пайвасти сомона. Одатан ин файлро install.php худ месозад (includes/config.php).
// Барои танзими дастӣ: ин файлро ба includes/config.php нусха кунед ва пур кунед.
// Номи маҷалла, ISSN, тамос, логотип ва ранг дар панели идоракунӣ → «Танзимот» иваз мешаванд.

$env = static fn(string $k, string $default = ''): string => getenv($k) !== false ? (string) getenv($k) : $default;

return [
    // Пойгоҳи додаҳои MySQL / MariaDB
    'db_host' => $env('DB_HOST', 'localhost'),
    'db_port' => (int) $env('DB_PORT', '3306'),
    'db_name' => $env('DB_NAME', 'journal'),
    'db_user' => $env('DB_USER', 'root'),
    'db_pass' => $env('DB_PASS', ''),

    // Суроғаи сомона бе / дар охир. Агар сомона дар зерпапка бошад: http://localhost/journal
    'site_url' => $env('SITE_URL', 'http://localhost'),

    // true — танҳо агар сомона паси прокси (Cloudflare, nginx) бошад
    'trust_proxy' => $env('TRUST_PROXY', '') === '1',
];
