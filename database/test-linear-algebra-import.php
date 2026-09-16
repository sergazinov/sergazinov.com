<?php

declare(strict_types=1);

require_once dirname(__DIR__)
    . '/src/Importers/LinearAlgebraImporter.php';

$importer = new LinearAlgebraImporter();

$content = $importer->fetch();

$weeks = $importer->parseWeeks($content);

foreach ($weeks as $week) {
    echo 'Week: '
        . ($week['number'] ?? 'unknown')
        . PHP_EOL;

    echo 'Topic: '
        . ($week['topic'] ?? 'unknown')
        . PHP_EOL;

    echo 'Homework: '
        . ($week['homework_url'] ?? 'NOT PUBLISHED')
        . PHP_EOL;

    echo PHP_EOL;
}