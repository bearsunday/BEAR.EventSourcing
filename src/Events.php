<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use ArrayIterator;
use Countable;
use DateTimeInterface;
use IteratorAggregate;
use JsonException;
use Throwable;
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
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @implements IteratorAggregate<int, Event>
 * @psalm-import-type EventInput from Types
 * @psalm-import-type EventList from Types
 * @psalm-suppress MixedAssignment JSON input is intentionally untyped external data.
 */
final readonly class Events implements Countable, IteratorAggregate
{
    /** @param EventList $events */
    public function __construct(private array $events = [])
    {
    }

    /** @throws JsonException */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            return new self();
        }

        $events = [];
        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['uri'], $item['method'])) {
                continue;
            }

            try {
                /** @var EventInput $item */
                $events[] = Event::fromArray($item);
            } catch (Throwable) {
                continue;
            }
        }

        return new self($events);
    }

    /** @throws JsonException */
    public function toJson(): string
    {
        return json_encode(
            array_map(static fn (Event $event): array => $event->toArray(), $this->events),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    public function add(Event $event): self
    {
        return new self([...$this->events, $event]);
    }

    public function filterByUri(string $pattern): self
    {
        return new self(array_values(array_filter(
            $this->events,
            static fn (Event $event): bool => fnmatch($pattern, $event->uri),
        )));
    }

    public function filterByMethod(string $method): self
    {
        return new self(array_values(array_filter(
            $this->events,
            static fn (Event $event): bool => strcasecmp($event->method, $method) === 0,
        )));
    }

    public function since(DateTimeInterface $since): self
    {
        return new self(array_values(array_filter(
            $this->events,
            static fn (Event $event): bool => $event->timestamp >= $since,
        )));
    }

    /** @param callable(Event): void $handler */
    public function replay(callable $handler): void
    {
        foreach ($this->events as $event) {
            $handler($event);
        }
    }

    /** @return EventList */
    public function all(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    /** @return Traversable<int, Event> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }
}
