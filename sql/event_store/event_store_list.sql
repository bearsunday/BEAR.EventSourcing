SELECT
    event_id,
    uri,
    method,
    params_json,
    result_json,
    recorded_at
FROM event_store
ORDER BY id ASC
