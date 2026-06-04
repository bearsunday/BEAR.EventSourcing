<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Sql\SqlQuery;
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
        private readonly ExtendedPdo $pdo,
        private readonly SqlQuery $sql = new SqlQuery(),
    ) {
    }

    /** @inheritDoc */
    public function append(Event $event): void
    {
        $this->pdo->perform($this->sql->get('event_store/append'), [
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
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($this->sql->get('event_store/get_events'));

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($this->sql->get('event_store/get_events_since'), [
            'since' => $since->format('Y-m-d H:i:s.u'),
        ]);

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsByUri(string $pattern): EventsInterface
    {
        // Convert glob pattern to SQL LIKE pattern
        $likePattern = self::globToSqlLikePattern($pattern);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($this->sql->get('event_store/get_events_by_uri'), ['pattern' => $likePattern]);

        return $this->hydrateEvents($rows);
    }

    /** @inheritDoc */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        // Match aggregate URI and child resources like /orders/123/items/1.
        $uri = sprintf('/%s/%s', $aggregateType, $aggregateId);
        $childrenPattern = sprintf('%s/%%', self::escapeSqlLikeLiteral($uri));

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->pdo->fetchAll($this->sql->get('event_store/get_events_by_aggregate_id'), [
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
        $this->pdo->exec($this->sql->get('event_store/create_mysql'));
    }

    private function createSqliteTable(): void
    {
        $this->pdo->exec($this->sql->get('event_store/create_sqlite'));
        $this->pdo->exec($this->sql->get('event_store/create_sqlite_index_timestamp'));
        $this->pdo->exec($this->sql->get('event_store/create_sqlite_index_uri'));
        $this->pdo->exec($this->sql->get('event_store/create_sqlite_index_method'));
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
