<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

/** A pure (unbacked) enum: http_build_query() cannot stringify one. */
enum FakeSuit
{
    case Hearts;
}
