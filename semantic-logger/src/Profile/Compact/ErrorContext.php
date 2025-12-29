<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Context\ErrorContextInterface;
use Koriym\SemanticLogger\AbstractContext;
use Throwable;

/**
 * Compact implementation of ErrorContextInterface.
 */
final class ErrorContext implements ErrorContextInterface
{
    private readonly ResourceErrorContext $context;

    public function __construct(
        private readonly Throwable $exception,
        ?string $id = null,
    ) {
        $this->context = new ResourceErrorContext($exception, $id);
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
