<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

$columns = $pdo
    ->query('PRAGMA table_info(deadlines)')
    ->fetchAll(PDO::FETCH_ASSOC);

$existingColumns = array_column($columns, 'name');

if (in_array('group_code', $existingColumns, true)) {
    echo 'Column already exists: group_code' . PHP_EOL;
} else {
    $pdo->exec(
        'ALTER TABLE deadlines ADD COLUMN group_code TEXT'
    );

    echo 'Added column: group_code' . PHP_EOL;
}

$pdo->exec(
    'CREATE INDEX IF NOT EXISTS
     idx_deadlines_group_published_due
     ON deadlines(group_code, is_published, due_at_utc)'
);

echo 'Deadline group migration complete.' . PHP_EOL;
