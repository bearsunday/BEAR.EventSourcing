<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdo;
use DateTimeInterface;

/**
 * Database-backed Event Store implementation
 */
class EventStore implements EventStoreInterface
{
    private const TABLE_NAME = 'event_store';

    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {
    }

    /**
     * @inheritDoc
     */
    public function append(Event $event): void
    {
        $sql = sprintf(
            'INSERT INTO %s (id, timestamp, uri, method, params, result) VALUES (:id, :timestamp, :uri, :method, :params, :result)',
            self::TABLE_NAME
        );

        $this->pdo->perform($sql, [
            'id' => $event->id,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s.u'),
            'uri' => $event->uri,
            'method' => $event->method,
            'params' => json_encode($event->params),
            'result' => json_encode($event->result),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEvents(): EventsInterface
    {
        $sql = sprintf(
            'SELECT * FROM %s ORDER BY timestamp ASC',
            self::TABLE_NAME
        );

        $rows = $this->pdo->fetchAll($sql);

        return $this->hydrateEvents($rows);
    }

    /**
     * @inheritDoc
     */
    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE timestamp >= :since ORDER BY timestamp ASC',
            self::TABLE_NAME
        );

        $rows = $this->pdo->fetchAll($sql, [
            'since' => $since->format('Y-m-d H:i:s.u'),
        ]);

        return $this->hydrateEvents($rows);
    }

    /**
     * @inheritDoc
     */
    public function getEventsByUri(string $pattern): EventsInterface
    {
        // Convert glob pattern to SQL LIKE pattern
        $likePattern = str_replace(['*', '?'], ['%', '_'], $pattern);

        $sql = sprintf(
            'SELECT * FROM %s WHERE uri LIKE :pattern ORDER BY timestamp ASC',
            self::TABLE_NAME
        );

        $rows = $this->pdo->fetchAll($sql, [
            'pattern' => $likePattern,
        ]);

        return $this->hydrateEvents($rows);
    }

    /**
     * @inheritDoc
     */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        // Match URIs like /orders/123, /customers/456
        $pattern = sprintf('/%s/%s%%', $aggregateType, $aggregateId);

        $sql = sprintf(
            'SELECT * FROM %s WHERE uri LIKE :pattern ORDER BY timestamp ASC',
            self::TABLE_NAME
        );

        $rows = $this->pdo->fetchAll($sql, [
            'pattern' => $pattern,
        ]);

        return $this->hydrateEvents($rows);
    }

    /**
     * Create the event store table if it doesn't exist
     */
    public function createTable(): void
    {
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                id VARCHAR(36) PRIMARY KEY,
                timestamp DATETIME(6) NOT NULL,
                uri VARCHAR(255) NOT NULL,
                method VARCHAR(10) NOT NULL,
                params JSON,
                result JSON,
                INDEX idx_timestamp (timestamp),
                INDEX idx_uri (uri),
                INDEX idx_method (method)
            )',
            self::TABLE_NAME
        );

        $this->pdo->exec($sql);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function hydrateEvents(array $rows): EventsInterface
    {
        $events = array_map(function (array $row): Event {
            return Event::fromArray([
                'id' => $row['id'],
                'timestamp' => $row['timestamp'],
                'uri' => $row['uri'],
                'method' => $row['method'],
                'params' => json_decode($row['params'] ?? '[]', true),
                'result' => json_decode($row['result'] ?? 'null', true),
            ]);
        }, $rows);

        return new Events($events);
    }
}
