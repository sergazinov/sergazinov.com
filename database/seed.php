<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__) . '/config/database.php';

$courses = [
    [
        'slug' => 'cpp',
        'name' => 'C++ Programming',
        'short_name' => 'C++',
    ],
    [
        'slug' => 'linear-algebra',
        'name' => 'Linear Algebra',
        'short_name' => 'Linear Algebra',
    ],
    [
        'slug' => 'discrete-mathematics',
        'name' => 'Discrete Mathematics',
        'short_name' => 'Discrete Math',
    ],
    [
        'slug' => 'russian-history',
        'name' => 'Russian History',
        'short_name' => 'History',
    ],
    [
        'slug' => 'russian-statehood',
        'name' => 'Foundations of Russian Statehood',
        'short_name' => 'ОРГ',
    ],
    [
        'slug' => 'english',
        'name' => 'English',
        'short_name' => 'English',
    ],
];

$sql = '
    INSERT INTO courses (
        slug,
        name,
        short_name
    )
    VALUES (
        :slug,
        :name,
        :short_name
    )
    ON CONFLICT(slug) DO UPDATE SET
        name = excluded.name,
        short_name = excluded.short_name,
        updated_at = CURRENT_TIMESTAMP
';

$statement = $pdo->prepare($sql);

$pdo->beginTransaction();

try {
    foreach ($courses as $course) {
        $statement->execute($course);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

echo "Courses seeded successfully." . PHP_EOL;