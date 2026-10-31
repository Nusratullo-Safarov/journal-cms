<?php

const SUBMISSION_STATUSES = ['new', 'review', 'accepted', 'rejected'];

function get_sections(): array
{
    static $cache = null;
    return $cache ??= db_all('SELECT * FROM sections ORDER BY sort, id');
}

function get_menu_pages(): array
{
    static $cache = null;
    return $cache ??= db_all('SELECT id, slug, title_tj, title_ru, title_en FROM pages WHERE in_menu = 1 ORDER BY sort, id');
}

function get_issue(int $id, bool $publishedOnly = true): ?array
{
    return db_one('SELECT * FROM issues WHERE id = ?' . ($publishedOnly ? ' AND is_published = 1' : ''), [$id]);
}

/** Последний опубликованный выпуск. */
function latest_issue(): ?array
{
    return db_one('SELECT * FROM issues WHERE is_published = 1 ORDER BY year DESC, published_at DESC, id DESC LIMIT 1');
}

/** Опубликованные выпуски, сгруппированные по годам: [2026 => [...], 2025 => [...]] */
function issues_by_year(): array
{
    $out = [];
    foreach (db_all('SELECT * FROM issues WHERE is_published = 1 ORDER BY year DESC, published_at DESC, id DESC') as $i) {
        $out[$i['year']][] = $i;
    }
    return $out;
}

/** Статьи выпуска, сгруппированные по рубрикам в порядке рубрик. */
function issue_contents(int $issueId, bool $publishedOnly = true): array
{
    $articles = db_all(
        'SELECT a.* FROM articles a LEFT JOIN sections s ON s.id = a.section_id
         WHERE a.issue_id = ?' . ($publishedOnly ? ' AND a.is_published = 1' : '') . '
         ORDER BY s.sort IS NULL, s.sort, s.id, a.sort, a.id',
        [$issueId]
    );
    $sections = array_column(get_sections(), null, 'id');
    $groups = [];
    foreach ($articles as $a) {
        $key = $a['section_id'] ?? 0;
        $groups[$key] ??= ['section' => $sections[$key] ?? null, 'articles' => []];
        $groups[$key]['articles'][] = $a;
    }
    return array_values($groups);
}

/** Опубликованная статья вместе с данными выпуска (поля выпуска с префиксом issue_). */
function get_article(int $id, bool $publishedOnly = true): ?array
{
    return db_one(
        'SELECT a.*, i.year AS issue_year, i.volume AS issue_volume, i.number AS issue_number, i.published_at AS issue_published_at,
                i.is_published AS issue_is_published
         FROM articles a JOIN issues i ON i.id = a.issue_id
         WHERE a.id = ?' . ($publishedOnly ? ' AND a.is_published = 1 AND i.is_published = 1' : ''),
        [$id]
    );
}

/** Пешнамоиш: админ мақола ё шумораи сиёҳнависро бо ?preview=1 дида метавонад. */
function is_preview(): bool
{
    return isset($_GET['preview']) && current_user() !== null;
}

/** Маълумоти иқтибос барои ГОСТ, BibTeX ва RIS. */
function citation_data(array $a, ?string $lang = null): array
{
    return [
        'authors' => array_column(parse_authors(tr($a, 'authors', $lang)), 'name'),
        'title' => tr($a, 'title', $lang),
        'journal' => setting_tr('journal_name'),
        'year' => (string) $a['issue_year'],
        'volume' => (string) $a['issue_volume'],
        'number' => (string) $a['issue_number'],
        'pages' => (string) $a['pages'],
        'doi' => (string) $a['doi'],
        'issn' => setting('issn_online') ?: setting('issn_print'),
        'url' => abs_url('article.php', ['id' => $a['id']]),
        'abstract' => tr($a, 'abstract', $lang),
        'keywords' => keywords_list(tr($a, 'keywords', $lang)),
        'publisher' => setting_tr('publisher'),
    ];
}

/** Иқтибос бо ГОСТ: Муаллифон. Ном // Маҷалла. – Сол. – № N. – С. 5–14. */
function gost_citation(array $a): string
{
    $c = citation_data($a);
    $en = current_lang() === 'en';
    $s = $c['authors'] ? rtrim(implode(', ', $c['authors']), '.') . '. ' : '';
    $s .= rtrim($c['title'], '.') . ' // ' . $c['journal'] . '. – ' . $c['year'] . '.';
    if ($c['volume'] !== '') $s .= ' – ' . ($en ? 'Vol. ' : 'Т. ') . $c['volume'] . '.';
    $s .= ' – № ' . $c['number'] . '.';
    if ($c['pages'] !== '') $s .= ' – ' . ($en ? 'P. ' : 'С. ') . str_replace('-', '–', $c['pages']) . '.';
    if ($c['doi'] !== '') $s .= ' – DOI: ' . $c['doi'] . '.';
    return $s;
}

