<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Abstract base for open context capturing request intent.
 */
abstract class AbstractOpenContext
{
    /** @param array<string, mixed> $params */
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $params,
        public readonly AbstractContext $context,
    ) {
    }
}
