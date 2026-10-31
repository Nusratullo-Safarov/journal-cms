<?php
// Очистка HTML из визуального редактора: оставляем только безопасные теги и атрибуты.
// Всё остальное (script, style, обработчики onclick, javascript:-ссылки) удаляется.

const HTML_ALLOWED = [
    'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [],
    'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
    'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [],
    'a' => ['href', 'title'],
    'img' => ['src', 'alt'],
    'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
];

/** Теги, которые удаляются вместе с содержимым. */
const HTML_DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'noscript', 'template', 'svg', 'math', 'head', 'title', 'meta', 'link'];

function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') return '';
    $doc = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) return '';
    sanitize_node($root);
    $out = '';
    foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
    return trim($out);
}

function sanitize_node(DOMNode $node): void
{
    // Копируем список: дерево меняется во время обхода
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) continue;
        if (!$child instanceof DOMElement) {
            $node->removeChild($child); // комментарии, инструкции обработки
            continue;
        }
        $tag = strtolower($child->tagName);
        if (in_array($tag, HTML_DROP, true)) {
            $node->removeChild($child);
            continue;
        }
        sanitize_node($child);
        if (!isset(HTML_ALLOWED[$tag])) {
            // Неизвестный тег (div, span, font…) — оставляем только содержимое
            while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
            $node->removeChild($child);
            continue;
        }
        foreach (iterator_to_array($child->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (!in_array($name, HTML_ALLOWED[$tag], true) || !safe_attr_value($name, $attr->value)) {
                $child->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a' && $child->hasAttribute('href') && preg_match('~^https?://~i', $child->getAttribute('href'))) {
            $child->setAttribute('rel', 'noopener');
            $child->setAttribute('target', '_blank');
        }
    }
}

function safe_attr_value(string $name, string $value): bool
{
    if ($name === 'href' || $name === 'src') {
        $v = trim(preg_replace('/[\x00-\x20]+/', '', $value));
        // Разрешены http(s), mailto, tel и ссылки внутри сайта
        return (bool) preg_match('~^(https?://|mailto:|tel:|/(?!/)|#|[a-z0-9_\-./]+(\?|$))~i', $v)
            && !preg_match('~^(javascript|data|vbscript):~i', $v);
    }
    if ($name === 'colspan' || $name === 'rowspan') return (bool) preg_match('/^\d{1,2}$/', $value);
    return true;
}

/** Обычный текст из HTML (для описаний и мета-тегов). */
function html_to_text(string $html, int $max = 300): string
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
}
