<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use ArrayIterator;
use Countable;
use DateTimeImmutable;
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
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function strcasecmp;
use function strtoupper;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/** @implements IteratorAggregate<int, Event> */
final readonly class Events implements Countable, IteratorAggregate
{
    private const RECORDED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** @param list<Event> $events */
    public function __construct(private array $events = [])
    {
    }

    /** @param array<array-key, mixed> $semanticLog */
    public static function fromSemanticLog(array $semanticLog): self
    {
        $open = $semanticLog['open'] ?? [];
        if (! is_array($open)) {
            return new self();
        }

        $events = [];
        self::walk($open, $events);

        return new self($events);
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
                /** @var array{uri: string, method: string, params?: array<string, mixed>, result?: mixed, timestamp?: string} $item */
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

    /** @return list<Event> */
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

    /**
     * @param array<array-key, mixed> $opens
     * @param list<Event>             $events
     */
    private static function walk(array $opens, array &$events): void
    {
        foreach ($opens as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            self::appendEvent($entry, $events);

            $children = $entry['open'] ?? [];
            if (is_array($children)) {
                self::walk($children, $events);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $entry
     * @param list<Event>             $events
     */
    private static function appendEvent(array $entry, array &$events): void
    {
        $request = self::context($entry);
        $response = self::closeContext($entry);
        if ($request === null || $response === null || ! self::isSuccessful($response)) {
            return;
        }

        $method = self::recordedMethod($request);
        $uri = self::stringValue($request, 'uri');
        if ($method === null || $uri === null) {
            return;
        }

        $events[] = Event::create(
            uri: $uri,
            method: $method,
            params: self::params($request),
            result: $response['body'] ?? null,
            timestamp: self::timestamp($request),
        );
    }

    /**
     * @param array<array-key, mixed> $entry
     * @return array<array-key, mixed>|null
     */
    private static function context(array $entry): array|null
    {
        $context = $entry['context'] ?? null;

        return is_array($context) ? $context : null;
    }

    /**
     * @param array<array-key, mixed> $entry
     * @return array<array-key, mixed>|null
     */
    private static function closeContext(array $entry): array|null
    {
        $close = $entry['close'] ?? null;
        if (! is_array($close)) {
            return null;
        }

        $context = $close['context'] ?? null;

        return is_array($context) ? $context : null;
    }

    /** @param array<array-key, mixed> $context */
    private static function recordedMethod(array $context): string|null
    {
        $methodValue = self::stringValue($context, 'method');
        if ($methodValue === null) {
            return null;
        }

        $method = strtoupper($methodValue);

        return in_array($method, self::RECORDED_METHODS, true) ? $method : null;
    }

    /** @param array<array-key, mixed> $context */
    private static function isSuccessful(array $context): bool
    {
        $code = $context['code'] ?? null;

        return ! is_int($code) || $code < 400;
    }

    /** @param array<array-key, mixed> $context */
    private static function stringValue(array $context, string $key): string|null
    {
        $value = $context[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<array-key, mixed> $context
     * @return array<string, mixed>
     */
    private static function params(array $context): array
    {
        $params = $context['params'] ?? $context['query'] ?? [];
        if (! is_array($params)) {
            return [];
        }

        $result = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @param array<array-key, mixed> $context */
    private static function timestamp(array $context): DateTimeImmutable|null
    {
        $timestamp = self::stringValue($context, 'timestamp');
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($timestamp);
        } catch (Throwable) {
            return null;
        }
    }
}
