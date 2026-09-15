<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__) . '/config/database.php';

$courseSlug = 'discrete-mathematics';
$title = 'First Homework';
$description = null;

$sourceTimezone = 'Europe/Moscow';
$sourceDateTime = '2026-09-15 23:59:00';

$sourceTime = new DateTimeImmutable(
    $sourceDateTime,
    new DateTimeZone($sourceTimezone)
);

$utcTime = $sourceTime->setTimezone(
    new DateTimeZone('UTC')
);

$dueAtUtc = $utcTime->format('Y-m-d\TH:i:s\Z');


/*
 * Find the course.
 */

$courseStatement = $pdo->prepare(
    '
        SELECT id
        FROM courses
        WHERE slug = :slug
        LIMIT 1
    '
);

$courseStatement->execute([
    'slug' => $courseSlug,
]);

$courseId = $courseStatement->fetchColumn();

if ($courseId === false) {
    throw new RuntimeException(
        "Course not found: {$courseSlug}"
    );
}


/*
 * Avoid creating the same deadline twice.
 */

$existingStatement = $pdo->prepare(
    '
        SELECT id
        FROM deadlines
        WHERE course_id = :course_id
          AND title = :title
          AND due_at_utc = :due_at_utc
        LIMIT 1
    '
);

$existingStatement->execute([
    'course_id' => $courseId,
    'title' => $title,
    'due_at_utc' => $dueAtUtc,
]);

if ($existingStatement->fetchColumn() !== false) {
    echo "Deadline already exists." . PHP_EOL;
    exit(0);
}


/*
 * Insert the deadline.
 */

$insertStatement = $pdo->prepare(
    '
        INSERT INTO deadlines (
            course_id,
            title,
            description,
            due_at_utc,
            source_timezone
        )
        VALUES (
            :course_id,
            :title,
            :description,
            :due_at_utc,
            :source_timezone
        )
    '
);

$insertStatement->execute([
    'course_id' => $courseId,
    'title' => $title,
    'description' => $description,
    'due_at_utc' => $dueAtUtc,
    'source_timezone' => $sourceTimezone,
]);

echo "Deadline created successfully." . PHP_EOL;
echo "Source: {$sourceDateTime} {$sourceTimezone}" . PHP_EOL;
echo "UTC: {$dueAtUtc}" . PHP_EOL;