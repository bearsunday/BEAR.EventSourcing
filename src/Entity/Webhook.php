<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

class Webhook extends AbstractEntity
{
    public function __construct(
        public int|null $id = null,
        public string $name = '',
        public string $url = '',
        public string $secret = '',
        public array $events = [],
        public bool $enabled = true,
        public DateTimeImmutable|null $createDate = null,
        public DateTimeImmutable|null $updateDate = null,
    ) {
    }
}
