<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;

use function hash;
use function is_array;
use function json_encode;
use function ksort;
use function strtoupper;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/** @psalm-import-type EventParams from Types */
final readonly class Event
{
    private const ID_TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.u';

    public string $method;

    /**
     * Deterministic identity of the observed fact.
     *
     * Derived from uri, method, timestamp (UTC), and params — the same operation
     * observed at the same instant is the same event, so re-extraction and retried
     * appends stay idempotent. `result` is excluded: the same domain operation
     * produces the same event regardless of how its response body was recorded.
     */
    public string $id;

    /**
     * @param EventParams $params
     *
     * @throws JsonException When params cannot be represented as JSON.
     */
    public function __construct(
        public string $uri,
        string $method,
        public DateTimeImmutable $timestamp,
        public array $params = [],
        public mixed $result = null,
        string|null $id = null,
    ) {
        $this->method = strtoupper($method);
        $this->id = $id ?? self::deriveId($this->uri, $this->method, $timestamp, $params);
    }

    /** @param EventParams $params */
    private static function deriveId(string $uri, string $method, DateTimeImmutable $timestamp, array $params): string
    {
        $utcTimestamp = $timestamp->setTimezone(new DateTimeZone('UTC'))->format(self::ID_TIMESTAMP_FORMAT);
        $canonicalParams = json_encode(
            self::canonicalize($params),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return hash('sha256', $method . ' ' . $uri . ' ' . $utcTimestamp . ' ' . $canonicalParams);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private static function canonicalize(array $values): array
    {
        ksort($values);
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::canonicalize($value);
            }
        }

        return $values;
    }
}
