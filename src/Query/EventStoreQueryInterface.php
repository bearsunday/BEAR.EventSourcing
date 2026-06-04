<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

interface EventStoreQueryInterface
{
    #[DbQuery('event_store/append')]
    public function append(
        string $id,
        string $timestamp,
        string $uri,
        string $method,
        string $params,
        string $result,
    ): AffectedRows;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/get_events')]
    public function getEvents(): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/get_events_since')]
    public function getEventsSince(string $since): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/get_events_by_uri')]
    public function getEventsByUri(string $pattern): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/get_events_by_aggregate_id')]
    public function getEventsByAggregateId(string $uri, string $childrenPattern): array;

    #[DbQuery('event_store/create_mysql')]
    public function createMysql(): void;

    #[DbQuery('event_store/create_sqlite')]
    public function createSqlite(): void;

    #[DbQuery('event_store/create_sqlite_index_timestamp')]
    public function createSqliteIndexTimestamp(): void;

    #[DbQuery('event_store/create_sqlite_index_uri')]
    public function createSqliteIndexUri(): void;

    #[DbQuery('event_store/create_sqlite_index_method')]
    public function createSqliteIndexMethod(): void;
}
