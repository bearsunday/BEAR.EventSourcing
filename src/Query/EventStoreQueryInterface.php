<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use BEAR\EventSourcing\Result\EventRecord;
use BEAR\EventSourcing\Result\EventRecords;
use Ray\MediaQuery\Annotation\DbQuery;

interface EventStoreQueryInterface
{
    /** @return EventRecords<EventRecord> */
    #[DbQuery('event_store/list')]
    public function list(): EventRecords;

    /** @return EventRecords<EventRecord> */
    #[DbQuery('event_store/list_since')]
    public function listSince(string $since): EventRecords;

    /** @return EventRecords<EventRecord> */
    #[DbQuery('event_store/list_by_uri')]
    public function listByUri(string $pattern): EventRecords;

    /** @return EventRecords<EventRecord> */
    #[DbQuery('event_store/list_by_aggregate_id')]
    public function listByAggregateId(string $uri, string $childrenPattern): EventRecords;
}
