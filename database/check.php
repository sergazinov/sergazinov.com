<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__) . '/config/database.php';

echo "Database connection: OK" . PHP_EOL;

$tables = $pdo
    ->query("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
          AND name NOT LIKE 'sqlite_%'
        ORDER BY name
    ")
    ->fetchAll(PDO::FETCH_COLUMN);

echo "Tables:" . PHP_EOL;

foreach ($tables as $table) {
    echo "- {$table}" . PHP_EOL;
}