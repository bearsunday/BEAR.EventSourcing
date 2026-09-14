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
         * when a ParamsFilterInterface withheld a value (a credential, typically).
         * SemanticLogExtractor extracts the request either way, so the event stream stays a
         * complete record of what happened, and carries the flag onto Event::$replayable
         * (and into the store) for the replay engine, whose call it is what to do with a
         * request it cannot re-execute faithfully.
         */
        public readonly bool $replayable = true,
    ) {
    }
}
