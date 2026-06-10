<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

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
     * @return list<array{
     *   uri: string,
     *   method: string,
     *   params_json: string,
     *   result_json: string,
     *   recorded_at: string
     * }>
     */
    #[DbQuery('event_store_list')]
    public function list(): array;
}
