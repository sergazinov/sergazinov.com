<?php

declare(strict_types=1);

/**
 * Return all published deadlines ordered from nearest to latest.
 */
function getPublishedDeadlines(PDO $pdo): array
{
    $sql = '
        SELECT
            deadlines.id,
            deadlines.title,
            deadlines.description,
            deadlines.due_at_utc,
            deadlines.source_timezone,
            courses.slug AS course_slug,
            courses.name AS course_name,
            courses.short_name AS course_short_name
        FROM deadlines
        INNER JOIN courses
            ON courses.id = deadlines.course_id
        WHERE deadlines.is_published = 1
          AND courses.is_active = 1
        ORDER BY deadlines.due_at_utc ASC
    ';

    $statement = $pdo->query($sql);

    return $statement->fetchAll();
}