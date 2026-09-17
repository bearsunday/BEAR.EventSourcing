SELECT
    event_id,
    uri,
    method,
    params_json,
    result_json,
    recorded_at,
    replayable
FROM event_store
ORDER BY id ASC
