<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use Koriym\SemanticLogger\AbstractContext;
use Throwable;

/**
 * Abstract base for error context capturing exception details.
 */
abstract class AbstractErrorContext
{
    public readonly string $id;

    public function __construct(
        public readonly Throwable $exception,
        public readonly AbstractContext $context,
        ?string $id = null,
    ) {
        $this->id = $id ?? sprintf('%08x', crc32($exception->getMessage() . $exception->getFile() . $exception->getLine()));
    }
}
