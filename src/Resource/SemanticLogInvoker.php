<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Throwable;

final class SemanticLogInvoker implements InvokerInterface
{
    private const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

    private readonly RecordedMethods $recordedMethods;

    public function __construct(
        private readonly InvokerInterface $invoker,
        private readonly SemanticLoggerInterface $logger,
        private readonly ViewStoreInterface $viewStore,
        RecordedMethods|null $recordedMethods = null,
    ) {
        $this->recordedMethods = $recordedMethods ?? new RecordedMethods();
    }

    public function invoke(AbstractRequest $request): ResourceObject
    {
        $method = $this->recordedMethods->normalize($request->method->value);
        if ($method === null) {
            return $this->invoker->invoke($request);
        }

        $openId = $this->logger->open(new ResourceRequestContext(
            uri: $request->toUri(),
            method: $method,
            params: $request->query,
            timestamp: (new DateTimeImmutable())->format(self::TIMESTAMP_FORMAT),
        ));

        try {
            $ro = $this->invoker->invoke($request);
        } catch (Throwable $e) {
            $this->logger->close(
                new ResourceResponseContext(code: self::httpCode($e), exception: self::exceptionContext($e)),
                $openId,
            );

            throw $e;
        }

        $this->logger->close($this->responseContext($request, $ro), $openId);

        return $ro;
    }

    private function responseContext(AbstractRequest $request, ResourceObject $ro): ResourceResponseContext
    {
        try {
            $viewRef = ($this->viewStore)($request, $ro);
        } catch (Throwable $e) {
            // Observation must not break a completed request: keep the real code and record the failure.
            return new ResourceResponseContext(code: $ro->code, exception: self::exceptionContext($e));
        }

        return new ResourceResponseContext($ro->code, $viewRef);
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
