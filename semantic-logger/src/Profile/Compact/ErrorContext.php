<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Context\AbstractErrorContext;
use Throwable;

/**
 * Compact implementation of error context.
 */
final class ErrorContext extends AbstractErrorContext
{
    public function __construct(
        Throwable $exception,
        ?string $id = null,
    ) {
        $generatedId = $id ?? sprintf('%08x', crc32($exception->getMessage() . $exception->getFile() . $exception->getLine()));
        parent::__construct(
            $exception,
            new ResourceErrorContext($exception, $generatedId),
            $generatedId,
        );
    }
}
