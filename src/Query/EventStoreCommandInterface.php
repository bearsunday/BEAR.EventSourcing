<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

interface EventStoreCommandInterface
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

    #[DbQuery('event_store/create_mysql_table')]
    public function createMysqlTable(): void;

    #[DbQuery('event_store/create_sqlite_table')]
    public function createSqliteTable(): void;

    #[DbQuery('event_store/create_sqlite_timestamp_index')]
    public function createSqliteTimestampIndex(): void;

    #[DbQuery('event_store/create_sqlite_uri_index')]
    public function createSqliteUriIndex(): void;

    #[DbQuery('event_store/create_sqlite_method_index')]
    public function createSqliteMethodIndex(): void;
}
