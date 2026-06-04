<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use BEAR\EventSourcing\Result\EventRecord;
use BEAR\EventSourcing\Result\EventRecords;
use Ray\MediaQuery\Annotation\DbQuery;

interface EventStoreQueryInterface
{
    #[DbQuery('event_store/list', factory: EventRecord::class)]
    public function list(): EventRecords;

    #[DbQuery('event_store/list_since', factory: EventRecord::class)]
    public function listSince(string $since): EventRecords;

    #[DbQuery('event_store/list_by_uri', factory: EventRecord::class)]
    public function listByUri(string $pattern): EventRecords;

    #[DbQuery('event_store/list_by_aggregate_id', factory: EventRecord::class)]
    public function listByAggregateId(string $uri, string $childrenPattern): EventRecords;
}
