<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use Koriym\SemanticLogger\AbstractContext;
use Throwable;

/**
 * Captures the details of an error during resource request.
 */
interface ErrorContextInterface
{
    /**
     * Get the error/exception ID.
     */
    public function getId(): string;

    /**
     * Get the exception.
     */
    public function getException(): Throwable;

    /**
     * Get the underlying context for SemanticLogger.
     */
    public function getContext(): AbstractContext;
}
