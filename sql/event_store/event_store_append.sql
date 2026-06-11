INSERT INTO event_store (
    uri,
    method,
    params_json,
    result_json,
    recorded_at
) VALUES (
    :uri,
    :method,
    :paramsJson,
    :resultJson,
    :timestamp
)
