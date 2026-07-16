INSERT OR IGNORE INTO event_store (
    event_id,
    uri,
    method,
    params_json,
    result_json,
    recorded_at
) VALUES (
    :eventId,
    :uri,
    :method,
    :paramsJson,
    :resultJson,
    :timestamp
)
