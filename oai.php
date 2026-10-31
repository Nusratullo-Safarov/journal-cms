<?php
// OAI-PMH 2.0 (формат oai_dc) — протокол, по которому научные базы (eLibrary, CyberLeninka, BASE, Google Scholar)
// забирают метаданные статей. Адрес для регистрации в базах: https://сайт/oai.php
require __DIR__ . '/includes/bootstrap.php';
session_write_close();

const OAI_PAGE = 100;

$verb = get_str('verb', 30);
$host = parse_url(site_url(), PHP_URL_HOST) ?: 'localhost';
$x = fn($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$params = array_filter($_GET, 'is_string');
$errors = [];
$body = '';

function oai_identifier(string $host, int $id): string
{
    return "oai:$host:article/$id";
}

function oai_record_xml(array $a, string $host, callable $x, bool $withMetadata): string
{
    $header = '<header><identifier>' . $x(oai_identifier($host, (int) $a['id'])) . '</identifier><datestamp>'
        . gmdate('Y-m-d', strtotime($a['created_at'])) . '</datestamp>'
        . ($a['section_id'] ? '<setSpec>section:' . (int) $a['section_id'] . '</setSpec>' : '') . '</header>';
    if (!$withMetadata) return $header;
    $dc = [];
    foreach (LANGS as $l) {
        $lang = ['tj' => 'tg', 'ru' => 'ru', 'en' => 'en'][$l];
        if ($a["title_$l"] !== '') $dc[] = "<dc:title xml:lang=\"$lang\">" . $x($a["title_$l"]) . '</dc:title>';
        if ($a["abstract_$l"] !== '') $dc[] = "<dc:description xml:lang=\"$lang\">" . $x($a["abstract_$l"]) . '</dc:description>';
        foreach (keywords_list($a["keywords_$l"]) as $k) $dc[] = "<dc:subject xml:lang=\"$lang\">" . $x($k) . '</dc:subject>';
    }
    $creators = [];
    foreach (LANGS as $l) foreach (parse_authors($a["authors_$l"]) as $au) $creators[$au['name']] = 1;
    foreach (array_keys($creators) as $c) $dc[] = '<dc:creator>' . $x($c) . '</dc:creator>';
    if ($a['udc'] !== '') $dc[] = '<dc:subject>UDC ' . $x($a['udc']) . '</dc:subject>';
    if (($p = setting_tr('publisher')) !== '') $dc[] = '<dc:publisher>' . $x($p) . '</dc:publisher>';
    $dc[] = '<dc:date>' . $x($a['issue_published_at'] ?: $a['issue_year']) . '</dc:date>';
    $dc[] = '<dc:type>info:eu-repo/semantics/article</dc:type>';
    $dc[] = '<dc:type>Text</dc:type>';
    if ($a['pdf'] !== '') $dc[] = '<dc:format>application/pdf</dc:format>';
    $dc[] = '<dc:identifier>' . $x(abs_url('article.php', ['id' => $a['id']])) . '</dc:identifier>';
    if ($a['doi'] !== '') $dc[] = '<dc:identifier>https://doi.org/' . $x($a['doi']) . '</dc:identifier>';
    $source = setting_tr('journal_name') . '; ' . strip_tags(issue_label($a, 'issue_')) . ($a['pages'] !== '' ? '; ' . $a['pages'] : '');
    $dc[] = '<dc:source>' . $x($source) . '</dc:source>';
    if (($issn = setting('issn_online') ?: setting('issn_print')) !== '') $dc[] = '<dc:source>ISSN ' . $x($issn) . '</dc:source>';
    foreach (['tg', 'ru', 'en'] as $i => $lang) if ($a['title_' . LANGS[$i]] !== '') $dc[] = "<dc:language>$lang</dc:language>";
    return '<record>' . $header . '<metadata><oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" '
        . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
        . 'xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">'
        . implode('', $dc) . '</oai_dc:dc></metadata></record>';
}

/** Проверяет, что переданы только разрешённые аргументы. */
function oai_args_ok(array $params, array $required, array $optional): bool
{
    unset($params['verb']);
    foreach ($required as $r) if (!isset($params[$r])) return false;
    return !array_diff(array_keys($params), [...$required, ...$optional]);
}

$cols = ARTICLE_ISSUE_COLS . ', i.published_at AS issue_published_at';

switch ($verb) {
    case 'Identify':
        if (!oai_args_ok($params, [], [])) { $errors[] = ['badArgument', 'Illegal argument']; break; }
        $earliest = db_val('SELECT MIN(a.created_at) ' . PUBLISHED_ARTICLES) ?: date('Y-m-d');
        $body = '<Identify><repositoryName>' . $x(setting_tr('journal_name')) . '</repositoryName><baseURL>' . $x(abs_url('oai.php')) . '</baseURL>'
            . '<protocolVersion>2.0</protocolVersion><adminEmail>' . $x(setting('email') ?: 'admin@' . $host) . '</adminEmail>'
            . '<earliestDatestamp>' . gmdate('Y-m-d', strtotime($earliest)) . '</earliestDatestamp><deletedRecord>no</deletedRecord>'
            . '<granularity>YYYY-MM-DD</granularity></Identify>';
        break;

    case 'ListMetadataFormats':
        if (!oai_args_ok($params, [], ['identifier'])) { $errors[] = ['badArgument', 'Illegal argument']; break; }
        $body = '<ListMetadataFormats><metadataFormat><metadataPrefix>oai_dc</metadataPrefix>'
            . '<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>'
            . '<metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace></metadataFormat></ListMetadataFormats>';
        break;

    case 'ListSets':
        if (!oai_args_ok($params, [], [])) { $errors[] = ['badArgument', 'Illegal argument']; break; }
        $body = '<ListSets>';
        foreach (get_sections() as $s) {
            $body .= '<set><setSpec>section:' . (int) $s['id'] . '</setSpec><setName>' . $x(tr($s, 'title')) . '</setName></set>';
        }
        $body .= '</ListSets>';
        break;

    case 'GetRecord':
        if (!oai_args_ok($params, ['identifier', 'metadataPrefix'], [])) { $errors[] = ['badArgument', 'identifier and metadataPrefix are required']; break; }
        if ($params['metadataPrefix'] !== 'oai_dc') { $errors[] = ['cannotDisseminateFormat', 'Only oai_dc is supported']; break; }
        $a = preg_match('~^oai:' . preg_quote($host, '~') . ':article/(\d+)$~', $params['identifier'], $m)
            ? db_one("SELECT $cols " . PUBLISHED_ARTICLES . ' AND a.id = ?', [(int) $m[1]]) : null;
        if (!$a) { $errors[] = ['idDoesNotExist', 'No such record']; break; }
        $body = '<GetRecord>' . oai_record_xml($a, $host, $x, true) . '</GetRecord>';
        break;

    case 'ListRecords':
    case 'ListIdentifiers':
        if (isset($params['resumptionToken'])) {
            if (!oai_args_ok($params, ['resumptionToken'], [])) { $errors[] = ['badArgument', 'resumptionToken is exclusive']; break; }
            $state = json_decode((string) base64_decode(strtr($params['resumptionToken'], '-_', '+/'), true), true);
            if (!is_array($state) || !isset($state['o'])) { $errors[] = ['badResumptionToken', 'Invalid token']; break; }
        } else {
            if (!oai_args_ok($params, ['metadataPrefix'], ['from', 'until', 'set'])) { $errors[] = ['badArgument', 'metadataPrefix is required']; break; }
            if ($params['metadataPrefix'] !== 'oai_dc') { $errors[] = ['cannotDisseminateFormat', 'Only oai_dc is supported']; break; }
            $state = ['o' => 0, 'f' => $params['from'] ?? '', 'u' => $params['until'] ?? '', 's' => $params['set'] ?? ''];
        }
        $where = '';
        $args = [];
        foreach (['f' => '>=', 'u' => '<='] as $k => $op) {
            if (($state[$k] ?? '') === '') continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $state[$k])) { $errors[] = ['badArgument', 'Dates must be YYYY-MM-DD']; break 2; }
            $where .= " AND DATE(a.created_at) $op ?";
            $args[] = $state[$k];
        }
        if (($state['s'] ?? '') !== '') {
            if (!preg_match('/^section:(\d+)$/', $state['s'], $m)) { $errors[] = ['badArgument', 'Unknown set']; break; }
            $where .= ' AND a.section_id = ?';
            $args[] = (int) $m[1];
        }
        $total = (int) db_val('SELECT COUNT(*) ' . PUBLISHED_ARTICLES . $where, $args);
        $offset = max(0, (int) $state['o']);
        $rows = db_all("SELECT $cols " . PUBLISHED_ARTICLES . $where . ' ORDER BY a.id LIMIT ' . OAI_PAGE . ' OFFSET ' . $offset, $args);
        if (!$rows) { $errors[] = ['noRecordsMatch', 'No records']; break; }
        $body = "<$verb>";
        foreach ($rows as $a) $body .= oai_record_xml($a, $host, $x, $verb === 'ListRecords');
        if ($offset + OAI_PAGE < $total) {
            $token = strtr(base64_encode(json_encode(['o' => $offset + OAI_PAGE] + $state)), '+/', '-_');
            $body .= '<resumptionToken completeListSize="' . $total . '" cursor="' . $offset . '">' . $x($token) . '</resumptionToken>';
        } elseif ($offset > 0) {
            $body .= '<resumptionToken completeListSize="' . $total . '" cursor="' . $offset . '"/>';
        }
        $body .= "</$verb>";
        break;

    default:
        $errors[] = ['badVerb', 'Illegal OAI verb'];
}

header('Content-Type: text/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ',
    'xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">';
echo '<responseDate>', gmdate('Y-m-d\TH:i:s\Z'), '</responseDate>';
$reqAttrs = '';
if (!$errors || $errors[0][0] !== 'badVerb') {
    foreach ($params as $k => $v) if (preg_match('/^[a-zA-Z]+$/', $k)) $reqAttrs .= " $k=\"" . $x($v) . '"';
}
echo '<request', $reqAttrs, '>', $x(abs_url('oai.php')), '</request>';
foreach ($errors as [$code, $msg]) echo '<error code="', $code, '">', $x($msg), '</error>';
if (!$errors) echo $body;
echo '</OAI-PMH>';
