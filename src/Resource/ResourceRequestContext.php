<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\SemanticLogExtractor;
use Koriym\SemanticLogger\AbstractContext;

final class ResourceRequestContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = SemanticLogExtractor::RESOURCE_REQUEST_TYPE;

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/resource-request.json';

    /** @param array<string, mixed> $params */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly array $params,
        public readonly string $timestamp,
        /**
         * Whether $params is complete enough to re-execute this operation faithfully. False
         * when a ParamsFilterInterface removed a key (a credential, typically): the request is
         * still visible in this log for audit purposes, but SemanticLogExtractor excludes it
         * from the event stream rather than mint a source-of-truth fact it cannot replay.
         */
        public readonly bool $replayable = true,
    ) {
    }
}
