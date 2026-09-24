PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    short_name TEXT,

    is_active INTEGER NOT NULL DEFAULT 1
        CHECK (is_active IN (0, 1)),

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS deadlines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    course_id INTEGER NOT NULL,
    group_code TEXT,

    title TEXT NOT NULL,
    description TEXT,

    due_at_utc TEXT NOT NULL,
    source_timezone TEXT NOT NULL DEFAULT 'Europe/Moscow',
    source_type TEXT,
    source_url TEXT,
    external_id TEXT,
    assignment_url TEXT,
    last_seen_at TEXT,
    is_published INTEGER NOT NULL DEFAULT 1
        CHECK (is_published IN (0, 1)),

    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_deadlines_course_id
    ON deadlines(course_id);

CREATE INDEX IF NOT EXISTS idx_deadlines_due_at_utc
    ON deadlines(due_at_utc);

CREATE INDEX IF NOT EXISTS idx_deadlines_published_due
    ON deadlines(is_published, due_at_utc);

CREATE UNIQUE INDEX IF NOT EXISTS idx_deadlines_source_external
    ON deadlines(source_type, external_id);

CREATE INDEX IF NOT EXISTS idx_deadlines_group_published_due
    ON deadlines(group_code, is_published, due_at_utc);
