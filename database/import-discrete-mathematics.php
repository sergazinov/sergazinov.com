<?php

declare(strict_types=1);

require_once dirname(__DIR__)
    . '/src/Importers/DiscreteMathematicsImporter.php';

$pdo = require dirname(__DIR__)
    . '/config/database.php';

$dryRun = in_array('--dry-run', $argv, true);

$courseStatement = $pdo->prepare(
    'SELECT id
     FROM courses
     WHERE slug = :slug
     LIMIT 1'
);

$courseStatement->execute([
    'slug' => 'discrete-mathematics',
]);

$courseId = $courseStatement->fetchColumn();

if ($courseId === false) {
    throw new RuntimeException(
        'Discrete Mathematics course not found in database.'
    );
}

$importer = new DiscreteMathematicsImporter();
$content = $importer->fetch();

$groups = [261, 262, 263];

$sourceTimezone = new DateTimeZone('Europe/Moscow');
$utcTimezone = new DateTimeZone('UTC');

$upsert = $pdo->prepare(
    'INSERT INTO deadlines (
        course_id,
        group_code,
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
        :group_code,
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
        group_code = excluded.group_code,
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

$legacyStatement = $pdo->prepare(
    'SELECT id
     FROM deadlines
     WHERE course_id = :course_id
       AND group_code = :group_code
       AND source_type IS NULL
       AND external_id IS NULL
       AND due_at_utc = :due_at_utc
     LIMIT 1'
);

$legacyUpdate = $pdo->prepare(
    'UPDATE deadlines
     SET
        title = :title,
        description = :description,
        source_timezone = :source_timezone,
        source_type = :source_type,
        source_url = :source_url,
        external_id = :external_id,
        assignment_url = :assignment_url,
        last_seen_at = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP,
        is_published = 1
     WHERE id = :id'
);

function parseDiscreteDeadline(
    string $date,
    DateTimeZone $timezone
): DateTimeImmutable {
    $parts = explode('.', $date);

    if (count($parts) !== 3) {
        throw new RuntimeException(
            'Invalid deadline date: ' . $date
        );
    }

    $yearFormat = strlen($parts[2]) === 2 ? 'y' : 'Y';

    $deadline = DateTimeImmutable::createFromFormat(
        '!d.m.' . $yearFormat . ' H:i',
        $date . ' 23:59',
        $timezone
    );

    $errors = DateTimeImmutable::getLastErrors();

    if (
        $deadline === false
        || (
            is_array($errors)
            && (
                $errors['warning_count'] > 0
                || $errors['error_count'] > 0
            )
        )
    ) {
        throw new RuntimeException(
            'Could not parse deadline date: ' . $date
        );
    }

    return $deadline;
}

foreach ($groups as $group) {
    $homeworks = $importer->parseHomeworkForGroup(
        $content,
        $group
    );

    if ($homeworks === []) {
        echo 'Group '
            . $group
            . ': no published homework.'
            . PHP_EOL;

        continue;
    }

    foreach ($homeworks as $homework) {
        $number = (int) $homework['number'];

        if ($number < 1) {
            continue;
        }

        $deadlineLocal = parseDiscreteDeadline(
            $homework['deadline_date'],
            $sourceTimezone
        );

        $deadlineUtc = $deadlineLocal->setTimezone(
            $utcTimezone
        );

        $dueAtUtc = $deadlineUtc->format(
            'Y-m-d\TH:i:s\Z'
        );

        $externalId =
            'discrete-mathematics-'
            . $group
            . '-homework-'
            . $number;

        $parameters = [
            'course_id' => $courseId,
            'group_code' => (string) $group,
            'title' => 'Homework ' . $number,
            'description' => null,
            'due_at_utc' => $dueAtUtc,
            'source_timezone' => 'Europe/Moscow',
            'source_type' => 'hse-wiki',
            'source_url' => $importer->sourceUrl(),
            'external_id' => $externalId,
            'assignment_url' => $homework['assignment_url'],
        ];

        if ($dryRun) {
            echo 'Would import: '
                . $externalId
                . ' | '
                . $deadlineLocal->format(
                    'Y-m-d H:i T'
                )
                . ' | '
                . $dueAtUtc
                . PHP_EOL;

            continue;
        }

        /*
         * Normalize the old manually seeded 263 Homework 1
         * instead of creating a duplicate row.
         */
        if ($group === 263 && $number === 1) {
            $legacyStatement->execute([
                'course_id' => $courseId,
                'group_code' => '263',
                'due_at_utc' => $dueAtUtc,
            ]);

            $legacyId = $legacyStatement->fetchColumn();

            if ($legacyId !== false) {
                $legacyUpdate->execute([
                    'id' => $legacyId,
                    'title' => $parameters['title'],
                    'description' => $parameters['description'],
                    'source_timezone' =>
                    $parameters['source_timezone'],
                    'source_type' =>
                    $parameters['source_type'],
                    'source_url' =>
                    $parameters['source_url'],
                    'external_id' =>
                    $parameters['external_id'],
                    'assignment_url' =>
                    $parameters['assignment_url'],
                ]);

                echo 'Normalized legacy row: '
                    . $externalId
                    . PHP_EOL;
            }
        }

        $upsert->execute($parameters);

        echo 'Imported: '
            . $externalId
            . ' | '
            . $deadlineLocal->format(
                'Y-m-d H:i T'
            )
            . PHP_EOL;
    }
}
