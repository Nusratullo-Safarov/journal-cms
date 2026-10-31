<?php
// Скачивание PDF статьи со счётчиком скачиваний: download.php?id=5
require __DIR__ . '/includes/bootstrap.php';

$a = get_article(get_id());
if (!$a || $a['pdf'] === '') not_found();

if (empty($_SESSION['downloaded'][$a['id']])) {
    $_SESSION['downloaded'][$a['id']] = 1;
    track_article((int) $a['id'], 'downloads');
}
session_write_close();

// Внешняя ссылка на PDF — просто перенаправляем
if (preg_match('~^https?://~i', $a['pdf'])) {
    header('Location: ' . $a['pdf'], true, 302);
    exit;
}
if (!preg_match('~^uploads/pdf/[a-f0-9]{24}\.pdf$~', $a['pdf']) || !is_file(APP_ROOT . '/' . $a['pdf'])) not_found();

$file = APP_ROOT . '/' . $a['pdf'];
$name = 'article-' . $a['id'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="' . $name . '"');
header('Cache-Control: public, max-age=86400');
readfile($file);
