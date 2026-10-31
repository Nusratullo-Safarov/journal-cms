<?php

const LANGS = ['tj', 'ru', 'en'];
const LANG_LABELS = ['tj' => 'ТҶ', 'ru' => 'РУ', 'en' => 'EN'];
const LANG_NAMES = ['tj' => 'Тоҷикӣ', 'ru' => 'Русский', 'en' => 'English'];
const DEFAULT_LANG = 'tj';
const LANG_COOKIE = 'lang';

function current_lang(): string
{
    $v = $_COOKIE[LANG_COOKIE] ?? '';
    return in_array($v, LANGS, true) ? $v : DEFAULT_LANG;
}

function set_lang(string $lang): void
{
    if (!in_array($lang, LANGS, true)) return;
    setcookie(LANG_COOKIE, $lang, [
        'expires' => time() + 365 * 86400,
        'path' => base_path() . '/',
        'samesite' => 'Lax',
        'secure' => is_https(),
    ]);
}

/** Админ-панель переведена на таджикский и русский; при английском сайте админка — на таджикском. */
function admin_lang(): string
{
    return current_lang() === 'ru' ? 'ru' : 'tj';
}

function html_lang(): string
{
    return ['tj' => 'tg', 'ru' => 'ru', 'en' => 'en'][current_lang()];
}

function dict(string $file, string $lang): array
{
    static $cache = [];
    $cache[$file] ??= require __DIR__ . "/lang/$file.php";
    return $cache[$file][$lang];
}

/** Текст сайта: t('found', ['n' => 3]) */
function t(string $key, array $vars = []): string
{
    $s = dict('site', current_lang())[$key] ?? $key;
    return $vars ? fmt($s, $vars) : $s;
}

/** Текст админ-панели */
function ta(string $key, array $vars = []): string
{
    $s = dict('admin', admin_lang())[$key] ?? $key;
    return $vars ? fmt($s, $vars) : $s;
}

/**
 * Поле на текущем языке с запасным вариантом: tr($article, 'title') → title_ru, если пусто — title_tj и т.д.
 */
function tr(array $row, string $field, ?string $lang = null): string
{
    $lang ??= current_lang();
    foreach (array_unique([$lang, 'tj', 'ru', 'en']) as $l) {
        $v = (string) ($row["{$field}_$l"] ?? '');
        if ($v !== '') return $v;
    }
    return '';
}

/** Настройка на текущем языке: setting_tr('journal_name') */
function setting_tr(string $key): string
{
    foreach (array_unique([current_lang(), 'tj', 'ru', 'en']) as $l) {
        $v = setting("{$key}_$l");
        if ($v !== '') return $v;
    }
    return '';
}
