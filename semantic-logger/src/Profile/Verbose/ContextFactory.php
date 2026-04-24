<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractErrorContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use Koriym\SemanticLogger\Profiler\OperationProfile;
use Koriym\SemanticLogger\Profiler\XdebugTrace;
use Koriym\SemanticLogger\Profiler\XHProfResult;
use Override;
use Throwable;

use function function_exists;
use function sprintf;
use function ucfirst;
use function xhprof_disable;

/**
 * Factory for Verbose profile contexts with full profiling.
 *
 * Verbose profile captures comprehensive profiling data:
 * - XHProf function-level profiling (if available)
 * - Xdebug trace data (if available)
 * - PHP backtrace and timing
 */
final class ContextFactory implements ContextFactoryInterface
{
    #[Override]
    public function createOpenContext(AbstractRequest $request): AbstractOpenContext
    {
        $ro = $request->resourceObject;
        $callSignature = sprintf(
            '%s::%s',
            $ro::class,
            'on' . ucfirst($request->method->value),
        );

        return new OpenContext(
            method: $request->method->value,
            uri: (string) $ro->uri,
            params: $request->query,
            callSignature: $callSignature,
        );
    }

    #[Override]
    public function createCompleteContext(
        ResourceObject $ro,
        AbstractOpenContext $openContext,
    ): AbstractCompleteContext {
        // Trigger rendering to capture view
        $view = (string) $ro;

        // Collect profiling data
        $profile = $this->collectProfile($openContext);

        return new CompleteContext(
            uri: (string) $ro->uri,
            code: $ro->code,
            headers: $ro->headers,
            body: $ro->body,
            view: $view,
            profile: $profile,
        );
    }

    #[Override]
    public function createErrorContext(
        Throwable $e,
        AbstractOpenContext|null $openContext = null,
    ): AbstractErrorContext {
        $profile = $openContext !== null
            ? $this->collectProfile($openContext)
            : null;

        return new ErrorContext($e, $profile);
    }

    private function collectProfile(AbstractOpenContext $openContext): OperationProfile
    {
        // Stop XHProf and collect data
        $xhprofProfile = [];
        // @codeCoverageIgnoreStart
        if (function_exists('xhprof_disable')) {
            /** @var array<string, mixed> $xhprofData */
            $xhprofData = xhprof_disable();
            $xhprofProfile[] = new XHProfResult($xhprofData);
        }

        // @codeCoverageIgnoreEnd

        // Collect Xdebug trace if available
        $xdebugTrace = [];
        // @codeCoverageIgnoreStart
        if (function_exists('xdebug_get_tracefile_name')) {
            /** @psalm-suppress UnnecessaryVarAnnotation @var string $traceFile */
            $traceFile = xdebug_get_tracefile_name();
            $xdebugTrace[] = new XdebugTrace(filePath: $traceFile);
        }

        // @codeCoverageIgnoreEnd

        // Stop PHP profiler and capture wall time
        $wallTime = 0.0;
        if ($openContext instanceof OpenContext) {
            $wallTime = $openContext->phpProfile->stop()->wallTime;
        }

        return new OperationProfile($wallTime, $xdebugTrace, $xhprofProfile);
    }
}
