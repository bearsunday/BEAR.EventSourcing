<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\SemanticLogExtractor;
use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;

/**
 * A request with links, opened before its first request is made: the requests made to follow the links nest in it.
 *
 * The first request records its own query, as every request does.
 */
final class LinkRequestContext extends AbstractContext implements JsonSerializable
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = SemanticLogExtractor::LINK_REQUEST_TYPE;

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/link-request.json';

    /** @param list<string> $links each link as `type:rel`, in the order it is followed */
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public readonly array $links,
        public readonly string $timestamp,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->uri,
            'method' => $this->method,
            'links' => $this->links,
            'timestamp' => $this->timestamp,
        ];
    }
}
