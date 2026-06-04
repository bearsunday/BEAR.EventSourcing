<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\EventSourcing\Query\EventStoreCommandInterface;
use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use DateTimeInterface;
use PDO;
use UnexpectedValueException;

use function get_debug_type;
use function is_scalar;
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
        private readonly EventStoreQueryInterface $eventStore,
        private readonly EventStoreCommandInterface $eventStoreCmd,
    ) {
    }

    /** @inheritDoc */
    public function append(Event $event): void
    {
        $this->eventStoreCmd->append(
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
        return $this->eventStore->list()->toEvents();
    }

    /** @inheritDoc */
    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        return $this->eventStore->listSince($since->format('Y-m-d H:i:s.u'))->toEvents();
    }

    /** @inheritDoc */
    public function getEventsByUri(string $pattern): EventsInterface
    {
        // Convert glob pattern to SQL LIKE pattern
        $likePattern = self::globToSqlLikePattern($pattern);

        return $this->eventStore->listByUri($likePattern)->toEvents();
    }

    /** @inheritDoc */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        // Match aggregate URI and child resources like /orders/123/items/1.
        $uri = sprintf('/%s/%s', $aggregateType, $aggregateId);
        $childrenPattern = sprintf('%s/%%', self::escapeSqlLikeLiteral($uri));

        return $this->eventStore->listByAggregateId($uri, $childrenPattern)->toEvents();
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
        $this->eventStoreCmd->createMysqlTable();
    }

    private function createSqliteTable(): void
    {
        $this->eventStoreCmd->createSqliteTable();
        $this->eventStoreCmd->createSqliteTimestampIndex();
        $this->eventStoreCmd->createSqliteUriIndex();
        $this->eventStoreCmd->createSqliteMethodIndex();
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
