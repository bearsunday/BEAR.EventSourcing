<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Throwable;

final readonly class SemanticLogInvoker implements InvokerInterface
{
    private const string TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

    private RecordedMethods $recordedMethods;
    private ParamsFilterInterface $paramsFilter;

    public function __construct(
        private InvokerInterface $invoker,
        private SemanticLoggerInterface $logger,
        private BodyStoreInterface $bodyStore,
        #[Recorded] RecordedMethods|null $recordedMethods = null,
        #[Filtered] ParamsFilterInterface|null $paramsFilter = null,
    ) {
        $this->recordedMethods = $recordedMethods ?? new RecordedMethods();
        // Secure by default: an application opts out of redaction by binding its own
        // #[Filtered] ParamsFilterInterface, never opts in to get it.
        $this->paramsFilter = $paramsFilter ?? new SensitiveParamsFilter();
    }

    public function invoke(AbstractRequest $request): ResourceObject
    {
        $method = $this->recordedMethods->normalize($request->method->value);
        if ($method === null) {
            return $this->invoker->invoke($request);
        }

        $filtered = $this->filterParams($request->query);
        $openId = $this->logger->open(new ResourceRequestContext(
            uri: self::stripQuery($request->toUri()),
            method: $method,
            params: $filtered->params,
            timestamp: (new DateTimeImmutable())->format(self::TIMESTAMP_FORMAT),
            replayable: $filtered->replayable,
        ));

        $start = hrtime(true);
        try {
            $ro = $this->invoker->invoke($request);
        } catch (Throwable $e) {
            $this->safeClose(
                new ResourceResponseContext(
                    code: self::httpCode($e),
                    exception: self::exceptionContext($e),
                    durationMs: self::elapsedMs($start),
                ),
                $openId,
            );

            throw $e;
        }

        $this->safeClose($this->responseContext($request, $ro, self::elapsedMs($start)), $openId);

        return $ro;
    }

    /**
     * Close the open observation without letting a logging failure escape.
     *
     * The bundled SemanticLogger records an out-of-order close as a diagnostic,
     * but the binding is an interface: an implementation that throws would, on
     * the success path, destroy a completed response and, on the error path,
     * mask the real domain exception. Observation must never break the request,
     * so a close failure is downgraded to a warning.
     */
    private function safeClose(ResourceResponseContext $context, string $openId): void
    {
        try {
            $this->logger->close($context, $openId);
        } catch (Throwable $e) {
            self::warn(sprintf('Semantic log close failed: %s', $e->getMessage()));
        }
    }

    /**
     * Filter params without letting a filter failure escape — and without recording what
     * it failed to filter.
     *
     * The filter is an application-supplied boundary, so it can throw on a param shape it did
     * not expect. Observation must never break the request, but recording the unfiltered
     * params on failure would turn a logging bug into a credential leak: fail closed, record
     * nothing, and mark the request non-replayable so the gap is visible.
     *
     * @param array<string, mixed> $params
     */
    private function filterParams(array $params): FilteredParams
    {
        try {
            return ($this->paramsFilter)($params);
        } catch (Throwable $e) {
            // The class only: a filter's message may quote the very values it was handed.
            self::warn(sprintf('Params filter failed, params withheld: %s', $e::class));

            return new FilteredParams([], replayable: false);
        }
    }

    private static function warn(string $message): void
    {
        try {
            trigger_error($message, E_USER_WARNING);
        } catch (Throwable) {
            // A strict error handler (e.g. Symfony/Laravel) may turn the warning into
            // an exception; swallow it too so observation never breaks the request.
        }
    }

    private function responseContext(
        AbstractRequest $request,
        ResourceObject $ro,
        float $durationMs,
    ): ResourceResponseContext {
        try {
            $bodyRef = ($this->bodyStore)($request, $ro);
        } catch (Throwable $e) {
            // Observation must not break a completed request: keep the real code and record the failure.
            return new ResourceResponseContext(
                code: $ro->code,
                exception: self::exceptionContext($e),
                durationMs: $durationMs,
            );
        }

        return new ResourceResponseContext($ro->code, $bodyRef, durationMs: $durationMs);
    }

    private static function elapsedMs(int|float $start): float
    {
        return round(((float) hrtime(true) - (float) $start) / 1e6, 3);
    }

    /**
     * Keep the resource uri canonical (path only). The query already lives in
     * `params`, so recording it in the uri too would duplicate it into the event
     * uri and make the stree formatter render the query string twice.
     */
    private static function stripQuery(string $uri): string
    {
        $queryStart = strpos($uri, '?');

        return $queryStart === false ? $uri : substr($uri, 0, $queryStart);
    }

    private static function httpCode(Throwable $e): int
    {
        $code = $e->getCode();

        return is_int($code) && $code >= 400 && $code < 600 ? $code : 500;
    }

    /** @return array{class: class-string, message: string} */
    private static function exceptionContext(Throwable $e): array
    {
        return [
            'class' => $e::class,
            'message' => $e->getMessage(),
        ];
    }
}
