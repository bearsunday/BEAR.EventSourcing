<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Body;

/** No public property, like a paginated domain list */
final class EntryList
{
    public self|null $previous = null;

    /** @param list<mixed> $items */
    public function __construct(
        protected array $items,
        private Pager $pager,
        private mixed $service = null,
    ) {
    }
}