function search_articles(string $q, int $limit = 100): array
{
    $like = '%' . addcslashes($q, '\\%_') . '%';
    $fields = [];
    foreach (['title', 'authors', 'abstract', 'keywords'] as $f) {
        foreach (LANGS as $l) $fields[] = "a.{$f}_$l LIKE ?";
    }
    return db_all(
        'SELECT a.*, i.year AS issue_year, i.volume AS issue_volume, i.number AS issue_number
         FROM articles a JOIN issues i ON i.id = a.issue_id
         WHERE a.is_published = 1 AND i.is_published = 1 AND (' . implode(' OR ', $fields) . ')
         ORDER BY i.year DESC, i.id DESC, a.sort LIMIT ' . (int) $limit,
        array_fill(0, count($fields), $like)
    );
}

function latest_news(int $limit): array
{
    return db_all('SELECT * FROM news WHERE is_published = 1 AND published_at <= NOW() ORDER BY published_at DESC, id DESC LIMIT ' . (int) $limit);
}

// ---------- Форматирование ----------

/** «№ 2, 2026» или «Ҷилди 5, № 2, 2026» */
function issue_label(array $issue, string $prefix = ''): string
{
    $v = $issue[$prefix . 'volume'];
    $vars = ['v' => $v, 'n' => $issue[$prefix . 'number'], 'year' => $issue[$prefix . 'year']];
    return t($v !== '' ? 'issueVolN' : 'issueN', $vars);
}

/**
 * Авторы хранятся построчно: «Фамилия И.О. | Организация».
 * Возвращает [['name' => ..., 'affiliation' => ...], ...]
 */
function parse_authors(string $text): array
{
    $out = [];
    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        [$name, $aff] = array_map('trim', explode('|', $line, 2) + [1 => '']);
        $out[] = ['name' => $name, 'affiliation' => $aff];
    }
    return $out;
}

function authors_names(array $article, ?string $lang = null): string
{
    return implode(', ', array_column(parse_authors(tr($article, 'authors', $lang)), 'name'));
}

function keywords_list(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/[,;]\s*/u', $text))));
}

// ---------- Барои саҳифаи асосӣ, муаллифон, рубрикаҳо ----------

const PUBLISHED_ARTICLES = 'FROM articles a JOIN issues i ON i.id = a.issue_id WHERE a.is_published = 1 AND i.is_published = 1';
const ARTICLE_ISSUE_COLS = 'a.*, i.year AS issue_year, i.volume AS issue_volume, i.number AS issue_number';

function site_stats(): array
{
    $authors = [];
    foreach (db_all('SELECT a.authors_tj, a.authors_ru, a.authors_en ' . PUBLISHED_ARTICLES) as $a) {
        foreach (parse_authors(tr($a, 'authors')) as $au) $authors[mb_strtolower($au['name'])] = 1;
    }
    return [
        'issues' => (int) db_val('SELECT COUNT(*) FROM issues WHERE is_published = 1'),
        'articles' => (int) db_val('SELECT COUNT(*) ' . PUBLISHED_ARTICLES),
        'authors' => count($authors),
        'downloads' => (int) db_val('SELECT COALESCE(SUM(a.downloads), 0) ' . PUBLISHED_ARTICLES),
    ];
}

function popular_articles(int $limit): array
{
    return db_all('SELECT ' . ARTICLE_ISSUE_COLS . ' ' . PUBLISHED_ARTICLES . ' ORDER BY a.views + a.downloads * 3 DESC, a.id DESC LIMIT ' . (int) $limit);
}

/** Рубрикаҳо бо шумораи мақолаҳои нашршуда (танҳо онҳое, ки мақола доранд). */
function sections_with_counts(): array
{
    $counts = array_column(db_all('SELECT a.section_id, COUNT(*) AS n ' . PUBLISHED_ARTICLES . ' AND a.section_id IS NOT NULL GROUP BY a.section_id'), 'n', 'section_id');
    $out = [];
    foreach (get_sections() as $s) {
        if (!empty($counts[$s['id']])) $out[] = $s + ['n' => (int) $counts[$s['id']]];
    }
    return $out;
}

/** Мақолаҳои монанд: аввал бо калидвожаҳои умумӣ, баъд аз ҳамон рубрика. */
function related_articles(array $a, int $limit = 4): array
{
    $scores = [];
    $kw = array_slice(keywords_list(tr($a, 'keywords')), 0, 6);
    foreach ($kw as $k) {
        if (mb_strlen($k) < 3) continue;
        $like = '%' . addcslashes($k, '\\%_') . '%';
        foreach (db_all('SELECT a.id ' . PUBLISHED_ARTICLES . ' AND a.id <> ? AND (a.keywords_tj LIKE ? OR a.keywords_ru LIKE ? OR a.keywords_en LIKE ?) LIMIT 20',
            [$a['id'], $like, $like, $like]) as $r) {
            $scores[$r['id']] = ($scores[$r['id']] ?? 0) + 2;
        }
    }
    if ($a['section_id']) {
        foreach (db_all('SELECT a.id ' . PUBLISHED_ARTICLES . ' AND a.id <> ? AND a.section_id = ? ORDER BY a.id DESC LIMIT 10', [$a['id'], $a['section_id']]) as $r) {
            $scores[$r['id']] = ($scores[$r['id']] ?? 0) + 1;
        }
    }
    if (!$scores) return [];
    arsort($scores);
    $ids = array_slice(array_keys($scores), 0, $limit);
    $rows = array_column(db_all('SELECT ' . ARTICLE_ISSUE_COLS . ' ' . PUBLISHED_ARTICLES . ' AND a.id IN (' . implode(',', array_map('intval', $ids)) . ')'), null, 'id');
    return array_values(array_filter(array_map(fn($id) => $rows[$id] ?? null, $ids)));
}

