<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use Override;
use Ray\MediaQuery\Result\AffectedRows;
use RuntimeException;

/**
 * Stands in for the Ray.MediaQuery-generated query so a store test can produce rows no
 * migration would, and a list() failure no dropped table reproduces.
 */
final class FakeEventStoreQuery implements EventStoreQueryInterface
{
    /** @param list<array<string, mixed>>|null $rows Null makes list() throw, as a driver would. */
    public function __construct(
        private readonly array|null $rows,
    ) {
    }

    #[Override]
    public function append(
        string $eventId,
        string $uri,
        string $method,
        string $paramsJson,
        string $resultJson,
        string $timestamp,
        int $replayable,
    ): AffectedRows {
        return new AffectedRows(1);
    }

    /**
     * @return array
     *
     * @psalm-suppress LessSpecificImplementedReturnType,InvalidReturnStatement The rows are
     *                 deliberately off-contract: a row the interface's type forbids is the test.
     */
    #[Override]
    public function list(): array
    {
        if ($this->rows === null) {
            throw new RuntimeException('driver failure');
        }

        return $this->rows;
    }
}
