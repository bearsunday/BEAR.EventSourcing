SELECT *
FROM event_store
WHERE uri LIKE :pattern ESCAPE '!'
ORDER BY timestamp ASC
