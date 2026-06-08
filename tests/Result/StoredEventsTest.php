<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Result;

use Aura\Sql\ExtendedPdo;
use InvalidArgumentException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Result\PostQueryContext;

final class StoredEventsTest extends TestCase
{
    public function testFromContextRejectsUnexpectedRows(): void
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $statement = $pdo->prepare('SELECT 1');
        $this->assertInstanceOf(PDOStatement::class, $statement);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected StoredEvent at row 0, got array');

        StoredEvents::fromContext(new PostQueryContext($statement, $pdo, [], [['id' => 'event-1']]));
    }
}
