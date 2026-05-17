<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Aura\Sql\ExtendedPdo;
use DateTimeInterface;
use InvalidArgumentException;

final class EventStore implements EventStoreInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo,
        private readonly string $table = 'event_store',
    ) {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $this->table) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid table name: %s', $this->table));
        }
    }

    public function append(Event $event): void
    {
        $this->pdo->perform(
            sprintf(
                'INSERT INTO %s (id, timestamp, uri, method, params, result) VALUES (:id, :timestamp, :uri, :method, :params, :result)',
                $this->table,
            ),
            [
                'id' => $event->id,
                'timestamp' => $event->timestamp->format(Event::TIMESTAMP_FORMAT),
                'uri' => $event->uri,
                'method' => $event->method,
                'params' => json_encode($event->params, JSON_THROW_ON_ERROR),
                'result' => json_encode($event->result, JSON_THROW_ON_ERROR),
            ],
        );
    }

    public function getEvents(): EventsInterface
    {
        $rows = $this->pdo->fetchAll(sprintf('SELECT * FROM %s ORDER BY timestamp ASC', $this->table));

        return $this->hydrate($rows);
    }

    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        $rows = $this->pdo->fetchAll(
            sprintf('SELECT * FROM %s WHERE timestamp >= :since ORDER BY timestamp ASC', $this->table),
            ['since' => $since->format(Event::TIMESTAMP_FORMAT)],
        );

        return $this->hydrate($rows);
    }

    public function getEventsByUri(string $pattern): EventsInterface
    {
        $rows = $this->pdo->fetchAll(
            sprintf("SELECT * FROM %s WHERE uri LIKE :pattern ESCAPE '\\' ORDER BY timestamp ASC", $this->table),
            ['pattern' => $this->globToLike($pattern)],
        );

        return $this->hydrate($rows);
    }

    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        $pattern = '/' . $this->escapeLike($aggregateType) . '/' . $this->escapeLike($aggregateId) . '%';

        $rows = $this->pdo->fetchAll(
            sprintf("SELECT * FROM %s WHERE uri LIKE :pattern ESCAPE '\\' ORDER BY timestamp ASC", $this->table),
            ['pattern' => $pattern],
        );

        return $this->hydrate($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function hydrate(array $rows): EventsInterface
    {
        return new Events(
            array_map(
                static fn (array $row): Event => Event::fromArray(
                    [
                    'id' => $row['id'],
                    'timestamp' => $row['timestamp'],
                    'uri' => $row['uri'],
                    'method' => $row['method'],
                    'params' => json_decode($row['params'] ?? '[]', true, 512, JSON_THROW_ON_ERROR),
                    'result' => json_decode($row['result'] ?? 'null', true, 512, JSON_THROW_ON_ERROR),
                    ]
                ),
                $rows,
            )
        );
    }

    private function globToLike(string $pattern): string
    {
        $escaped = $this->escapeLike($pattern);

        return strtr($escaped, ['*' => '%', '?' => '_']);
    }

    private function escapeLike(string $input): string
    {
        return strtr($input, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
    }
}
