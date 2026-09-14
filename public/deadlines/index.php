<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__, 2) . '/config/database.php';

require_once dirname(__DIR__, 2) . '/src/deadlines.php';

$deadlines = getPublishedDeadlines($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Deadlines · Sergazinov</title>

    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <div class="page-shell">

        <header class="site-header">
            <a class="site-logo" href="/" aria-label="Sergazinov home">
                sergazinov
            </a>

            <button
                class="theme-toggle"
                id="themeToggle"
                type="button"
                aria-label="Toggle theme"
                aria-pressed="false"
            >
                <span class="theme-icon moon">☾</span>
                <span class="theme-icon sun">☀</span>
                <span class="theme-thumb"></span>
            </button>
        </header>

        <main class="inner-main">

            <section class="inner-page">

                <p class="hero-eyebrow">
                    COMPUTING AND DATA SCIENCE
                </p>

                <h1 class="inner-title">
                    Deadlines
                </h1>

                <p class="inner-description">
                    Upcoming coursework and submission deadlines.
                </p>

                <section class="deadlines-list">

                    <?php if ($deadlines === []): ?>

                        <div class="deadlines-empty">
                            <p class="deadlines-empty-title">
                                No deadlines yet.
                            </p>

                            <p class="deadlines-empty-text">
                                Upcoming deadlines will appear here.
                            </p>
                        </div>

                    <?php else: ?>

                        <?php foreach ($deadlines as $deadline): ?>

                            <article class="deadline-card">

                                <div class="deadline-course">
                                    <?= htmlspecialchars(
                                        $deadline['course_short_name']
                                        ?: $deadline['course_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                                <h2 class="deadline-title">
                                    <?= htmlspecialchars(
                                        $deadline['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h2>

                                <?php if (!empty($deadline['description'])): ?>

                                    <p class="deadline-description">
                                        <?= htmlspecialchars(
                                            $deadline['description'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                <?php endif; ?>

                                <time
                                    class="deadline-time"
                                    datetime="<?= htmlspecialchars(
                                        $deadline['due_at_utc'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $deadline['due_at_utc'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </time>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </section>

            </section>

        </main>

        <footer class="site-footer">

            <div class="footer-identity">
                <span>Artyom Sergazinov</span>
                <span class="footer-dot">·</span>
                <span>2026</span>
            </div>

            <a class="admin-link" href="/admin/">
                Admin
            </a>

        </footer>

    </div>

    <script src="../script.js"></script>

</body>

</html>