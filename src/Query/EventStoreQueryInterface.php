<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface EventStoreQueryInterface
{
    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/list')]
    public function list(): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/list_since')]
    public function listSince(string $since): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/list_by_uri')]
    public function listByUri(string $pattern): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('event_store/list_by_aggregate_id')]
    public function listByAggregateId(string $uri, string $childrenPattern): array;
}
