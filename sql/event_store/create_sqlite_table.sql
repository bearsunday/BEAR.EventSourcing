CREATE TABLE IF NOT EXISTS event_store (
    id TEXT PRIMARY KEY,
    timestamp TEXT NOT NULL,
    uri TEXT NOT NULL,
    method TEXT NOT NULL,
    params TEXT,
    result TEXT
)