/** Мақолаи қаблӣ ва навбатӣ дар ҳамон шумора (бо тартиби мундариҷа). */
function neighbor_articles(array $a): array
{
    $list = [];
    foreach (issue_contents((int) $a['issue_id']) as $g) foreach ($g['articles'] as $x) $list[] = $x;
    $ids = array_column($list, 'id');
    $i = array_search($a['id'], $ids, true);
    if ($i === false) return [null, null];
    return [$list[$i - 1] ?? null, $list[$i + 1] ?? null];
}

/**
 * Ҳамаи муаллифон: [['name' => ..., 'n' => шумораи мақолаҳо], ...] бо тартиби алифбо.
 * Ном аз забони ҷорӣ гирифта мешавад (агар набошад — аз забони дигар).
 */
function authors_index(): array
{
    $out = [];
    foreach (db_all('SELECT a.authors_tj, a.authors_ru, a.authors_en ' . PUBLISHED_ARTICLES) as $a) {
        foreach (parse_authors(tr($a, 'authors')) as $au) {
            $key = mb_strtolower($au['name']);
            $out[$key] ??= ['name' => $au['name'], 'n' => 0];
            $out[$key]['n']++;
        }
    }
    // Collator (расширение intl) правильно сортирует кириллицу с таджикскими буквами; без него — простое сравнение
    $coll = class_exists('Collator') ? new Collator('ru_RU') : null;
    uasort($out, fn($x, $y) => $coll ? $coll->compare($x['name'], $y['name']) : strcmp(mb_strtolower($x['name']), mb_strtolower($y['name'])));
    return array_values($out);
}

/** Мақолаҳои як муаллиф: номро дар ҳар се забон месанҷад (мувофиқати пурра, на қисман). */
function author_articles(string $name): array
{
    $like = '%' . addcslashes($name, '\\%_') . '%';
    $needle = mb_strtolower($name);
    $out = [];
    foreach (db_all('SELECT ' . ARTICLE_ISSUE_COLS . ' ' . PUBLISHED_ARTICLES . ' AND (a.authors_tj LIKE ? OR a.authors_ru LIKE ? OR a.authors_en LIKE ?)
                     ORDER BY i.year DESC, i.id DESC, a.sort', [$like, $like, $like]) as $a) {
        foreach (LANGS as $l) {
            foreach (parse_authors($a["authors_$l"]) as $au) {
                if (mb_strtolower($au['name']) === $needle) {
                    $out[] = $a + ['_affiliation' => $au['affiliation']];
                    continue 3;
                }
            }
        }
    }
    return $out;
}

/** Ҷустуҷӯ бо филтрҳо ва саҳифабандӣ. Бармегардонад [мақолаҳо, ҳамагӣ]. */
function search_articles_filtered(string $q, int $year, int $sectionId, int $limit, int $offset): array
{
    $where = [];
    $args = [];
    if ($q !== '') {
        $like = '%' . addcslashes($q, '\\%_') . '%';
        $fields = [];
        foreach (['title', 'authors', 'abstract', 'keywords'] as $f) {
            foreach (LANGS as $l) $fields[] = "a.{$f}_$l LIKE ?";
        }
        $fields[] = 'a.udc LIKE ?';
        $fields[] = 'a.doi LIKE ?';
        $where[] = '(' . implode(' OR ', $fields) . ')';
        array_push($args, ...array_fill(0, count($fields), $like));
    }
    if ($year) {
        $where[] = 'i.year = ?';
        $args[] = $year;
    }
    if ($sectionId) {
        $where[] = 'a.section_id = ?';
        $args[] = $sectionId;
    }
    $cond = $where ? ' AND ' . implode(' AND ', $where) : '';
    $total = (int) db_val('SELECT COUNT(*) ' . PUBLISHED_ARTICLES . $cond, $args);
    $rows = db_all('SELECT ' . ARTICLE_ISSUE_COLS . ' ' . PUBLISHED_ARTICLES . $cond . ' ORDER BY i.year DESC, i.id DESC, a.sort, a.id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset, $args);
    return [$rows, $total];
}

function published_years(): array
{
    return array_map('intval', array_column(db_all('SELECT DISTINCT year FROM issues WHERE is_published = 1 ORDER BY year DESC'), 'year'));
}
