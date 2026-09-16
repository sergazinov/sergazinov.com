<?php

declare(strict_types=1);

require_once dirname(__DIR__)
    . '/src/Importers/LinearAlgebraImporter.php';

$pdo = require dirname(__DIR__)
    . '/config/database.php';

$schedule = require dirname(__DIR__)
    . '/config/schedule.php';

$courseSchedule = $schedule['263']['linear-algebra'];

$timezone = new DateTimeZone(
    $courseSchedule['timezone']
);

$firstDeadline = new DateTimeImmutable(
    $courseSchedule['first_homework_deadline'],
    $timezone
);

$courseStatement = $pdo->prepare(
    'SELECT id
     FROM courses
     WHERE slug = :slug
     LIMIT 1'
);

$courseStatement->execute([
    'slug' => 'linear-algebra',
]);

$courseId = $courseStatement->fetchColumn();

if ($courseId === false) {
    throw new RuntimeException(
        'Linear Algebra course not found in database.'
    );
}

$importer = new LinearAlgebraImporter();

$content = $importer->fetch();
$weeks = $importer->parseWeeks($content);

$upsert = $pdo->prepare(
    'INSERT INTO deadlines (
        course_id,
        title,
        description,
        due_at_utc,
        source_timezone,
        is_published,
        source_type,
        source_url,
        external_id,
        assignment_url,
        last_seen_at
    ) VALUES (
        :course_id,
        :title,
        :description,
        :due_at_utc,
        :source_timezone,
        1,
        :source_type,
        :source_url,
        :external_id,
        :assignment_url,
        CURRENT_TIMESTAMP
    )
    ON CONFLICT(source_type, external_id)
    DO UPDATE SET
        course_id = excluded.course_id,
        title = excluded.title,
        description = excluded.description,
        due_at_utc = excluded.due_at_utc,
        source_timezone = excluded.source_timezone,
        source_url = excluded.source_url,
        assignment_url = excluded.assignment_url,
        last_seen_at = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP,
        is_published = 1'
);

foreach ($weeks as $week) {
    if ($week['homework_url'] === null) {
        echo 'Skipping Week '
            . $week['number']
            . ': homework not published.'
            . PHP_EOL;

        continue;
    }

    $weekNumber = (int) $week['number'];

    if ($weekNumber < 1) {
        continue;
    }

    $deadlineLocal = $firstDeadline->modify(
        '+' . ($weekNumber - 1) . ' weeks'
    );

    $deadlineUtc = $deadlineLocal->setTimezone(
        new DateTimeZone('UTC')
    );

    $externalId =
        'linear-algebra-week-' . $weekNumber;

    $upsert->execute([
        'course_id' => $courseId,
        'title' => 'Homework ' . $weekNumber,
        'description' => $week['topic'],
        'due_at_utc' => $deadlineUtc->format(
            'Y-m-d\TH:i:s\Z'
        ),
        'source_timezone' => $courseSchedule['timezone'],
        'source_type' => 'hse-wiki',
        'source_url' => $importer->sourceUrl(),
        'external_id' => $externalId,
        'assignment_url' => $week['homework_url'],
    ]);

    echo 'Imported: '
        . $externalId
        . ' | '
        . $deadlineLocal->format('Y-m-d H:i P')
        . PHP_EOL;
}
