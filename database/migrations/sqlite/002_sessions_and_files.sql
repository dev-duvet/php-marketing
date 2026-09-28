-- Database-backed sessions (work across serverless instances) and stored uploads.

CREATE TABLE sessions (
    id TEXT PRIMARY KEY,
    data TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX idx_sessions_activity ON sessions (last_activity);

CREATE TABLE stored_files (
    name TEXT PRIMARY KEY,
    mime TEXT NOT NULL,
    size INTEGER NOT NULL,
    data TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);
