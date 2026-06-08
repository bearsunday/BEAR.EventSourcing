<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use BEAR\EventSourcing\Result\StoredEvent;
use BEAR\EventSourcing\Result\StoredEvents;
use Ray\MediaQuery\Annotation\DbQuery;

interface EventStoreQueryInterface
{
    #[DbQuery('event_store/list', factory: StoredEvent::class)]
    public function list(): StoredEvents;

    #[DbQuery('event_store/list_since', factory: StoredEvent::class)]
    public function listSince(string $since): StoredEvents;

    #[DbQuery('event_store/list_by_uri', factory: StoredEvent::class)]
    public function listByUri(string $pattern): StoredEvents;

    #[DbQuery('event_store/list_by_aggregate_id', factory: StoredEvent::class)]
    public function listByAggregateId(string $uri, string $childrenPattern): StoredEvents;
}
