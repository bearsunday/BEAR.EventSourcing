<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Invoker;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\EventExtractorInterface;
use Koriym\SemanticLogger\DevLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\Di\Named;
use Throwable;

/**
 * Development invoker with immediate file persistence.
 *
 * Extends semantic logging with:
 * - Immediate file persistence via DevLogger
 * - MCP server integration support
 * - Detailed profiling data (when using Verbose profile)
 *
 * @psalm-api
 */
final class DevSemanticInvoker implements InvokerInterface
{
    public function __construct(
        #[Named('original')]
        private readonly InvokerInterface $invoker,
        private readonly SemanticLoggerInterface $logger,
        private readonly DevLogger $devLogger,
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
        } finally {
            // Persist log to file immediately
            try {
                $logJson = $this->logger->flush();
                $this->devLogger->saveToFile($logJson);
            } catch (Throwable) {
                // Prevent persistence failure from affecting the result
            }
        }
    }
}
