<?php
// Содироти иқтибос барои Zotero, Mendeley, EndNote: cite.php?id=5&format=bibtex|ris
require __DIR__ . '/includes/bootstrap.php';

$a = get_article(get_id());
$format = get_str('format', 10);
if (!$a || !in_array($format, ['bibtex', 'ris'], true)) not_found();
$c = citation_data($a);

if ($format === 'ris') {
    $lines = ['TY  - JOUR'];
    foreach ($c['authors'] as $au) $lines[] = "AU  - $au";
    $lines[] = 'TI  - ' . $c['title'];
    $lines[] = 'JO  - ' . $c['journal'];
    $lines[] = 'PY  - ' . $c['year'];
    if ($c['volume'] !== '') $lines[] = 'VL  - ' . $c['volume'];
    $lines[] = 'IS  - ' . $c['number'];
    if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', $c['pages'], $m)) {
        $lines[] = "SP  - $m[1]";
        $lines[] = "EP  - $m[2]";
    }
    if ($c['issn'] !== '') $lines[] = 'SN  - ' . $c['issn'];
    if ($c['doi'] !== '') $lines[] = 'DO  - ' . $c['doi'];
    if ($c['publisher'] !== '') $lines[] = 'PB  - ' . $c['publisher'];
    foreach ($c['keywords'] as $k) $lines[] = "KW  - $k";
    if ($c['abstract'] !== '') $lines[] = 'AB  - ' . preg_replace('/\s+/u', ' ', $c['abstract']);
    $lines[] = 'UR  - ' . $c['url'];
    $lines[] = 'ER  - ';
    $body = implode("\r\n", $lines) . "\r\n";
    $type = 'application/x-research-info-systems';
} else {
    $esc = fn(string $v) => strtr($v, ['\\' => '\\textbackslash{}', '{' => '\\{', '}' => '\\}', '%' => '\\%', '&' => '\\&', '$' => '\\$', '#' => '\\#', '_' => '\\_']);
    $fields = array_filter([
        'author' => implode(' and ', $c['authors']),
        'title' => $c['title'],
        'journal' => $c['journal'],
        'year' => $c['year'],
        'volume' => $c['volume'],
        'number' => $c['number'],
        'pages' => str_replace(['–', '-'], '--', $c['pages']),
        'issn' => $c['issn'],
        'doi' => $c['doi'],
        'url' => $c['url'],
        'keywords' => implode(', ', $c['keywords']),
    ], fn($v) => $v !== '');
    $body = "@article{article{$a['id']},\n";
    foreach ($fields as $k => $v) $body .= "  $k = {" . ($k === 'url' || $k === 'doi' ? $v : $esc($v)) . "},\n";
    $body .= "}\n";
    $type = 'application/x-bibtex';
}

header("Content-Type: $type; charset=utf-8");
header('Content-Disposition: attachment; filename="article-' . $a['id'] . ($format === 'ris' ? '.ris' : '.bib') . '"');
echo $body;
