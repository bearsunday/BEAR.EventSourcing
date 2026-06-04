<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Sql;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class SqlQueryTest extends TestCase
{
    public function testGetBundledSql(): void
    {
        $sql = (new SqlQuery())->get('event_store/append');

        $this->assertStringContainsString('INSERT INTO event_store', $sql);
        $this->assertStringContainsString('VALUES (:id, :timestamp, :uri, :method, :params, :result)', $sql);
    }

    public function testMissingSqlThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQL file not found:');

        (new SqlQuery())->get('event_store/missing');
    }
}
