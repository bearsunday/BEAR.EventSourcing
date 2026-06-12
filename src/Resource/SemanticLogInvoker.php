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
            $viewRef = ($this->viewStore)($request, $ro);
            $this->logger->close(new ResourceResponseContext($ro->code, $viewRef), $openId);

            return $ro;
        } catch (Throwable $e) {
            $this->logger->close(
                new ResourceResponseContext(
                    code: 500,
                    exception: [
                        'class' => $e::class,
                        'message' => $e->getMessage(),
                    ],
                ),
                $openId,
            );

            throw $e;
        }
    }
}
