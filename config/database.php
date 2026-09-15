<?php

declare(strict_types=1);

$databasePath = dirname(__DIR__) . '/storage/database.sqlite';

if (!file_exists($databasePath)) {
    throw new RuntimeException('Database file not found.');
}

$pdo = new PDO(
    'sqlite:' . $databasePath,
    null,
    null,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$pdo->exec('PRAGMA foreign_keys = ON');

return $pdo;