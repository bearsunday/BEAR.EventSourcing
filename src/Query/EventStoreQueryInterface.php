<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use BEAR\EventSourcing\Types;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

/** @psalm-import-type EventStoreRow from Types */
interface EventStoreQueryInterface
{
    #[DbQuery('event_store_append')]
    public function append(
        string $uri,
        string $method,
        string $paramsJson,
        string $resultJson,
        string $timestamp,
    ): AffectedRows;

    /**
     * @return array
     * @psalm-return list<EventStoreRow>
     */
    #[DbQuery('event_store_list')]
    public function list(): array;
}
