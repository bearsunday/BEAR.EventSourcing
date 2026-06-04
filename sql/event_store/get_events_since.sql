SELECT *
FROM event_store
WHERE timestamp >= :since
ORDER BY timestamp ASC
