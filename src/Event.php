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
    private const string ID_TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.u';

    public string $method;

    /**
     * Deterministic identity of the observed fact.
     *
     * Derived from uri, method, timestamp (UTC), and params — the same operation
     * observed at the same instant is the same event, so re-extraction and retried
     * appends stay idempotent. `result` is excluded: the same domain operation
     * produces the same event regardless of how its response body was recorded.
     * `replayable` is excluded for the same reason: whether a filter withheld a
     * credential is a property of how the request was recorded, not of the operation.
     */
    public string $id;

    /**
     * @param EventParams $params
     * @param bool        $replayable Whether `params` is complete enough to re-execute this
     *                                operation faithfully. False when a `ParamsFilterInterface`
     *                                withheld domain input (a credential, typically) at record
     *                                time; the placeholder is then in `params`. "Complete"
     *                                excludes transport tokens (a CSRF token) that a replay
     *                                engine mints itself: a filtered `csrfToken` leaves the
     *                                flag true, and this package provides no minting seam — that
     *                                is the replay engine's job. Carried into every
     *                                `EventStoreInterface` so a replay engine reading the store,
     *                                not the transient log, can still tell.
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
        public bool $replayable = true,
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
