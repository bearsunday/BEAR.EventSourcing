CREATE TABLE event_store (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT NOT NULL UNIQUE,
    uri TEXT NOT NULL,
    method TEXT NOT NULL,
    params_json TEXT NOT NULL,
    result_json TEXT NOT NULL,
    recorded_at TEXT NOT NULL
);

CREATE INDEX event_store_recorded_at_idx ON event_store (recorded_at);
