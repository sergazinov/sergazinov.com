<?php

declare(strict_types=1);

require_once dirname(__DIR__)
    . '/src/Importers/LinearAlgebraImporter.php';

$importer = new LinearAlgebraImporter();

$content = $importer->fetch();

$weeks = $importer->parseWeeks($content);

$schedule = require dirname(__DIR__) . '/config/schedule.php';

$linearAlgebraSchedule =
    $schedule['263']['linear-algebra'];

$timezone = new DateTimeZone(
    $linearAlgebraSchedule['timezone']
);

$now = new DateTimeImmutable(
    'now',
    $timezone
);

$nextSeminar = $now
    ->modify('next saturday')
    ->setTime(13, 10);

echo 'Next Linear Algebra seminar: '
    . $nextSeminar->format('Y-m-d H:i P')
    . PHP_EOL
    . PHP_EOL;

$deadlineUtc = $nextSeminar->setTimezone(
    new DateTimeZone('UTC')
);

echo 'Deadline UTC: '
    . $deadlineUtc->format('Y-m-d\TH:i:s\Z')
    . PHP_EOL
    . PHP_EOL;

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
