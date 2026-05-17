<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger;

use BEAR\Resource\LoggerInterface;
use BEAR\Resource\ResourceObject;
use Koriym\SemanticLogger\SemanticLoggerInterface;

final class SemanticLogger implements LoggerInterface
{
    private const RECORDED_METHODS = ['post', 'put', 'patch', 'delete'];

    public function __construct(
        private readonly SemanticLoggerInterface $logger,
    ) {
    }

    public function __invoke(ResourceObject $ro): void
    {
        $method = strtolower((string) $ro->uri->method);
        if (! in_array($method, self::RECORDED_METHODS, true)) {
            return;
        }

        $openId = $this->logger->open(new ResourceRequestContext(
            uri: (string) $ro->uri,
            method: strtoupper($method),
            query: $ro->uri->query,
        ));

        $this->logger->close(
            new ResourceResponseContext(
                code: $ro->code,
                body: $ro->body,
                headers: $ro->headers,
            ),
            $openId,
        );
    }
}
