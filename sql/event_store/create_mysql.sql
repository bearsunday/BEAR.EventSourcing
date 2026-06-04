CREATE TABLE IF NOT EXISTS event_store (
    id VARCHAR(36) PRIMARY KEY,
    timestamp DATETIME(6) NOT NULL,
    uri VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    params JSON,
    result JSON,
    INDEX idx_timestamp (timestamp),
    INDEX idx_uri (uri),
    INDEX idx_method (method)
)
