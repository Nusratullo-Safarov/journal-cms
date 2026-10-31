<?php
// Загрузка файлов. Тип всегда определяется по содержимому файла, а не по имени или MIME от браузера.

const UPLOAD_ROOT = APP_ROOT . '/uploads';
const SUBMISSIONS_DIR = APP_ROOT . '/storage/submissions';

const UPLOAD_KINDS = [
    // вид => [папка, макс. размер, допустимые расширения]
    'image' => ['uploads/images', 5 * 1024 * 1024, ['jpg', 'png', 'webp']],
    'pdf' => ['uploads/pdf', 50 * 1024 * 1024, ['pdf']],
];

class UploadError extends RuntimeException {}

function detect_file_ext(string $path): ?string
{
    $head = (string) file_get_contents($path, false, null, 0, 16);
    if (str_starts_with($head, "\xFF\xD8\xFF")) return 'jpg';
    if (str_starts_with($head, "\x89PNG\r\n\x1A\n")) return 'png';
    if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP') return 'webp';
    if (str_starts_with($head, '%PDF-')) return 'pdf';
    if (str_starts_with($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) return 'doc';
    if (str_starts_with($head, "PK\x03\x04")) return 'docx'; // DOCX — это ZIP-архив
    return null;
}

/** Был ли выбран файл в поле формы. */
function has_upload(?array $file): bool
{
    return $file !== null && !is_array($file['error'] ?? null) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/** Проверяет загруженный файл. Возвращает расширение или бросает UploadError('size'|'type'|'fail'). */
function check_upload(array $file, int $maxSize, array $allowed): string
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) throw new UploadError('size');
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) throw new UploadError('fail');
    if ($file['size'] > $maxSize) throw new UploadError('size');
    $ext = detect_file_ext($file['tmp_name']);
    if (!$ext || !in_array($ext, $allowed, true)) throw new UploadError('type');
    return $ext;
}

/** Сохраняет картинку или PDF в публичную папку uploads/ и возвращает путь вида uploads/pdf/abc….pdf */
function save_upload(array $file, string $kind): string
{
    [$dir, $max, $allowed] = UPLOAD_KINDS[$kind];
    $ext = check_upload($file, $max, $allowed);
    $abs = APP_ROOT . '/' . $dir;
    if (!is_dir($abs)) mkdir($abs, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$abs/$name")) throw new UploadError('fail');
    @chmod("$abs/$name", 0644);
    return "$dir/$name";
}

/** Удаляет файл, если путь указывает на нашу папку uploads/. Внешние ссылки и чужие пути игнорируются. */
function delete_upload(string $path): void
{
    if (preg_match('~^uploads/(images|pdf)/([a-f0-9]{24}\.(jpg|png|webp|pdf))$~', $path, $m)) {
        @unlink(UPLOAD_ROOT . "/$m[1]/$m[2]");
    }
}

/**
 * Обрабатывает поле файла в админке: новый файл заменяет старый, галочка «удалить» очищает.
 * Возвращает новый путь. Старый файл удаляется вызывающим кодом после успешного сохранения в базу.
 */
function handle_file_field(string $field, string $kind, string $current): string
{
    if (has_upload($_FILES[$field] ?? null)) return save_upload($_FILES[$field], $kind);
    if (!empty($_POST[$field . '_remove'])) return '';
    return $current;
}

function upload_error_text(UploadError $ex, string $kind): string
{
    return match ($ex->getMessage()) {
        'size' => ta($kind === 'pdf' ? 'errPdfSize' : 'errImageSize'),
        'type' => ta($kind === 'pdf' ? 'errPdfType' : 'errImageType'),
        default => ta('errUpload'),
    };
}
