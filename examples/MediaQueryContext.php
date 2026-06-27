<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Examples;

use Koriym\SemanticLogger\AbstractContext;

/**
 * Example-only context for a Ray.MediaQuery DB operation embedded inside a
 * resource. Media query observation is a separate concern from this package;
 * the node is here to show a non-resource operation nesting cleanly in the tree.
 */
final class MediaQueryContext extends AbstractContext
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'media_query';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/schemas/semantic-logger/media-query.json';

    // `name`, not `id`: stree's compact signal excludes an `id` key (it treats
    // it as the node's structural identifier), so the query identity is `name`.
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
    ) {
    }
}
