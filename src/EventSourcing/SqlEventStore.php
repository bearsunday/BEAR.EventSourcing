<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use DateTimeInterface;
use PDO;
use UnexpectedValueException;

use function array_map;
use function get_debug_type;
use function is_scalar;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use function str_replace;

use const JSON_THROW_ON_ERROR;

/**
 * SQL-backed Event Store implementation
 */
class SqlEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly ExtendedPdoInterface $pdo,
        private readonly EventStoreQueryInterface $query,
    ) {
    }

    /** @inheritDoc */
    public function append(Event $event): void
    {
        $this->query->append(
            $event->id,
            $event->timestamp->format('Y-m-d H:i:s.u'),
            $event->uri,
            $event->method,
            json_encode($event->params, JSON_THROW_ON_ERROR),
            json_encode($event->result, JSON_THROW_ON_ERROR),
        );
    }

    /** @inheritDoc */
    public function getEvents(): EventsInterface
    {
        $rows = $this->query->getEvents();

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        $rows = $this->query->getEventsSince($since->format('Y-m-d H:i:s.u'));

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsByUri(string $pattern): EventsInterface
    {
        // Convert glob pattern to SQL LIKE pattern
        $likePattern = self::globToSqlLikePattern($pattern);

        $rows = $this->query->getEventsByUri($likePattern);

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        // Match aggregate URI and child resources like /orders/123/items/1.
        $uri = sprintf('/%s/%s', $aggregateType, $aggregateId);
        $childrenPattern = sprintf('%s/%%', self::escapeSqlLikeLiteral($uri));

        $rows = $this->query->getEventsByAggregateId($uri, $childrenPattern);

        return $this->hydrateEvents($rows);
    }

    /**
     * Create the event store table if it doesn't exist
     */
    public function createTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $this->createMysqlTable();

            return;
        }

        if ($driver === 'sqlite') {
            $this->createSqliteTable();

            return;
        }

        $driverName = is_scalar($driver) ? (string) $driver : get_debug_type($driver);

        throw new UnexpectedValueException(sprintf('Unsupported PDO driver: %s', $driverName));
    }

    private function createMysqlTable(): void
    {
        $this->query->createMysql();
    }

    private function createSqliteTable(): void
    {
        $this->query->createSqlite();
        $this->query->createSqliteIndexTimestamp();
        $this->query->createSqliteIndexUri();
        $this->query->createSqliteIndexMethod();
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
