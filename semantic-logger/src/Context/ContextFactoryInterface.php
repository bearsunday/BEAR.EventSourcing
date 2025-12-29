<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Context;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use Throwable;

/**
 * Factory for creating semantic logging contexts at different lifecycle stages.
 */
interface ContextFactoryInterface
{
    /**
     * Create context for request initiation.
     */
    public function createOpenContext(AbstractRequest $request): OpenContextInterface;

    /**
     * Create context for successful completion.
     */
    public function createCompleteContext(
        ResourceObject $ro,
        OpenContextInterface $openContext,
    ): CompleteContextInterface;

    /**
     * Create context for error/exception.
     */
    public function createErrorContext(
        Throwable $e,
        ?OpenContextInterface $openContext = null,
    ): ErrorContextInterface;
}
