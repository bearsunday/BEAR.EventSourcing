<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\SemanticLogger\Context\AbstractErrorContext;
use Koriym\SemanticLogger\Profiler\Profile;
use Throwable;

use function crc32;
use function sprintf;

/**
 * Verbose implementation of error context with profiling.
 */
final class ErrorContext extends AbstractErrorContext
{
    public function __construct(
        Throwable $exception,
        public readonly Profile|null $profile = null,
        string|null $id = null,
    ) {
        $generatedId = $id ?? sprintf('%08x', crc32($exception->getMessage() . $exception->getFile() . $exception->getLine()));

        parent::__construct(
            $exception,
            new ResourceErrorContext($exception, $profile, $generatedId),
            $generatedId,
        );
    }
}
