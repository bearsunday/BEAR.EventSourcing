<?php

declare(strict_types=1);

namespace BearEccube\Entity;

class Webhook extends AbstractEntity
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $url = '',
        public string $secret = '',
        public array $events = [],
        public bool $enabled = true,
        public ?\DateTimeImmutable $createDate = null,
        public ?\DateTimeImmutable $updateDate = null
    ) {}
}
