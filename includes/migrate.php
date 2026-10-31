<?php
// Навсозии худкори сохтори пойгоҳ барои сомонаҳое, ки бо версияи кӯҳна насб шудаанд.
// Версияи ҷорӣ дар settings.db_version нигоҳ дошта мешавад. Ҳар қадам такроран иҷро шуда метавонад.

const DB_VERSION = 2;

function ensure_schema(): void
{
    $v = (int) setting('db_version', '1');
    if ($v >= DB_VERSION) return;

    if ($v < 2) {
        db()->exec("CREATE TABLE IF NOT EXISTS article_stats (
            article_id INT UNSIGNED NOT NULL, day DATE NOT NULL,
            views INT UNSIGNED NOT NULL DEFAULT 0, downloads INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (article_id, day), KEY idx_article_stats_day (day),
            CONSTRAINT fk_article_stats_article FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        db()->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL,
            user_login VARCHAR(50) NOT NULL DEFAULT '', action VARCHAR(20) NOT NULL, entity VARCHAR(30) NOT NULL,
            entity_id INT UNSIGNED NULL, title VARCHAR(300) NOT NULL DEFAULT '', ip VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_activity_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if (!db_one("SHOW COLUMNS FROM submissions LIKE 'notes'")) {
            db()->exec('ALTER TABLE submissions ADD COLUMN notes TEXT NULL AFTER status');
        }
        $defaults = ['announcement_tj' => '', 'announcement_ru' => '', 'announcement_en' => '', 'indexing' => ''];
        save_settings(array_diff_key($defaults, settings_all()));
    }

    save_settings(['db_version' => (string) DB_VERSION]);
}
