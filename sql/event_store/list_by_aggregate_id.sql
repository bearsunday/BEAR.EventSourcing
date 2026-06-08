SELECT id, timestamp, uri, method, params, result
FROM event_store
WHERE uri = :uri OR uri LIKE :childrenPattern ESCAPE '!'
ORDER BY timestamp ASC
