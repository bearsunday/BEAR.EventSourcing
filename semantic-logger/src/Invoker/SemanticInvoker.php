<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Invoker;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\EventExtractorInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\Di\Named;
use Throwable;

/**
 * Invoker that wraps resource operations with semantic logging.
 *
 * Implements the open/close lifecycle pattern:
 * 1. open() - Log request initiation
 * 2. invoke() - Execute the resource operation
 * 3. close() - Log completion or error
 * 4. extract() - Optionally extract events for Event Sourcing
 *
 * @psalm-api
 */
final class SemanticInvoker implements InvokerInterface
{
    public function __construct(
        #[Named('original')]
        private readonly InvokerInterface $invoker,
        private readonly SemanticLoggerInterface $logger,
        private readonly ContextFactoryInterface $contextFactory,
        private readonly EventExtractorInterface|null $extractor = null,
    ) {
    }

    #[Override]
    public function invoke(AbstractRequest $request): ResourceObject
    {
        $openContext = $this->contextFactory->createOpenContext($request);
        $openId = $this->logger->open($openContext->context);

        try {
            $ro = $this->invoker->invoke($request);
            $completeContext = $this->contextFactory->createCompleteContext($ro, $openContext);
            $this->logger->close($completeContext->context, $openId);

            // Real-time event extraction (if configured)
            $this->extractor?->extract($openContext, $completeContext);

            return $ro;
        } catch (Throwable $e) {
            $errorContext = $this->contextFactory->createErrorContext($e, $openContext);

            try {
                $this->logger->close($errorContext->context, $openId);
            } catch (Throwable) {
                // Prevent logging failure from masking original exception
            }

            throw $e;
        }
    }
}
