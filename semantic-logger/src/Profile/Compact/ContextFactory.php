<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractErrorContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use Override;
use Throwable;

/**
 * Factory for Compact profile contexts.
 *
 * Compact profile captures minimal information for production use:
 * - No profiling overhead
 * - Essential request/response data only
 */
final class ContextFactory implements ContextFactoryInterface
{
    #[Override]
    public function createOpenContext(AbstractRequest $request): AbstractOpenContext
    {
        $ro = $request->resourceObject;

        return new OpenContext(
            method: $request->method->value,
            uri: (string) $ro->uri,
            params: $request->query,
        );
    }

    #[Override]
    public function createCompleteContext(
        ResourceObject $ro,
        AbstractOpenContext $openContext,
    ): AbstractCompleteContext {
        // Trigger rendering to capture view
        $view = (string) $ro;

        return new CompleteContext(
            uri: (string) $ro->uri,
            code: $ro->code,
            headers: $ro->headers,
            body: $ro->body,
            view: $view,
        );
    }

    #[Override]
    public function createErrorContext(
        Throwable $e,
        AbstractOpenContext|null $openContext = null,
    ): AbstractErrorContext {
        return new ErrorContext($e);
    }
}
