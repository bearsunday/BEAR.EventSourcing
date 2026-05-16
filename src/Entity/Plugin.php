<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

class Plugin extends AbstractEntity
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $code = '',
        public string $version = '1.0.0',
        public ?string $source = null,
        public bool $enabled = false,
        public int $sortNo = 0,
        public ?\DateTimeImmutable $createDate = null,
        public ?\DateTimeImmutable $updateDate = null
    ) {}
}
