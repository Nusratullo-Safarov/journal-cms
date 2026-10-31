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
function get_article(int $id): ?array
{
    return db_one(
        'SELECT a.*, i.year AS issue_year, i.volume AS issue_volume, i.number AS issue_number, i.published_at AS issue_published_at
         FROM articles a JOIN issues i ON i.id = a.issue_id
         WHERE a.id = ? AND a.is_published = 1 AND i.is_published = 1',
        [$id]
    );
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
    foreach (preg_split('/\R/', $text) as $line) {
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
