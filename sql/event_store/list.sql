SELECT id, timestamp, uri, method, params, result
FROM event_store
ORDER BY timestamp ASC
