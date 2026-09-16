<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__) . '/config/database.php';

$stmt = $pdo->prepare(
    'SELECT
        id,
        title,
        description,
        due_at_utc,
        source_type,
        external_id,
        assignment_url
     FROM deadlines
     WHERE source_type = :source_type'
);

$stmt->execute([
    'source_type' => 'hse-wiki',
]);

$deadlines = $stmt->fetchAll();

echo 'Imported HSE Wiki deadlines: '
    . count($deadlines)
    . PHP_EOL
    . PHP_EOL;

foreach ($deadlines as $deadline) {
    echo 'ID: ' . $deadline['id'] . PHP_EOL;
    echo 'Title: ' . $deadline['title'] . PHP_EOL;
    echo 'Description: ' . $deadline['description'] . PHP_EOL;
    echo 'Due UTC: ' . $deadline['due_at_utc'] . PHP_EOL;
    echo 'External ID: ' . $deadline['external_id'] . PHP_EOL;
    echo 'Assignment: ' . $deadline['assignment_url'] . PHP_EOL;
    echo PHP_EOL;
}
