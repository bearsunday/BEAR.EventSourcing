<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use Koriym\SemanticLogger\AbstractContext;

final class ResourceResponseContext extends AbstractContext
{
    public function __construct(
        public int $code,
        public mixed $body,
    ) {
    }
}
