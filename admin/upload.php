<?php
// Бор кардани сурат аз муҳаррири матн (саҳифаҳо, хабарҳо). Ҷавоб: {"url": "..."} ё {"error": "..."}
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';

header('Content-Type: application/json; charset=utf-8');
if (!current_user()) {
    http_response_code(403);
    exit(json_encode(['error' => ta('errNoAccess')]));
}
if (!is_post() || !csrf_ok()) {
    http_response_code(400);
    exit(json_encode(['error' => post_too_large() ? ta('errImageSize') : ta('errCsrf')]));
}
$file = $_FILES['file'] ?? null;
if (!has_upload($file)) {
    http_response_code(400);
    exit(json_encode(['error' => ta('errUpload')]));
}
try {
    $path = save_upload($file, 'image');
    log_action('upload', 'image', null, $path);
    echo json_encode(['url' => url($path)]);
} catch (UploadError $ex) {
    http_response_code(400);
    echo json_encode(['error' => upload_error_text($ex, 'image')]);
}
