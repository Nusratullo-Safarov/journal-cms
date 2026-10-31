<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), cfg('db_port', 3306), cfg('db_name'));
    try {
        $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $ex) {
        error_log('DB connection failed: ' . $ex->getMessage());
        http_response_code(503);
        exit('Ба пойгоҳи додаҳо пайваст шуда натавонистем. Танзимоти includes/config.php-ро санҷед.');
    }
    // Время в базе — по Душанбе (смещение, а не имя зоны: на хостингах часто нет таблиц часовых поясов)
    $pdo->exec("SET time_zone = '+05:00'");
    return $pdo;
}

function db_exec(string $sql, array $args = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function db_all(string $sql, array $args = []): array
{
    return db_exec($sql, $args)->fetchAll();
}

function db_one(string $sql, array $args = []): ?array
{
    return db_exec($sql, $args)->fetch() ?: null;
}

function db_val(string $sql, array $args = []): mixed
{
    $v = db_exec($sql, $args)->fetchColumn();
    return $v === false ? null : $v;
}

function db_tx(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
