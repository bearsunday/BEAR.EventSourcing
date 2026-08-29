<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\MediaQuery;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Leaf event for one executed media query: intent (name + params) and wall
 * time, attached under the currently open scope. Result rows are not
 * available through Ray.MediaQuery's logger seam; `rows_ref` stays optional
 * in the schema for a future richer upstream contract.
 */
final class MediaQueryContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'media_query';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/media-query.json';

    /** @param array<string, mixed> $params */
    public function __construct(
        public readonly string $name,
        public readonly array $params,
        public readonly float $durationMs,
    ) {
    }
}
