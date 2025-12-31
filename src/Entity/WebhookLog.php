<?php

declare(strict_types=1);

namespace BearEccube\Entity;

class WebhookLog extends AbstractEntity
{
    public function __construct(
        public ?int $id = null,
        public int $webhookId = 0,
        public string $event = '',
        public string $payload = '',
        public int $responseCode = 0,
        public ?string $responseBody = null,
        public bool $success = false,
        public ?\DateTimeImmutable $createDate = null
    ) {}
}
