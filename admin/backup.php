<?php
// Нусхаи эҳтиётӣ: пойгоҳи додаҳо (.sql) ва файлҳо (.zip). Танҳо барои администратор.
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
require_admin_role();

$canZip = class_exists('ZipArchive');

if (is_post()) {
    admin_require_csrf();
    $action = post_str('action', 10);
    set_time_limit(300);
    session_write_close();
    $stamp = date('Y-m-d_H-i');

    if ($action === 'db') {
        log_action('backup', 'database');
        header('Content-Type: application/sql; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"journal-db-$stamp.sql\"");
        $pdo = db();
        echo "-- Нусхаи эҳтиётии пойгоҳи маҷалла, " . date('Y-m-d H:i') . "\n";
        echo "-- Барқарор кардан: phpMyAdmin → «Импорт» ё mysql -u USER -p DBNAME < файл.sql\n\n";
        echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET time_zone = '+05:00';\n\n";
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) continue;
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
            echo "DROP TABLE IF EXISTS `$table`;\n$create;\n\n";
            if ($table === 'rate_limits') continue; // маълумоти муваққатӣ
            $st = $pdo->query("SELECT * FROM `$table`", PDO::FETCH_ASSOC);
            $batch = [];
            $cols = null;
            $flush = function () use (&$batch, &$cols, $table) {
                if (!$batch) return;
                echo "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES\n" . implode(",\n", $batch) . ";\n";
                $batch = [];
            };
            foreach ($st as $row) {
                $cols ??= array_keys($row);
                $batch[] = '(' . implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
                if (count($batch) >= 100) $flush();
            }
            $flush();
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        exit;
    }

    if ($action === 'files' && $canZip) {
        log_action('backup', 'files');
        $tmp = tempnam(sys_get_temp_dir(), 'jbk');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        foreach (['uploads/images', 'uploads/pdf', 'storage/submissions'] as $dir) {
            foreach (glob(APP_ROOT . "/$dir/*") ?: [] as $file) {
                if (is_file($file) && basename($file) !== '.gitkeep') $zip->addFile($file, "$dir/" . basename($file));
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=\"journal-files-$stamp.zip\"");
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
    redirect('admin/backup.php');
}

// Ҳаҷми файлҳо
$size = 0;
$count = 0;
foreach (['uploads/images', 'uploads/pdf', 'storage/submissions'] as $dir) {
    foreach (glob(APP_ROOT . "/$dir/*") ?: [] as $f) {
        if (is_file($f) && basename($f) !== '.gitkeep') { $size += filesize($f); $count++; }
    }
}
$last = db_one("SELECT * FROM activity_log WHERE action = 'backup' ORDER BY id DESC LIMIT 1");

admin_header(ta('navBackup'), 'backup');
?>
<div class="narrow stack">
  <h1 class="h1"><?= e(ta('navBackup')) ?></h1>
  <p class="muted"><?= e(ta('backupHint')) ?></p>
  <?php if ($last): ?><p class="small"><?= e(ta('lastBackup')) ?>: <b><?= e(format_date($last['created_at'], true)) ?></b> (<?= e($last['user_login']) ?>)</p><?php endif ?>
  <div class="two-col">
    <form method="post" class="card pad stack-sm">
      <?= csrf_field() ?>
      <h2 class="h3">🗄 <?= e(ta('backupDb')) ?></h2>
      <p class="small muted"><?= e(ta('backupDbHint')) ?></p>
      <button class="btn btn-primary" name="action" value="db"><?= e(ta('download')) ?> .sql</button>
    </form>
    <form method="post" class="card pad stack-sm">
      <?= csrf_field() ?>
      <h2 class="h3">🗂 <?= e(ta('backupFiles')) ?></h2>
      <p class="small muted"><?= e(ta('backupFilesHint', ['n' => $count, 'size' => number_format($size / 1048576, 1, ',', ' ')])) ?></p>
      <?php if ($canZip): ?>
        <button class="btn btn-primary" name="action" value="files"><?= e(ta('download')) ?> .zip</button>
      <?php else: ?>
        <p class="alert-warn small"><?= e(ta('noZip')) ?></p>
      <?php endif ?>
    </form>
  </div>
</div>
<?php admin_footer();
