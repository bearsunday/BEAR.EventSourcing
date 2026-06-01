<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdo;
use DateTimeInterface;
use PDO;
use UnexpectedValueException;

use function array_map;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use function str_replace;

use const JSON_THROW_ON_ERROR;

/**
 * Database-backed Event Store implementation
 */
class EventStore implements EventStoreInterface
{
    private const TABLE_NAME = 'event_store';

    public function __construct(
        private readonly ExtendedPdo $pdo,
    ) {
    }

    /** @inheritDoc */
    public function append(Event $event): void
    {
        $sql = sprintf(
            'INSERT INTO %s (id, timestamp, uri, method, params, result) VALUES (:id, :timestamp, :uri, :method, :params, :result)',
            self::TABLE_NAME,
        );

        $this->pdo->perform($sql, [
            'id' => $event->id,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s.u'),
            'uri' => $event->uri,
            'method' => $event->method,
            'params' => json_encode($event->params, JSON_THROW_ON_ERROR),
            'result' => json_encode($event->result, JSON_THROW_ON_ERROR),
        ]);
    }

    /** @inheritDoc */
    public function getEvents(): EventsInterface
    {
        $sql = sprintf(
            'SELECT * FROM %s ORDER BY timestamp ASC',
            self::TABLE_NAME,
        );

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($sql);

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE timestamp >= :since ORDER BY timestamp ASC',
            self::TABLE_NAME,
        );

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($sql, [
            'since' => $since->format('Y-m-d H:i:s.u'),
        ]);

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsByUri(string $pattern): EventsInterface
    {
        // Convert glob pattern to SQL LIKE pattern
        $likePattern = self::globToSqlLikePattern($pattern);

        $sql = sprintf(
            "SELECT * FROM %s WHERE uri LIKE :pattern ESCAPE '!' ORDER BY timestamp ASC",
            self::TABLE_NAME,
        );

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($sql, ['pattern' => $likePattern]);

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        // Match aggregate URI and child resources like /orders/123/items/1.
        $uri = sprintf('/%s/%s', $aggregateType, $aggregateId);
        $childrenPattern = sprintf('%s/%%', self::escapeSqlLikeLiteral($uri));

        $sql = sprintf(
            "SELECT * FROM %s WHERE uri = :uri OR uri LIKE :childrenPattern ESCAPE '!' ORDER BY timestamp ASC",
            self::TABLE_NAME,
        );

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($sql, [
            'uri' => $uri,
            'childrenPattern' => $childrenPattern,
        ]);

        return $this->hydrateEvents($rows);
    }

    /**
     * Create the event store table if it doesn't exist
     */
    public function createTable(): void
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $this->createSqliteTable();

            return;
        }

        $this->createMysqlTable();
    }

    private function createMysqlTable(): void
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
            self::TABLE_NAME,
        );

        $this->pdo->exec($sql);
    }

    private function createSqliteTable(): void
    {
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                id TEXT PRIMARY KEY,
                timestamp TEXT NOT NULL,
                uri TEXT NOT NULL,
                method TEXT NOT NULL,
                params TEXT,
                result TEXT
            )',
            self::TABLE_NAME,
        );

        $this->pdo->exec($sql);
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_timestamp ON %s (timestamp)', self::TABLE_NAME));
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_uri ON %s (uri)', self::TABLE_NAME));
        $this->pdo->exec(sprintf('CREATE INDEX IF NOT EXISTS idx_method ON %s (method)', self::TABLE_NAME));
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function hydrateEvents(array $rows): EventsInterface
    {
        $events = array_map(static function (array $row): Event {
            $paramsJson = $row['params'] ?? '[]';
            $resultJson = $row['result'] ?? 'null';

            if (! is_string($paramsJson) || ! is_string($resultJson)) {
                throw new UnexpectedValueException('Invalid event store row');
            }

            return Event::fromArray([
                'id' => $row['id'],
                'timestamp' => $row['timestamp'],
                'uri' => $row['uri'],
                'method' => $row['method'],
                'params' => json_decode($paramsJson, true, 512, JSON_THROW_ON_ERROR),
                'result' => json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR),
            ]);
        }, $rows);

        return new Events($events);
    }

    private static function globToSqlLikePattern(string $pattern): string
    {
        return str_replace(
            ['!', '%', '_', '*', '?'],
            ['!!', '!%', '!_', '%', '_'],
            $pattern,
        );
    }

    private static function escapeSqlLikeLiteral(string $value): string
    {
        return str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $value,
        );
    }
}
