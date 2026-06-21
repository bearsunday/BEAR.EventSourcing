<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Store;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\Exception\EventStoreException;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\EventsInterface;
use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use BEAR\EventSourcing\Types;
use DateTimeImmutable;
use JsonException;
use Throwable;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-import-type EventParams from Types
 * @psalm-import-type EventStoreRow from Types
 */
final readonly class MediaQueryEventStore implements EventStoreInterface
{
    private const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

    public function __construct(
        private EventStoreQueryInterface $query,
    ) {
    }

    public function append(Event $event): void
    {
        $this->query->append(
            uri: $event->uri,
            method: $event->method,
            paramsJson: self::encode($event->params),
            resultJson: self::encode($event->result),
            timestamp: $event->timestamp->format(self::TIMESTAMP_FORMAT),
        );
    }

    public function appendAll(EventsInterface $events): void
    {
        foreach ($events as $event) {
            $this->append($event);
        }
    }

    public function all(): EventsInterface
    {
        $events = [];
        foreach ($this->query->list() as $row) {
            $events[] = self::event($row);
        }

        return new Events($events);
    }

    private static function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new EventStoreException('Failed to encode event data as JSON.', 0, $e);
        }
    }

    /** @param EventStoreRow $row */
    private static function event(array $row): Event
    {
        try {
            return new Event(
                uri: $row['uri'],
                method: $row['method'],
                timestamp: new DateTimeImmutable($row['recorded_at']),
                params: self::params($row['params_json']),
                result: self::decode($row['result_json']),
            );
        } catch (Throwable $e) {
            throw new EventStoreException('Failed to restore event from stored row.', 0, $e);
        }
    }

    /**
     * @return EventParams
     * @psalm-suppress MixedAssignment JSON object values are event parameters.
     */
    private static function params(string $json): array
    {
        $params = self::decode($json);
        if (! is_array($params)) {
            throw new EventStoreException('Stored event params must decode to an array.');
        }

        $result = [];
        foreach ($params as $key => $value) {
            if (! is_string($key)) {
                throw new EventStoreException('Stored event params must be keyed by string.');
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function decode(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new EventStoreException('Failed to decode stored event JSON.', 0, $e);
        }
    }
}
