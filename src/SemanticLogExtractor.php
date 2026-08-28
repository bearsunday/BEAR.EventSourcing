<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeImmutable;
use Koriym\SemanticLogger\LogJson;
use Throwable;

use function is_array;
use function is_int;
use function is_string;
use function strlen;
use function strspn;

/**
 * @psalm-import-type EventList from Types
 * @psalm-import-type EventParams from Types
 * @psalm-import-type SemanticContext from Types
 * @psalm-import-type SemanticEntry from Types
 * @psalm-suppress MixedAssignment LogJson public context data is schema-defined but array-shaped.
 */
final readonly class SemanticLogExtractor implements SemanticLogExtractorInterface
{
    /**
     * The Semantic Logger entry type extracted as events.
     *
     * Extraction is type-gated: only entries observed as resource requests become
     * events, so unrelated open/close pairs that happen to carry `method` and
     * `uri` fields are never misread as state changes.
     */
    public const RESOURCE_REQUEST_TYPE = 'resource_request';

    private RecordedMethods $recordedMethods;

    public function __construct(RecordedMethods|null $recordedMethods = null)
    {
        $this->recordedMethods = $recordedMethods ?? new RecordedMethods();
    }

    public function extract(LogJson $semanticLog): EventsInterface
    {
        $events = [];
        $this->walk($semanticLog->toArray()['open'], $events);

        return new Events($events);
    }

    /**
     * @param array<array-key, mixed> $opens
     * @param EventList   $events
     */
    private function walk(array $opens, array &$events): void
    {
        foreach ($opens as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $this->appendEvent($entry, $events);

            $children = $entry['open'] ?? [];
            if (is_array($children)) {
                $this->walk($children, $events);
            }
        }
    }

    /**
     * @param SemanticEntry $entry
     * @param EventList     $events
     */
    private function appendEvent(array $entry, array &$events): void
    {
        if (($entry['type'] ?? null) !== self::RESOURCE_REQUEST_TYPE) {
            return;
        }

        $request = self::context($entry);
        $response = self::closeContext($entry);
        if ($request === null || $response === null || ! self::isSuccessful($response)) {
            return;
        }

        $method = $this->recordedMethod($request);
        $uri = self::stringValue($request, 'uri');
        $timestamp = self::timestamp($request);
        if ($method === null || $uri === null || $timestamp === null) {
            return;
        }

        $events[] = new Event(
            uri: $uri,
            method: $method,
            timestamp: $timestamp,
            params: self::params($request),
            result: $response['body'] ?? null,
        );
    }

    /**
     * @param SemanticEntry $entry
     * @return SemanticContext|null
     */
    private static function context(array $entry): array|null
    {
        $context = $entry['context'] ?? null;

        return is_array($context) ? $context : null;
    }

    /**
     * @param SemanticEntry $entry
     * @return SemanticContext|null
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

    /** @param SemanticContext $context */
    private function recordedMethod(array $context): string|null
    {
        $methodValue = self::stringValue($context, 'method');

        return $methodValue === null ? null : $this->recordedMethods->normalize($methodValue);
    }

    /** @param SemanticContext $context */
    private static function isSuccessful(array $context): bool
    {
        $code = $context['code'] ?? null;
        if ($code === null) {
            return true; // No code means no failure signal.
        }

        if (is_int($code)) {
            return $code < 400;
        }

        // Accept a digit-only string code (e.g. "404"); "-1" and "5xx" are not all
        // digits, so they stay uninterpretable.
        if (is_string($code) && $code !== '' && strspn($code, '0123456789') === strlen($code)) {
            return (int) $code < 400;
        }

        // An uninterpretable code (bool, float, non-numeric string) is not minted
        // into the source of truth: a state change we cannot confirm succeeded.
        return false;
    }

    /** @param SemanticContext $context */
    private static function stringValue(array $context, string $key): string|null
    {
        $value = $context[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param SemanticContext $context
     * @return EventParams
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

    /**
     * The observed request timestamp, or null when the observation lacks one.
     *
     * No fallback clock: an event without an observed timestamp is not extracted,
     * so extracting the same log twice always yields the same events.
     *
     * @param SemanticContext $context
     */
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
