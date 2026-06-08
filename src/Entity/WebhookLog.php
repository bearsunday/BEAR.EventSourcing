<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

class WebhookLog extends AbstractEntity
{
    public function __construct(
        public int|null $id = null,
        public int $webhookId = 0,
        public string $event = '',
        public string $payload = '',
        public int $responseCode = 0,
        public string|null $responseBody = null,
        public bool $success = false,
        public DateTimeImmutable|null $createDate = null,
    ) {
    }
}
