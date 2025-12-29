<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\SemanticLogger\Context\ErrorContextInterface;
use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\Profile;
use Throwable;

/**
 * Verbose implementation of ErrorContextInterface with profiling.
 */
final class ErrorContext implements ErrorContextInterface
{
    private readonly ResourceErrorContext $context;

    public function __construct(
        private readonly Throwable $exception,
        ?Profile $profile = null,
        ?string $id = null,
    ) {
        $this->context = new ResourceErrorContext($exception, $profile, $id);
    }

    public function getId(): string
    {
        return $this->context->id;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function getContext(): AbstractContext
    {
        return $this->context;
    }
}
