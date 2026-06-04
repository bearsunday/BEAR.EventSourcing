SELECT id, timestamp, uri, method, params, result
FROM event_store
WHERE uri LIKE :pattern ESCAPE '!'
ORDER BY timestamp ASC
