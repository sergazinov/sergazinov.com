<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

$columns = $pdo
    ->query('PRAGMA table_info(deadlines)')
    ->fetchAll(PDO::FETCH_ASSOC);

$existingColumns = array_column($columns, 'name');

$columnsToAdd = [
    'source_type' => 'TEXT',
    'source_url' => 'TEXT',
    'external_id' => 'TEXT',
    'assignment_url' => 'TEXT',
    'last_seen_at' => 'TEXT',
];

foreach ($columnsToAdd as $name => $type) {
    if (in_array($name, $existingColumns, true)) {
        echo "Column already exists: {$name}" . PHP_EOL;
        continue;
    }

    $pdo->exec(
        "ALTER TABLE deadlines ADD COLUMN {$name} {$type}"
    );

    echo "Added column: {$name}" . PHP_EOL;
}

$pdo->exec(
    'CREATE UNIQUE INDEX IF NOT EXISTS
     idx_deadlines_source_external
     ON deadlines(source_type, external_id)'
);

echo 'Import metadata migration complete.' . PHP_EOL;
