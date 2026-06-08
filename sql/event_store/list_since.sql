SELECT id, timestamp, uri, method, params, result
FROM event_store
WHERE timestamp >= :since
ORDER BY timestamp ASC
