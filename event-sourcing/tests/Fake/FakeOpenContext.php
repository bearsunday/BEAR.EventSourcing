<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Fake;

use BEAR\SemanticLogger\Context\AbstractOpenContext;
use Koriym\SemanticLogger\AbstractContext;

final class FakeOpenContext extends AbstractOpenContext
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        string $method,
        string $uri,
        array $params = [],
    ) {
        parent::__construct(
            $method,
            $uri,
            $params,
            new FakeContext(),
        );
    }
}
