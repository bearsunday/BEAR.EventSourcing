<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

class Plugin extends AbstractEntity
{
    public function __construct(
        public int|null $id = null,
        public string $name = '',
        public string $code = '',
        public string $version = '1.0.0',
        public string|null $source = null,
        public bool $enabled = false,
        public int $sortNo = 0,
        public DateTimeImmutable|null $createDate = null,
        public DateTimeImmutable|null $updateDate = null,
    ) {
    }
}
