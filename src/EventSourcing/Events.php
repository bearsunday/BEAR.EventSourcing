<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use ArrayIterator;
use DateTimeInterface;
use InvalidArgumentException;
use Traversable;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function fnmatch;
use function is_array;
use function json_decode;
use function json_encode;
use function strcasecmp;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Events collection implementation
 */
final readonly class Events implements EventsInterface
{
    /** @param Event[] $events */
    public function __construct(private array $events = [])
    {
    }

    /** @inheritDoc */
    public static function fromJson(string $json): EventsInterface
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON data');
        }

        $events = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid JSON data');
            }

            $events[] = Event::fromArray($item);
        }

        return new self($events);
    }

    /** @inheritDoc */
    public function toJson(): string
    {
        return json_encode(
            array_map(static fn (Event $e): array => $e->toArray(), $this->events),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /** @inheritDoc */
    public function add(Event $event): EventsInterface
    {
        $events = $this->events;
        $events[] = $event;

        return new self($events);
    }

    /** @inheritDoc */
    public function filterByUri(string $pattern): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            static fn (Event $e): bool => fnmatch($pattern, $e->uri),
        );

        return new self(array_values($filtered));
    }

    /** @inheritDoc */
    public function filterByMethod(string $method): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            static fn (Event $e): bool => strcasecmp($e->method, $method) === 0,
        );

        return new self(array_values($filtered));
    }

    /** @inheritDoc */
    public function since(DateTimeInterface $since): EventsInterface
    {
        $filtered = array_filter(
            $this->events,
            static fn (Event $e): bool => $e->timestamp >= $since,
        );

        return new self(array_values($filtered));
    }

    /** @inheritDoc */
    public function replay(callable $handler): void
    {
        foreach ($this->events as $event) {
            $handler($event);
        }
    }

    /** @inheritDoc */
    public function all(): array
    {
        return $this->events;
    }

    /** @inheritDoc */
    public function count(): int
    {
        return count($this->events);
    }

    /** @inheritDoc */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }
}
