<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractErrorContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use Koriym\SemanticLogger\Profiler\Profile;
use Koriym\SemanticLogger\Profiler\XdebugTrace;
use Koriym\SemanticLogger\Profiler\XHProfResult;
use Throwable;

use function function_exists;
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
    public function createOpenContext(AbstractRequest $request): AbstractOpenContext
    {
        $ro = $request->resourceObject;
        $callSignature = sprintf(
            '%s::%s',
            $ro::class,
            'on' . ucfirst(strtolower($request->method)),
        );

        return new OpenContext(
            method: $request->method,
            uri: (string) $ro->uri,
            params: $request->query,
            callSignature: $callSignature,
        );
    }

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

    public function createErrorContext(
        Throwable $e,
        ?AbstractOpenContext $openContext = null,
    ): AbstractErrorContext {
        $profile = $openContext !== null
            ? $this->collectProfile($openContext)
            : null;

        return new ErrorContext($e, $profile);
    }

    private function collectProfile(AbstractOpenContext $openContext): Profile
    {
        // Stop XHProf and collect data
        $xhprof = null;
        if (function_exists('xhprof_disable')) {
            $xhprofData = xhprof_disable();
            if ($xhprofData !== null) {
                $xhprof = new XHProfResult($xhprofData);
            }
        }

        // Collect Xdebug trace if available
        $xdebug = null;
        if (function_exists('xdebug_get_tracefile_name')) {
            $traceFile = xdebug_get_tracefile_name();
            if ($traceFile !== null && $traceFile !== false) {
                $xdebug = new XdebugTrace($traceFile);
            }
        }

        // Stop PHP profiler
        $phpProfile = null;
        if ($openContext instanceof OpenContext) {
            $phpProfile = $openContext->phpProfile;
            $phpProfile->stop();
        }

        return new Profile($xhprof, $xdebug, $phpProfile);
    }
}
