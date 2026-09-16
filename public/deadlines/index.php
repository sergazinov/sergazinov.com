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

    <title data-i18n="deadlines.pageTitle">
        sergazinov · deadlines
    </title>

    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <div class="page-shell">

        <header class="site-header">

            <a
                class="site-logo"
                href="/"
                aria-label="Sergazinov home"
                data-i18n-aria-label="header.homeLabel">
                sergazinov
            </a>

            <div class="header-controls">

                <div
                    class="language-switcher"
                    role="group"
                    aria-label="Language"
                    data-i18n-aria-label="header.languageLabel">
                    <button
                        class="language-option is-active"
                        type="button"
                        data-language="en"
                        aria-pressed="true">
                        EN
                    </button>

                    <span class="language-divider">/</span>

                    <button
                        class="language-option"
                        type="button"
                        data-language="ru"
                        aria-pressed="false">
                        RU
                    </button>
                </div>

                <button
                    class="theme-toggle"
                    id="themeToggle"
                    type="button"
                    aria-label="Toggle theme"
                    aria-pressed="false"
                    data-i18n-aria-label="header.themeLabel">
                    <span class="theme-icon moon">☾</span>
                    <span class="theme-icon sun">☀</span>
                    <span class="theme-thumb"></span>
                </button>

            </div>

        </header>


        <main class="inner-main">

            <section class="inner-page">

                <p
                    class="hero-eyebrow"
                    data-i18n="deadlines.eyebrow">
                    COMPUTING AND DATA SCIENCE
                </p>

                <h1
                    class="inner-title"
                    data-i18n="deadlines.title">
                    Deadlines
                </h1>

                <p
                    class="inner-description"
                    data-i18n="deadlines.description">
                    Upcoming coursework and submission deadlines.
                </p>


                <div class="deadlines-toolbar">

                    <div class="current-clock">
                        <div class="current-clock-date" id="currentDate">—</div>

                        <div class="current-clock-time" id="currentTime">--:--:--</div>

                        <div
                            class="current-clock-zone"
                            id="currentTimezone">
                            LOCAL TIME
                        </div>
                    </div>

                    <button
                        class="schedule-timezone"
                        id="timezoneToggle"
                        type="button"
                        aria-label="Switch deadline timezone"
                        data-i18n-aria-label="deadlines.timezoneLabel">
                        ASTANA TIME · UTC+5
                    </button>

                </div>


                <section class="deadlines-list">

                    <?php if ($deadlines === []): ?>

                        <div class="deadlines-empty">

                            <p
                                class="deadlines-empty-title"
                                data-i18n="deadlines.emptyTitle">
                                No deadlines yet.
                            </p>

                            <p
                                class="deadlines-empty-text"
                                data-i18n="deadlines.emptyText">
                                Upcoming deadlines will appear here.
                            </p>

                        </div>

                    <?php else: ?>

                        <?php foreach ($deadlines as $deadline): ?>

                            <article class="deadline-card">

                                <div
                                    class="deadline-course"
                                    data-i18n-course="<?= htmlspecialchars(
                                                            $deadline['course_slug'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">
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


                                <div
                                    class="deadline-meta"
                                    data-deadline
                                    data-due-at="<?= htmlspecialchars(
                                                        $deadline['due_at_utc'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                    <div
                                        class="deadline-date-label"
                                        data-i18n="deadlines.deadlineLabel">
                                        Deadline
                                    </div>

                                    <time
                                        class="deadline-time"
                                        datetime="<?= htmlspecialchars(
                                                        $deadline['due_at_utc'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                        —
                                    </time>


                                    <div class="deadline-countdown">

                                        <span class="deadline-countdown-label">
                                            Due in
                                        </span>

                                        <strong class="deadline-countdown-value">
                                            —
                                        </strong>

                                    </div>

                                </div>

                                <?php if (!empty($deadline['assignment_url'])): ?>

                                    <a
                                        class="deadline-assignment-link"
                                        href="<?= htmlspecialchars(
                                                    $deadline['assignment_url'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        data-i18n="deadlines.openAssignment">
                                        Open assignment
                                    </a>

                                <?php endif; ?>

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

            <a
                class="admin-link"
                href="/admin/"
                data-i18n="nav.admin">
                Admin
            </a>

        </footer>

    </div>


    <script src="../script.js"></script>

</body>

</html>