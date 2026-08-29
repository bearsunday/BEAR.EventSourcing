<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Examples;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;

/**
 * The result of an embedded media query. The rows are externalized and kept
 * behind a `rows_ref` pointer — the same `*_ref` rule the resource bridge uses
 * for `body_ref`, so any node's heavy detail sits behind a pointer.
 */
final class MediaQueryResultContext extends AbstractContext implements JsonSerializable
{
    /** @psalm-suppress InvalidClassConstantType */
    public const TYPE = 'media_query_result';

    /** @psalm-suppress InvalidClassConstantType */
    public const SCHEMA_URL = 'https://bearsunday.github.io/BEAR.EventSourcing/schemas/media-query-result.json';

    /** @param non-empty-string $rowsRef */
    public function __construct(
        public readonly string $rowsRef,
    ) {
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return ['rows_ref' => $this->rowsRef];
    }
}
