<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\CompleteContextInterface;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\Context\ErrorContextInterface;
use BEAR\SemanticLogger\Context\OpenContextInterface;
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
    public function createOpenContext(AbstractRequest $request): OpenContextInterface
    {
        $ro = $request->resourceObject;

        return new OpenContext(
            method: $request->method,
            uri: (string) $ro->uri,
            params: $request->query,
        );
    }

    public function createCompleteContext(
        ResourceObject $ro,
        OpenContextInterface $openContext,
    ): CompleteContextInterface {
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

    public function createErrorContext(
        Throwable $e,
        ?OpenContextInterface $openContext = null,
    ): ErrorContextInterface {
        return new ErrorContext($e);
    }
}
