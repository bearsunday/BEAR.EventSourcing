<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use ArrayIterator;
use BEAR\SemanticLogger\ResourceRequestContext;
use BEAR\SemanticLogger\ResourceResponseContext;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Traversable;

final class Events implements EventsInterface
{
    /**
     * @param Event[] $events
     */
    public function __construct(
        private readonly array $events = [],
    ) {
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new InvalidArgumentException('JSON did not decode to an array');
        }

        return new self(
            array_map(
                static fn (array $item): Event => Event::fromArray($item),
                $data,
            )
        );
    }

    /**
     * @param array<string, mixed> $semanticLog Output of Koriym\SemanticLogger\LogJson::toArray()
     */
    public static function fromSemanticLog(array $semanticLog): self
    {
        $events = [];
        self::walk($semanticLog['open'] ?? [], $events);

        return new self($events);
    }

    public function toJson(): string
    {
        return json_encode(
            array_map(static fn (Event $e): array => $e->toArray(), $this->events),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
        );
    }

    public function add(Event $event): self
    {
        return new self([...$this->events, $event]);
    }

    public function filterByUri(string $pattern): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->events,
                    static fn (Event $e): bool => fnmatch($pattern, $e->uri),
                )
            )
        );
    }

    public function filterByMethod(string $method): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->events,
                    static fn (Event $e): bool => strcasecmp($e->method, $method) === 0,
                )
            )
        );
    }

    public function since(DateTimeInterface $since): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->events,
                    static fn (Event $e): bool => $e->timestamp >= $since,
                )
            )
        );
    }

    public function replay(callable $handler): void
    {
        foreach ($this->events as $event) {
            $handler($event);
        }
    }

    /**
     * @return Event[]
     */
    public function all(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }

    /**
     * @param array<int, array<string, mixed>> $opens
     * @param Event[]                          $events
     */
    private static function walk(array $opens, array &$events): void
    {
        foreach ($opens as $entry) {
            if (($entry['type'] ?? null) === ResourceRequestContext::TYPE) {
                $close = $entry['close'] ?? null;
                if (is_array($close) && ($close['type'] ?? null) === ResourceResponseContext::TYPE) {
                    $timestampString = $entry['context']['timestamp'] ?? '';
                    $timestamp = is_string($timestampString) && $timestampString !== ''
                        ? new DateTimeImmutable($timestampString)
                        : null;
                    $events[] = Event::create(
                        (string) ($entry['context']['uri'] ?? ''),
                        (string) ($entry['context']['method'] ?? ''),
                        (array) ($entry['context']['query'] ?? []),
                        $close['context']['body'] ?? null,
                        $timestamp,
                    );
                }
            }

            if (isset($entry['open']) && is_array($entry['open'])) {
                self::walk($entry['open'], $events);
            }
        }
    }
}
