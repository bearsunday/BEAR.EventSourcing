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
         * when a ParamsFilterInterface withheld a value (a credential, typically). Recorded
         * for a replay engine or an auditor to read: SemanticLogExtractor extracts the request
         * either way, so the event stream stays a complete record of what happened, and what
         * to do with a request it cannot re-execute faithfully is the replay engine's call.
         */
        public readonly bool $replayable = true,
    ) {
    }
}
