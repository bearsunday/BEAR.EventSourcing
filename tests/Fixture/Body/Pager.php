<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Body;

final readonly class Pager
{
    public function __construct(
        private int $current,
        private int $last,
    ) {
    }
}
