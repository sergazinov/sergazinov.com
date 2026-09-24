<?php

declare(strict_types=1);

/**
 * Return upcoming deadlines and deadlines overdue by less than 48 hours.
 */
function getPublishedDeadlines(PDO $pdo): array
{
    $sql = '
        SELECT
            deadlines.id,
            deadlines.group_code,
            deadlines.title,
            deadlines.description,
            deadlines.due_at_utc,
            deadlines.source_timezone,
            deadlines.assignment_url,
            courses.slug AS course_slug,
            courses.name AS course_name,
            courses.short_name AS course_short_name
        FROM deadlines
        INNER JOIN courses
            ON courses.id = deadlines.course_id
        WHERE deadlines.is_published = 1
            AND courses.is_active = 1
            AND datetime(deadlines.due_at_utc) >= datetime(\'now\', \'-2 days\')
        ORDER BY deadlines.due_at_utc ASC
    ';

    $statement = $pdo->query($sql);

    return $statement->fetchAll();
}
